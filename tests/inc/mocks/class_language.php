<?php

require_once MYBB_ROOT . '/inc/class_core.php';


class MyLanguageMock extends MyBB_Mock
{
    protected $targetClassName = 'MyLanguage';
    protected $targetClassPath = 'inc/class_language.php';

    public function mock_setup()
    {
        parent::mock_setup();
        $this->load_language_file('global');
    }

    public function load_language_file($section)
    {

        // Can't do this because of the require_once in class_language.php unfortunately
        /*
        $this->realInstance->language = 'english';
        $this->realInstance->path = MYBB_ROOT . '/inc/languages';
        $this->realInstance->load($section);
        */
        $file = MYBB_ROOT . '/inc/languages/english/' . $section . '.lang.php';
        require $file;

        if (isset($l))
        {
            foreach ($l as $key => $val)
            {
                $this->realInstance->$key = $val;
            }
        }
    }

}