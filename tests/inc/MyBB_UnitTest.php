<?php

require_once('MyBB_TestBase.php');

class MyBB_UnitTest extends MyBB_TestBase
{

    protected function getBaseRequirements()
    {
        return array(
            'inc/functions.php',
        );
    }

    protected function getRequrements()
    {
        return array();
    }

    protected function loadRequirements($requirements)
    {
        foreach ($requirements as $requirement)
        {
            require_once MYBB_ROOT . '/' . $requirement;
        }
    }

    public function setUp()
    {
        parent::setUp();
        $requirements = array_merge($this->getBaseRequirements(), $this->getRequrements());
        $this->loadRequirements($requirements);
    }

    public function loadDefaultEnvironment()
    {
        $this->loadEnvironmentModule('mybb');
    }

    public function tearDown()
    {

    }

    protected function loadEnvironmentModule($name)
    {
        $modules = $this->getEnvironmentModules();
        $module = $modules[$name];
        require_once MYBB_ROOT . '/tests/inc/mocks/' . $module['mock_file'] . '.php';
        $GLOBALS[$module['global']] = new $module['mock_class'];
    }

    protected function getEnvironmentModules()
    {
        return array(
            'mybb' => array(
                'global' => 'mybb',
                'mock_class' => 'MyBBMock',
                'mock_file' => 'class_core',
            ),
            'lang' => array(
                'global' => 'lang',
                'mock_class' => 'MyLanguageMock',
                'mock_file' => 'class_language',
            ),
            'plugins' => array(
                'global' => 'plugins',
                'mock_class' => 'PluginsMock',
                'mock_file' => 'class_plugins',
            ),
            'cache' => array(
                'global' => 'cache',
                'mock_class' => 'datacacheMock',
                'mock_file' => 'class_datacache',
            ),
        );
    }


}