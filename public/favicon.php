<?php

declare(strict_types=1);

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: public, max-age=604800');

readfile(__DIR__ . '/favicon.svg');
