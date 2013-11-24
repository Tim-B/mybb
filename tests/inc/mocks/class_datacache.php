<?php

require_once MYBB_ROOT . '/inc/class_core.php';


class datacacheMock extends MyBB_Mock
{
    protected $targetClassName = 'datacache';
    protected $targetClassPath = 'inc/class_datacache.php';
    protected $cache_values = array();

    protected function call_read($name, $hard = false)
    {
        if (isset($this->cache_values[$name]))
        {
            return $this->cache_values[$name];
        }

        $dataMethod = 'cache_' . $name;
        if (method_exists($this, $dataMethod))
        {
            return call_user_func(array($this, $dataMethod));
        }

        return $this->realInstance->read($name, $hard);
    }

    public function setCacheValue($key, $value)
    {
        $this->cache_values[$key] = $value;
    }

    public function cache_smilies()
    {
        return array(
            1 => array(
                'sid' => '1',
                'name' => 'Smile',
                'find' => ':)',
                'image' => 'images/smilies/smile.gif',
                'disporder' => '1',
                'showclickable' => '1',
            ),
            4 => array(
                'sid' => '4',
                'name' => 'Big Grin',
                'find' => ':D',
                'image' => 'images/smilies/biggrin.gif',
                'disporder' => '4',
                'showclickable' => '1',
            ),
            5 => array(
                'sid' => '5',
                'name' => 'Tongue',
                'find' => ':P',
                'image' => 'images/smilies/tongue.gif',
                'disporder' => '5',
                'showclickable' => '1',
            ),
            8 => array(
                'sid' => '8',
                'name' => 'Sad',
                'find' => ':(',
                'image' => 'images/smilies/sad.gif',
                'disporder' => '8',
                'showclickable' => '1',
            ),
        );
    }

    public function cache_badwords()
    {
        return array(
            1 =>
            array(
                'bid' => '1',
                'badword' => 'flap',
                'replacement' => 'flop',
            ),
        );
    }

    public function cache_mycode()
    {
        return array(
            0 =>
            array(
                'regex' => '\\[beans\\](.*?)\\[/beans\\]',
                'replacement' => '[cool]$1[/cool]',
            ),
            1 =>
            array(
                'regex' => '\\[cool\\](.*?)\\[/cool\\]',
                'replacement' => '$1 is so cool!',
            ),
        );
    }
}