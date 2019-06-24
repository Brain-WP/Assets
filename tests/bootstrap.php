<?php # -*- coding: utf-8 -*-

declare(strict_types=1);

if (!file_exists(dirname(__DIR__)  . '/vendor/autoload.php')) {
    die('Please install via Composer before running tests.');
}

putenv('TESTS_DIR=' . __DIR__);
putenv('FIXTURES_DIR=' . __DIR__ . '/fixtures');

require_once dirname(__DIR__) . '/vendor/autoload.php';
