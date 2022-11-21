<?php

declare(strict_types=1);

use function Imi\env;

require \dirname(__DIR__) . '/vendor/autoload.php';

/**
 * 开启服务器.
 */
function startServer(): void
{
    // @phpstan-ignore-next-line
    function checkHttpServerStatus(): bool
    {
        $serverStarted = false;
        for ($i = 0; $i < 20; ++$i)
        {
            sleep(1);
            $context = stream_context_create(['http' => ['timeout' => 3]]);
            if ('imi' === @file_get_contents(env('HTTP_SERVER_HOST', 'http://127.0.0.1:13000/'), false, $context))
            {
                $serverStarted = true;
                break;
            }
        }

        return $serverStarted;
    }

    if ('\\' === \DIRECTORY_SEPARATOR)
    {
        $servers = [
            'HttpServer'    => [
                'start'         => __DIR__ . '\unit\HttpServer\bin\start.ps1 -d 1',
                'stop'          => __DIR__ . '\unit\HttpServer\bin\stop.ps1',
                'checkStatus'   => 'checkHttpServerStatus',
            ],
        ];
    }
    else
    {
        $servers = [
            'HttpServer'    => [
                'start'         => __DIR__ . '/unit/HttpServer/bin/start.sh -d',
                'stop'          => __DIR__ . '/unit/HttpServer/bin/stop.sh',
                'checkStatus'   => 'checkHttpServerStatus',
            ],
        ];
    }

    foreach ($servers as $name => $options)
    {
        // start server
        if ('\\' === \DIRECTORY_SEPARATOR)
        {
            $cmd = 'powershell ' . $options['start'];
        }
        else
        {
            $cmd = 'nohup ' . $options['start'] . ' > /dev/null 2>&1';
        }
        echo "Starting {$name}...", \PHP_EOL;
        shell_exec("{$cmd}");

        register_shutdown_function(static function () use ($name, $options) {
            // stop server
            $cmd = $options['stop'];
            if ('\\' === \DIRECTORY_SEPARATOR)
            {
                $cmd = 'powershell ' . $cmd;
            }
            echo "Stoping {$name}...", \PHP_EOL;
            shell_exec("{$cmd}");
            echo "{$name} stoped!", \PHP_EOL, \PHP_EOL;
        });

        if (($options['checkStatus'])())
        {
            echo "{$name} started!", \PHP_EOL;
        }
        else
        {
            throw new \RuntimeException("{$name} start failed");
        }
    }
}

startServer();
