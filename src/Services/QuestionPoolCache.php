<?php

namespace Amryami\Assessments\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class QuestionPoolCache
{
    private const KEY_RING = 'assessments:pool_keys';

    public function __construct(private CacheRepository $cache)
    {
    }

    public function remember(?int $categoryId, array $topicIds, callable $resolver): array
    {
        sort($topicIds);
        $key = $this->buildKey($categoryId, $topicIds);
        $ttl = (int) config('assessments.cache.pool_ttl', 300);

        if ($ttl <= 0) {
            return $resolver();
        }

        $data = $this->cache->get($key);
        if ($data !== null) {
            return $data;
        }

        $resolved = $resolver();
        $this->cache->put($key, $resolved, now()->addSeconds($ttl));
        $this->rememberKey($key);

        return $resolved;
    }

    public function flush(): void
    {
        $keys = $this->cache->get(self::KEY_RING, []);
        foreach ($keys as $key) {
            $this->cache->forget($key);
        }
        $this->cache->forget(self::KEY_RING);
    }

    private function buildKey(?int $categoryId, array $topicIds): string
    {
        $topicHash = empty($topicIds) ? 'none' : md5(json_encode(array_values($topicIds)));
        $category = $categoryId ?? 'none';

        return "assessments:pool:{$category}:{$topicHash}";
    }

    private function rememberKey(string $key): void
    {
        $keys = $this->cache->get(self::KEY_RING, []);
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            $this->cache->forever(self::KEY_RING, $keys);
        }
    }

}
