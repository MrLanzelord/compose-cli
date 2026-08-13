#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

\ComposeCli\Support\EnvironmentGuard::check();

exit((new \ComposeCli\Application($argv))->run());
