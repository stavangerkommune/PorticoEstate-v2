<?php

// preload.php
$autoloadFile = __DIR__ . '/vendor/autoload.php';
require $autoloadFile;

$classMap = require __DIR__ . '/vendor/composer/autoload_classmap.php';

foreach ($classMap as $file)
{
	opcache_compile_file($file);
}
