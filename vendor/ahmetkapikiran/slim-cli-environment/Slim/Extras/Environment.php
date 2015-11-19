<?php
namespace Slim\Extras;

class Environment extends \Slim\Environment
{
    function __construct()
    {
        if (PHP_SAPI == 'cli') {

            $argv = $GLOBALS['argv'];

            array_shift($argv);

            //Convert $argv to PATH_INFO
            $env              = self::mock(
                array(
                    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
                    'PATH_INFO'   => '/' . implode('/', $argv)
                )
            );
            $this->properties = $env;
        }
    }
}