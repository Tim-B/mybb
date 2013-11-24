<?php

abstract class MyBB_Mock
{

    protected $targetClassName;
    protected $targetClassPath;
    protected $realInstance;
    protected $function_calls = array();
    protected $attribute_calls = array();
    protected $attribute_sets = array();

    public function __construct()
    {
        $arguments = func_get_args();
        $this->realInstance = $this->getRealInstance($arguments);
        $this->mock_setup();
    }

    protected function mock_setup()
    {

    }

    protected function getRealInstance($arguments)
    {
        $require = MYBB_ROOT . '/' . $this->targetClassPath;
        require_once $require;

        $reflector = new ReflectionClass($this->targetClassName);

        return $reflector->newInstanceArgs($arguments);
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->function_calls[$name]))
        {
            $this->function_calls[$name] = 0;
        }
        $this->function_calls[$name]++;
        $mockMethod = 'call_' . $name;
        if (method_exists($this, $mockMethod))
        {
            return call_user_func_array(array($this, $mockMethod), $arguments);
        }

        return call_user_func_array(array($this->realInstance, $name), $arguments);
    }

    public function &__get($name)
    {
        if (!isset($this->attribute_calls[$name]))
        {
            $this->attribute_calls[$name] = 0;
        }
        $this->attribute_calls[$name]++;
        $mockMethod = 'get_' . $name;
        if (method_exists($this, $mockMethod))
        {
            $return = & call_user_func(array($this, $mockMethod));

            return $return;
        }

        return $this->realInstance->$name;
    }

    public function __set($name, $value)
    {
        if (!isset($this->attribute_sets[$name]))
        {
            $this->attribute_sets[$name] = 0;
        }
        $this->attribute_sets[$name]++;
        $mockMethod = 'set_' . $name;
        if (method_exists($this, $mockMethod))
        {
            return call_user_func_array(array($this, $mockMethod), array($value));
        }

        return $this->realInstance->$name = $value;
    }

    public function mock_getFunctionCallCount($name)
    {
        if (isset($this->function_calls[$name]))
        {
            return $this->function_calls[$name];
        }

        return 0;
    }

    public function mock_getAttributeCallCount($name)
    {
        if (isset($this->attribute_calls[$name]))
        {
            return $this->attribute_calls[$name];
        }

        return 0;
    }

    public function mock_getAttributeSetCount($name)
    {
        if (isset($this->attribute_sets[$name]))
        {
            return $this->attribute_sets[$name];
        }

        return 0;
    }

    public function __isset($name)
    {
        return isset($this->realInstance->$name);
    }

    public function __unset($name)
    {
        unset($this->realInstance->$name);
    }

}