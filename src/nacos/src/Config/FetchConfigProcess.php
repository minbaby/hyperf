<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Hyperf\Nacos\Config;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Client;
use Hyperf\Process\AbstractProcess;
use Swoole\Coroutine\Server as CoServer;
use Swoole\Server;

class FetchConfigProcess extends AbstractProcess
{
    /**
     * @var string
     */
    public $name = 'nacos-config-fetcher';

    /**
     * @var CoServer|Server
     */
    protected $server;

    public function bind($server): void
    {
        $this->server = $server;
        parent::bind($server);
    }

    public function handle(): void
    {
        $workerCount = $this->server->setting['worker_num'] + $this->server->setting['task_worker_num'] - 1;
        $cache = [];
        $config = $this->container->get(ConfigInterface::class);
        $client = $this->container->get(Client::class);
        while (true) {
            $remoteConfig = $client->pull();
            if ($remoteConfig != $cache) {
                $pipeMessage = new PipeMessage($remoteConfig);
                for ($workerId = 0; $workerId <= $workerCount; ++$workerId) {
                    $this->server->sendMessage($pipeMessage, $workerId);
                }
                $cache = $remoteConfig;
            }
            sleep((int) $config->get('nacos.config_reload_interval', 3));
        }
    }

    public function isEnable($server): bool
    {
        $config = $this->container->get(ConfigInterface::class);
        return $server instanceof Server && (bool) $config->get('nacos.config_reload_interval', false);
    }
}
