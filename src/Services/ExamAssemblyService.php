<?php

namespace Streaming\Assessments\Services;

use Streaming\Assessments\Domain\Models\{Exam, Question};
use Illuminate\Support\Collection;

class ExamAssemblyService
{
    public function __construct(private QuestionPoolCache $poolCache)
    {
    }

    public function buildPool(Exam $exam, ?array $topicIds = null): Collection
    {
        $topicIds = $topicIds ?? $exam->topics()->pluck('assessment_topics.id')->all();
        $categoryId = $exam->category_id ? (int) $exam->category_id : null;

        $payload = $this->poolCache->remember($categoryId, $topicIds, function () use ($categoryId, $topicIds) {
            $query = Question::query()->where('is_active', true);
            if ($categoryId) {
                $query->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId));
            }
            if (!empty($topicIds)) {
                $query->whereHas('topics', function ($q) use ($topicIds) {
                    $q->whereIn('assessment_topics.id', $topicIds);
                });
            }

            return $query->get(['id', 'weight', 'difficulty'])
                ->map(fn($item) => [
                    'id' => (int) $item->id,
                    'weight' => (int) ($item->weight ?? 0),
                    'difficulty' => $item->difficulty,
                ])
                ->values()
                ->all();
        });

        return collect($payload);
    }

    public function seededShuffle(array $arr, int $seed): array
    {
        // Simple deterministic shuffle using mt_rand seeded
        mt_srand($seed);
        for ($i = count($arr) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        }
        mt_srand(); // reset
        return $arr;
    }

    public function sampleByCount(Collection $pool, int $count, int $seed, ?int $userId = null, ?array $difficultyCounts = null): array
    {
        $ids = $pool->pluck('id')->all();
        $exposures = collect();
        $strictExposure = false;

        if ($userId && config('assessments.exposure_enabled')) {
            $exposures = \DB::table('assessment_question_exposures')
                ->where('user_id', $userId)
                ->whereIn('question_id', $ids)
                ->get()
                ->keyBy('question_id');
            $strictExposure = (bool) config('assessments.exposure_strict', false);
        }

        $isUnseen = static function (int $questionId) use ($exposures): bool {
            $entry = $exposures->get($questionId);
            if (!$entry) {
                return true;
            }
            return (int) ($entry->seen_count ?? 0) === 0;
        };

        $orderIds = function (array $ids, int $seed) use ($userId, $exposures) {
            if ($userId && config('assessments.exposure_enabled')) {
                usort($ids, function ($a, $b) use ($exposures, $seed) {
                    $ea = $exposures->get($a);
                    $eb = $exposures->get($b);
                    $sa = $ea->seen_count ?? null;
                    $sb = $eb->seen_count ?? null;
                    if ($sa === null && $sb !== null) {
                        return -1;
                    }
                    if ($sa !== null && $sb === null) {
                        return 1;
                    }
                    if ($sa !== $sb) {
                        return ($sa ?? 0) <=> ($sb ?? 0);
                    }
                    $la = $ea && $ea->last_seen_at ? strtotime($ea->last_seen_at) : 0;
                    $lb = $eb && $eb->last_seen_at ? strtotime($eb->last_seen_at) : 0;
                    if ($la !== $lb) {
                        return $la <=> $lb;
                    }
                    $ha = crc32($a . '-' . $seed);
                    $hb = crc32($b . '-' . $seed);
                    return $ha <=> $hb;
                });
                return $ids;
            }

            return $this->seededShuffle($ids, $seed);
        };

        if ($difficultyCounts !== null) {
            $difficultyCounts = array_map('intval', $difficultyCounts);
            $selected = [];
            $alreadySelected = [];

            foreach ($difficultyCounts as $difficulty => $needed) {
                if ($needed <= 0) {
                    continue;
                }

                $groupAll = $pool->filter(function ($question) use ($difficulty, $alreadySelected) {
                    $id = is_array($question) ? ($question['id'] ?? null) : ($question->id ?? null);
                    $diff = is_array($question) ? ($question['difficulty'] ?? null) : ($question->difficulty ?? null);
                    return $id !== null && !isset($alreadySelected[$id]) && $diff === $difficulty;
                })->pluck('id')->all();

                if (count($groupAll) < $needed) {
                    throw new \RuntimeException("Not enough {$difficulty} questions to satisfy exam quota.");
                }

                $groupEligible = $groupAll;
                if ($strictExposure) {
                    $groupEligible = array_values(array_filter($groupAll, $isUnseen));
                    if (count($groupEligible) < $needed) {
                        throw new \RuntimeException("Not enough unseen {$difficulty} questions to satisfy exposure policy.");
                    }
                }

                $ordered = $orderIds($groupEligible, crc32($difficulty . '-' . $seed));
                $picked = array_slice($ordered, 0, $needed);
                foreach ($picked as $id) {
                    $alreadySelected[$id] = true;
                }
                $selected = array_merge($selected, $picked);
            }

            if (count($selected) !== $count) {
                throw new \RuntimeException('Difficulty quotas do not sum to the requested question count.');
            }

            return $selected;
        }

        if ($count <= 0) {
            return [];
        }

        $eligibleIds = $pool->pluck('id')->all();
        if ($strictExposure) {
            $eligibleIds = array_values(array_filter($eligibleIds, $isUnseen));
            if (count($eligibleIds) < $count) {
                throw new \RuntimeException('Not enough unseen questions available to satisfy exposure policy.');
            }
        }

        if (count($eligibleIds) <= $count) {
            return $eligibleIds;
        }

        $ordered = $orderIds($eligibleIds, $seed);
        return array_slice($ordered, 0, $count);
    }

    public function subsetSumExact(Collection $pool, int $target): array
    {
        $items = $pool->values()->map(function ($item) {
            return [
                'id' => is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null),
                'weight' => (int) (is_array($item) ? ($item['weight'] ?? 0) : ($item->weight ?? 0)),
            ];
        })->all();
        $n = count($items);
        $dp = array_fill(0, $target + 1, null);
        $dp[0] = 0;
        for ($i = 0; $i < $n; $i++) {
            $w = (int) ($items[$i]['weight'] ?? 0);
            if ($w <= 0) continue;
            for ($s = $target; $s >= $w; $s--) {
                if ($dp[$s - $w] !== null && $dp[$s] === null) {
                    $dp[$s] = $dp[$s - $w] | (1 << $i);
                }
            }
        }
        if ($dp[$target] === null) return [];
        $mask = $dp[$target];
        $selected = [];
        for ($i = 0; $i < $n; $i++) if ($mask & (1 << $i)) $selected[] = $items[$i]['id'];
        return $selected;
    }

    public function sampleByScore(Collection $pool, int $target, ?array $difficultyTargets = null, bool $allowTolerance = false): array
    {
        $target = max(0, (int) $target);
        if ($difficultyTargets === null) {
            $subset = $this->subsetSumExact($pool, $target);
            if (!empty($subset)) {
                return $subset;
            }
            if ($allowTolerance) {
                return $this->subsetSumAtMost($pool, $target);
            }
            return [];
        }

        $selected = [];
        $remaining = $pool;

        foreach ($difficultyTargets as $difficulty => $scoreTarget) {
            $scoreTarget = (int) $scoreTarget;
            if ($scoreTarget <= 0) {
                continue;
            }

            $group = $remaining->where('difficulty', $difficulty);
            $subset = $this->subsetSumExact($group, $scoreTarget);
            if (empty($subset)) {
                if (!$allowTolerance) {
                    throw new \RuntimeException("Unable to satisfy {$difficulty} score target ({$scoreTarget}).");
                }
                $subset = $this->subsetSumAtMost($group, $scoreTarget);
                if (empty($subset)) {
                    throw new \RuntimeException("Unable to satisfy {$difficulty} score target ({$scoreTarget}).");
                }
            }
            $selected = array_merge($selected, $subset);
            $remaining = $remaining->reject(function ($question) use ($subset) {
                $id = is_array($question) ? ($question['id'] ?? null) : ($question->id ?? null);
                return $id !== null && in_array($id, $subset, true);
            });
        }

        return array_values(array_unique($selected));
    }

    public function coverage(Collection $pool, int $maxTarget = 100): array
    {
        $weights = $pool->pluck('weight')->map(fn($w) => (int)$w)->all();
        $reachable = array_fill(0, $maxTarget + 1, false);
        $reachable[0] = true;
        foreach ($weights as $w) {
            for ($s = $maxTarget; $s >= $w; $s--) {
                if ($reachable[$s - $w]) $reachable[$s] = true;
            }
        }
        return $reachable;
    }

    protected function subsetSumAtMost(Collection $pool, int $target): array
    {
        $target = max(0, (int) $target);
        if ($target === 0) {
            return [];
        }

        $items = $pool->values()->map(function ($item) {
            return [
                'id' => is_array($item) ? ($item['id'] ?? null) : ($item->id ?? null),
                'weight' => (int) (is_array($item) ? ($item['weight'] ?? 0) : ($item->weight ?? 0)),
            ];
        })->all();

        $dp = array_fill(0, $target + 1, null);
        $dp[0] = [];

        foreach ($items as $item) {
            $weight = $item['weight'];
            if ($weight <= 0) {
                continue;
            }
            for ($sum = $target; $sum >= 0; $sum--) {
                if ($dp[$sum] === null) {
                    continue;
                }
                $newSum = $sum + $weight;
                if ($newSum > $target) {
                    continue;
                }
                if ($dp[$newSum] === null || count($dp[$newSum]) > count($dp[$sum]) + 1) {
                    $dp[$newSum] = array_merge($dp[$sum], [$item['id']]);
                }
            }
        }

        for ($sum = $target; $sum >= 0; $sum--) {
            if ($dp[$sum] !== null && !empty($dp[$sum])) {
                return $dp[$sum];
            }
        }

        return [];
    }

    public function summarizeExamPool(Exam $exam): array
    {
        $pool = $this->buildPool($exam);
        $byDifficulty = $pool->groupBy('difficulty');

        $difficultySummary = [];
        foreach (['easy', 'medium', 'hard', 'very_hard'] as $diff) {
            $difficultySummary[$diff] = [
                'count' => ($byDifficulty[$diff] ?? collect())->count(),
                'score' => ($byDifficulty[$diff] ?? collect())->sum('weight'),
            ];
        }

        $targetScore = (int) ($exam->target_total_score ?? 0);
        $targetCount = (int) ($exam->question_count ?? 0);
        $strict = config('assessments.assembly.strict');

        $coverage = null;
        $coverageHints = [];
        if ($exam->assembly_mode === 'by_score' && $targetScore > 0) {
            $split = ($exam->difficulty_split_json['mode'] ?? null) === 'by_score'
                ? ($exam->difficulty_split_json['splits'] ?? null)
                : null;
            try {
                $selection = $this->sampleByScore($pool, $targetScore, $split, !$strict);
            } catch (\RuntimeException $e) {
                $selection = [];
                $coverageHints[] = $e->getMessage();
            }
            $actualScore = Question::whereIn('id', $selection)->sum('weight');
            if ($actualScore < $targetScore) {
                $coverageHints[] = "Pool score is {$actualScore}, target is {$targetScore}.";
            }
            $coverageHints = array_merge($coverageHints, $this->buildDifficultyHints($pool, $exam, 'by_score'));
            $coverage = [
                'requested' => $targetScore,
                'actual' => $actualScore,
                'tolerance' => !$strict && $actualScore < $targetScore,
                'hints' => $coverageHints,
            ];
        } elseif ($exam->assembly_mode === 'by_count' && $targetCount > 0) {
            $coverageHints = $this->buildDifficultyHints($pool, $exam, 'by_count');
            $coverage = [
                'requested' => $targetCount,
                'actual' => min($targetCount, $pool->count()),
                'tolerance' => false,
                'hints' => $coverageHints,
            ];
        }

        return [
            'pool_size' => $pool->count(),
            'pool_score' => $pool->sum('weight'),
            'difficulty' => $difficultySummary,
            'coverage' => $coverage,
        ];
    }

    protected function buildDifficultyHints(Collection $pool, Exam $exam, string $mode): array
    {
        $hints = [];
        $splits = $exam->difficulty_split_json['splits'] ?? [];
        $grouped = $pool->groupBy('difficulty');

        foreach ($splits as $diff => $required) {
            $required = (int) $required;
            if ($required <= 0) {
                continue;
            }
            if ($mode === 'by_count') {
                $available = ($grouped[$diff] ?? collect())->count();
                if ($available < $required) {
                    $hints[] = "Need {$required} {$diff} question(s); only {$available} available.";
                }
            } else {
                $available = ($grouped[$diff] ?? collect())->sum('weight');
                if ($available < $required) {
                    $hints[] = "Need {$required} score from {$diff}; pool provides {$available}.";
                }
            }
        }

        return $hints;
    }
}
