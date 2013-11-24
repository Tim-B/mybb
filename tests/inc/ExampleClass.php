<?php

class ExampleCLass
{
    public $foo;
    public $bar;
    public $pizza = 'pepperoni';
    public $cookies = 'choc chip';
    public $numbers = array('one' => 1, 'two' => 2);
    public $letters = array('a' => 'A', 'b' => 'B');

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }
}