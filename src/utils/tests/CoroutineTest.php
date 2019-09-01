<?php


namespace HyperfTest\Utils;

use Hyperf\Utils\Coroutine;
use PHPUnit\Framework\TestCase;
use Swoole\Coroutine\Scheduler;


class CoroutineTest extends TestCase
{
    /**
     * @EnableCoroutine
     */
    public function testEnableCo()
    {
        $this->assertIsInt(Coroutine::id());
    }

    /**
     * @DisableCoroutine
     */
    public function testDisableCo()
    {
        $this->assertEquals(-1, Coroutine::id());
        $this->assertFalse(\Swoole\Coroutine::getPcid());
    }

    protected function runTest()
    {
        $annotations = $this->getAnnotations();
        if (isset($annotations['method']['DisableCoroutine'])) {
            return parent::runTest();
        }

        $result = null;
        \Swoole\Coroutine\Run(function () use (&$result) {
            $result = parent::runTest();
        });
        return $result;
    }
}