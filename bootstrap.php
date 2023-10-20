<?php

declare(strict_types=1);

namespace Imi\RoadRunner;

use Imi\App;

return static function (): void {
    $path = null;

    if (\defined('IMI_IN_PHAR') && IMI_IN_PHAR)
    {
        $path = \dirname(__DIR__, 3);
    }
    elseif (!class_exists(\Imi\App::class))
    {
        (static function () use (&$path): void {
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
