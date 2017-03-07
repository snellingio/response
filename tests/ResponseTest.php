<?php


class ResponseTest extends PHPUnit_Framework_TestCase
{

    public function testIsThereAnySyntaxError()
    {
        $var = new Snelling\Response;
        $this->assertTrue(is_object($var));
        unset($var);
    }
}