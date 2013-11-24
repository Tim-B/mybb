<?php

require_once MYBB_ROOT . '/inc/class_parser.php';

class ParserClassTest extends MyBB_UnitTest
{

    public function setUp()
    {
        parent::setUp();
        $this->loadDefaultEnvironment();
        $this->loadEnvironmentModule('lang');
        $this->loadEnvironmentModule('plugins');
        $this->loadEnvironmentModule('cache');
    }

    protected function makeInstance()
    {
        return new postParser();
    }

    public function test_mycode()
    {
        $values = $this->bbCodeTests();
        $this->parse($values);

    }

    public function test_nomycode()
    {
        $regularValues = $this->bbCodeTests();
        $values = array();

        foreach ($regularValues as $key => $value)
        {
            $values[] = array($value[0], $value[0]);
        }

        $options = $this->getDefaultOptions();
        $options['allow_mycode'] = false;

        $this->parse($values, $options);
    }

    protected function parse($values, $options = null)
    {
        $instance = $this->makeInstance();
        if ($options == null)
        {
            $options = $this->getDefaultOptions();
        }
        foreach ($values as $testName => $testData)
        {
            $input = $testData[0];
            $expected = $testData[1];
            $output = $instance->parse_message($input, $options);
            $this->assertEquals($expected, $output);
        }
    }

    public function test_slapme()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = '/slap foobar';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '<span style="color: red;">* Joe Bloggs slaps foobar around a bit with a large trout.</span>');
        $input = '/me likes cookies';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '<span style="color: red;">* Joe Bloggs likes cookies</span>');
    }

    public function test_html()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();
        $options['allow_html'] = true;

        $input = '<strong>Testing</strong>';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '<strong>Testing</strong>');

        $input = '<meta name="description" content="Hello World">';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '&lt;meta name=&quot;description&quot; content=&quot;Hello World&quot;&gt;');

        $input = '<script src="http://something.com/whatever.js" />';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '&lt;script src=&quot;http://something.com/whatever.js&quot; /&gt;');
    }

    public function test_htmlencode()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = '<strong>Testing</strong>';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '&lt;strong&gt;Testing&lt;/strong&gt;');
    }

    public function test_nl2br()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = "Hello \n World";
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, "Hello <br />\n World");
    }

    public function test_smilies()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = 'Hello :) World';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'Hello <img src="images/smilies/smile.gif" style="vertical-align: middle;" border="0" alt="Smile" title="Smile" /> World');

        $options['allow_smilies'] = false;
        $input = 'Hello :) World';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'Hello :) World');
    }

    public function test_badwords()
    {

        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = 'What the flap?!';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'What the flop?!');

        $options['filter_badwords'] = false;
        $input = 'What the flap?!';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'What the flap?!');
    }

    public function test_custom_mycode()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = '[cool]Joe Bloggs[/cool]';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'Joe Bloggs is so cool!');

        $input = '[beans]Joe Bloggs[/beans]';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'Joe Bloggs is so cool!');

        $options['allow_mycode'] = false;
        $input = '[cool]Joe Bloggs[/cool]';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '[cool]Joe Bloggs[/cool]');
    }

    public function test_highlight()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();
        $options['highlight'] = 'world';

        $input = 'Hello World';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, 'Hello <span class="highlight" style="padding-left: 0px; padding-right: 0px;">World</span>');
    }

    public function test_code()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = '[code]test[/code]';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, "<div class=\"codeblock\">\n<div class=\"title\">Code:<br />\n</div><div class=\"body\" dir=\"ltr\"><code>test</code></div></div>\n");
        $input = '[php]test();[/php]';
        $output = $instance->parse_message($input, $options);

        $expected = "<div class=\"codeblock phpcodeblock\"><div class=\"title\">PHP Code:<br />\n";
        $expected .= "</div><div class=\"body\"><div dir=\"ltr\"><code><span style=\"color: #0000BB\">test</span>";
        $expected .= "<span style=\"color: #007700\">();&nbsp;<br /></span></code></div></div></div>\n";

        $this->assertEquals($output, $expected);
    }

    /**
     * There is an undefined variable bug here which needs to be fixed!
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function test_img()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = '[img=100x100]http://example.com/something.jpg[/img]';
        $output = $instance->parse_message($input, $options);
        $this->assertEquals($output, '<img src="http://example.com/something.jpg" width="100" height="100" border="0" alt="[Image: something.jpg]" style="float: left;" />');
    }


    /**
     * There is an undefined index bug here which needs to be fixed!
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function test_video()
    {
        $options = $this->getDefaultOptions();
        $instance = $this->makeInstance();

        $input = '[video=youtube]http://www.youtube.com/watch?v=oHg5SJYRHA0[/video]';
        $output = $instance->parse_message($input, $options);
        $expected = "test";

        $this->assertEquals($output, $expected);
    }


    protected function getDefaultOptions()
    {
        return array(
            'allow_html' => false,
            'allow_smilies' => true,
            'allow_mycode' => true,
            'allow_imgcode' => true,
            'allow_videocode' => true,
            'nl2br' => true,
            'filter_badwords' => true,
            'me_username' => 'Joe Bloggs',
            'shorten_urls' => true,
            'highlight' => false,
        );
    }

    protected function bbCodeTests()
    {
        // test name => array(input, expected)
        return array(
            'bold' => array('[b]test[/b]', '<span style="font-weight: bold;">test</span>'),
            'underline' => array('[u]test[/u]', '<span style="text-decoration: underline;">test</span>'),
            'italics' => array('[i]test[/i]', '<span style="font-style: italic;">test</span>'),
            'strike' => array('[s]test[/s]', '<del>test</del>'),
            'copy' => array('(c)', '&copy;'),
            'tm' => array('(tm)', '&#153;'),
            'reg' => array('(r)', '&reg;'),
            'url1' => array('[url]example.com[/url]', '<a href="http://example.com" target="_blank">http://example.com</a>'),
            'url2' => array('[url]http://example.com[/url]', '<a href="http://example.com" target="_blank">http://example.com</a>'),
            'url3' => array('[url=http://example.com]foobar[/url]', '<a href="http://example.com" target="_blank">foobar</a>'),
            'url4' => array('[url=example.com]foobar[/url]', '<a href="http://example.com" target="_blank">foobar</a>'),
            'email' => array('[email]joe@blogs.com[/email]', '<a href="mailto:joe@blogs.com">joe@blogs.com</a>'),
            'email2' => array('[email=joe@blogs.com]Joe[/email]', '<a href="mailto:joe@blogs.com">Joe</a>'),
            'hr' => array('[hr]', '<hr />'),
            'color' => array('[color=#ffffff]test[/color]', '<span style="color: #ffffff;">test</span>'),
            'size' => array('[size=large]test[/size]', '<span style="font-size: large;">test</span>'),
            'size2' => array('[size=20]test[/size]', '<span style="font-size: 30pt;">test</span>'),
            'font' => array('[font=arial]test[/font]', '<span style="font-family: arial;">test</span>'),
            'align' => array('[align=right]test[/align]', '<div style="text-align: right;">test</div>'),
        );
    }

}