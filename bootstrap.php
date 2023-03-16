<?php

declare(strict_types=1);

use Imi\App;
use Imi\RoadRunner\RoadRunnerApp;

return static function () {
    $path = null;

    if (\defined('IMI_IN_PHAR') && IMI_IN_PHAR)
    {
        $path = \dirname(__DIR__, 3);
        if (!class_exists(\Imi\App::class))
        {
            require $path . '/vendor/autoload.php';
        }
    }
    elseif (!class_exists(\Imi\App::class))
    {
        (static function () use (&$path) {
            foreach ([
                $_SERVER['PWD'] ?? null,
                getcwd(),
                \dirname(__DIR__, 3),
                \dirname(__DIR__, 5), // 在非工作路径，使用绝对路径启动
            ] as $path)
            {
                if (!$path)
                {
                    continue;
                }
                $fileName = $path . '/vendor/autoload.php';
                if (is_file($fileName))
                {
                    require $fileName;

                    return;
                }
            }
            echo 'No file vendor/autoload.php', \PHP_EOL;
            exit(255);
        })();
    }

    App::runApp($path ?? realpath(\dirname($_SERVER['SCRIPT_NAME'], 2)), RoadRunnerApp::class);
};
