<?php

$baseDir = dirname(__DIR__);
$autoloadCandidates = [
    $baseDir . '/vendor/autoload.php',
    $baseDir . '/../../../vendor/autoload.php',
];

$loader = null;
foreach ($autoloadCandidates as $autoload) {
    if (file_exists($autoload)) {
        $loader = require $autoload;
        break;
    }
}

if (! $loader instanceof Composer\Autoload\ClassLoader) {
    throw new RuntimeException('Unable to locate Composer autoload.php for Yami Assessments tests.');
}

$loader->addPsr4('Yami\\Assessments\\Tests\\', __DIR__);
