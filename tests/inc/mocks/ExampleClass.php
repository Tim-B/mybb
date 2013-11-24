<?php

class ExampleClassMock extends MyBB_Mock
{
    protected $targetClassName = 'ExampleClass';
    protected $targetClassPath = 'tests/inc/ExampleClass.php';
    protected $pizza = 'margherita';

    protected function call_getBar()
    {
        return 'cats dogs rats';
    }

    public function setPizza($type)
    {
        $this->pizza = $type;
    }

    protected function get_pizza()
    {
        return $this->pizza;
    }

    public function set_bar($value)
    {
        // do nothing!
    }

    public function get_letters()
    {
        return array('a' => 'A', 'c' => 'C');
    }
}