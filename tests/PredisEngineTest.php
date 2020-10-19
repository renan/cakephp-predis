<?php
namespace Renan\Cake\Predis;

use Cake\Cache\Cache;

final class PredisEngineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PredisEngine
     */
    private $engine;

    public function setUp()
    {
        $this->engine = new PredisEngine();
        $this->engine->init();
    }

    public function tearDown()
    {
        $this->engine->clear(false);
        unset($this->engine);
    }

    /**
     * @param mixed $value
     * @dataProvider writeAndReadDataTypes
     */
    public function testWriteAndReadDataTypesAndThenDelete($value)
    {
        $this->assertTrue($this->engine->set('dataType', $value));
        $this->assertEquals($value, $this->engine->get('dataType'));

        $this->assertTrue($this->engine->delete('dataType'));
        $this->assertFalse($this->engine->get('dataType', false));
    }

    public function writeAndReadDataTypes()
    {
        return [
            'string' => ['Hello world'],
            'integer' => [42],
            'float' => [13.37],
            'true' => [true],
            'false' => [false],
            'array' => [['foo' => 'bar']],
        ];
    }

    public function testIncrement()
    {
        $this->assertFalse($this->engine->increment('key'));

        $this->assertTrue($this->engine->set('key', 10));
        $this->assertEquals(11, $this->engine->increment('key'));
        $this->assertEquals(12, $this->engine->increment('key'));
        $this->assertEquals(13, $this->engine->increment('key'));
    }

    public function testDecrement()
    {
        $this->assertFalse($this->engine->decrement('key'));

        $this->assertTrue($this->engine->set('key', 10));
        $this->assertEquals(9, $this->engine->decrement('key'));
        $this->assertEquals(8, $this->engine->decrement('key'));
        $this->assertEquals(7, $this->engine->decrement('key'));
    }
}
