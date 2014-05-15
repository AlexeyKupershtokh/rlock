<?php
namespace Eleme\Rlock\Tests;

use Eleme\Rlock\Lock;
use Mockery;

class LockTest extends \PHPUnit_Framework_TestCase
{
    private $redisMock = null;
    private $lock = null;

    public function testLockSuccess()
    {
        $redis = Mockery::mock('predis');
        $redis->shouldReceive('setnx')->andReturn(true);
        $redis->shouldReceive('get')->andReturn(microtime(true) + 10);
        $redis->shouldReceive('del');

        $lockname = 'test';
        $lock = new Lock($redis, $lockname);
        $result = $lock->lock();
        $this->assertTrue($result);

        $redis = Mockery::mock('predis');
        $redis->shouldReceive('setnx')->andReturn(false);
        $redis->shouldReceive('get')->andReturn(microtime(true) - 10);
        $redis->shouldReceive('getset')->andReturn(microtime(true) - 10);
        $redis->shouldReceive('get')->andReturn(microtime(true) - 10);

        $lockname = 'test';
        $lock = new Lock($redis, $lockname);
        $result = $lock->lock();
        $this->assertTrue($result);
    }

    public function testLockFailed()
    {
        $redis = Mockery::mock('predis');
        $redis->shouldReceive('setnx')->andReturn(false);
        $redis->shouldReceive('get')->andReturn(microtime(true) - 10);
        $redis->shouldReceive('getset')->andReturn(microtime(true) + 10);

        $lockname = 'test';
        $lock = new Lock($redis, $lockname, array('blocking' => false));
        $result = $lock->lock();
        $this->assertFalse($result);

        $redis = Mockery::mock('predis');
        $redis->shouldReceive('setnx')->andReturn(false);
        $redis->shouldReceive('get')->andReturn(microtime(true) + 10);

        $lockname = 'test';
        $lock = new Lock($redis, $lockname, array('blocking' => false));
        $result = $lock->lock();
        $this->assertFalse($result);
    }

    public function testLocked()
    {
        $timeout = 1000;
        $interval = 100;
        $time = microtime(true);

        $redis = Mockery::mock('predis');
        $redis->shouldReceive('setnx')->andReturn(false);
        $redis->shouldReceive('get')->andReturn($time + $timeout / 1000);
        $redis->shouldReceive('getset')->andReturn($time + $timeout / 1000);

        $lockname = 'test';
        $lock = new Lock($redis, $lockname, array('timeout' => $timeout, 'interval' => $interval));
        $beforeLocked = microtime(true);
        $result = $lock->lock();
        $afterLocked = microtime(true);
        $this->assertTrue($beforeLocked + $timeout / 1000 <= $afterLocked);
    }

    public function testDestruct()
    {
        $redis = Mockery::mock('predis');
        $redis->shouldReceive('setnx')->andReturn(true);
        $redis->shouldReceive('get')->andReturn(microtime(true) + 10);
        $redis->shouldReceive('del');

        $lockname = 'test';
        $lock = new Lock($redis, $lockname);
        $result = $lock->lock();
        unset($lock);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRelease()
    {
        $redis = Mockery::mock('predis');

        $lockname = 'test';
        $lock = new Lock($redis, $lockname);
        $lock->release();
    }
}
