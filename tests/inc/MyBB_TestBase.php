<?php

class MyBB_TestBase extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        parent::setUp();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

}