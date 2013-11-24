<?php

require_once MYBB_ROOT . '/tests/inc/mocks/ExampleClass.php';

/**
 * Class MockingTest
 *
 * This test is intended to ensure the mocking functionality behaves correctly, and therefore will not effect tests.
 */

class MockingTest extends MyBB_UnitTest
{

    public function test_mock_creation()
    {
        $mock = new ExampleClassMock('hello', 'world');
        $this->assertEquals($mock->getFoo(), 'hello');
        $this->assertEquals($mock->getBar(), 'cats dogs rats');

        $this->assertEquals($mock->mock_getFunctionCallCount('getFoo'), 1);
        $this->assertEquals($mock->mock_getFunctionCallCount('getBar'), 1);
    }

    public function test_mock_class_attributes()
    {
        $mock = new ExampleClassMock('mars', 'venus');
        $this->assertEquals($mock->cookies, 'choc chip');
        $this->assertEquals($mock->pizza, 'margherita');
        $mock->setPizza('hawaiian');
        $this->assertEquals($mock->pizza, 'hawaiian');

        $this->assertEquals($mock->mock_getAttributeCallCount('pizza'), 2);
        $this->assertEquals($mock->mock_getAttributeCallCount('cookies'), 1);
    }

    public function test_mock_setattributes()
    {
        $mock = new ExampleClassMock('mars', 'venus');
        $this->assertEquals($mock->foo, 'mars');
        $this->assertEquals($mock->bar, 'venus');
        $mock->foo = 'foo';
        $mock->bar = 'bar';

        $mock->numbers['four'] = 4;

        $this->assertEquals($mock->foo, 'foo');
        $this->assertEquals($mock->bar, 'venus');
        $this->assertEquals($mock->numbers['four'], 4);

        $mock->numbers['four'] = 'IV';
        $this->assertEquals($mock->numbers['four'], 'IV');

        $this->assertEquals($mock->mock_getAttributeSetCount('foo'), 1);
        $this->assertEquals($mock->mock_getAttributeSetCount('bar'), 1);
    }

    public function test_mock_isset()
    {
        $mock = new ExampleClassMock('mars', 'venus');

        $this->assertTrue(isset($mock->pizza));
        $this->assertFalse(isset($mock->pasta));

        $this->assertTrue(isset($mock->numbers['one']));
        $this->assertFalse(isset($mock->numbers['three']));

        $this->assertTrue(isset($mock->letters['a']));
        $this->assertFalse(isset($mock->letters['b']));
        $this->assertTrue(isset($mock->letters['c']));
    }

    public function test_unset()
    {
        $mock = new ExampleClassMock('mars', 'venus');

        $mock->randomValue = 34;
        $this->assertEquals($mock->randomValue, 34);
        $this->assertTrue(isset($mock->randomValue));
        unset($mock->randomValue);
        $this->assertFalse(isset($mock->randomValue));
    }

}