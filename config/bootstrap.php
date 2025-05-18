<?php

use Platformsh\ConfigReader\Config;

if (getenv('PLATFORM_RELATIONSHIPS')) {
    $relationships = (new Config())->relationships;
    if (isset($relationships['mariadb'][0])) {
        $database = $relationships['mariadb'][0];
        $dsn = sprintf(
            'mysql://%s:%s@%s:%s/%s',
            $database['username'],
            $database['password'],
            $database['host'],
            $database['port'],
            $database['path']
        );
        $_ENV['DATABASE_URL'] = $dsn;
        $_SERVER['DATABASE_URL'] = $dsn;
        putenv("DATABASE_URL=$dsn");
    }
}