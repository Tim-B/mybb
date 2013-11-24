<?php

require_once MYBB_ROOT . '/inc/class_core.php';


class PluginsMock extends MyBB_Mock
{
    protected $targetClassName = 'pluginSystem';
    protected $targetClassPath = 'inc/class_plugins.php';

    public function run_hooks($hook, &$arguments = "")
    {
        return $arguments;
    }

}