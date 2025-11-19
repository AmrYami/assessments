<?php

$loader = require __DIR__ . '/../../../vendor/autoload.php';

if ($loader instanceof Composer\Autoload\ClassLoader) {
    $loader->addPsr4('Fakeeh\\Assessments\\Tests\\', __DIR__);
}
