<?php

namespace Prestashop;

use PHPUnit_Framework_TestCase;
use Prestashop\Controller\FrontController;

class ControllerTest extends PHPUnit_Framework_TestCase
{
    public function testControllerResponse()
    {
        $_SERVER['REQUEST_URI'] = '/';

        $controller = new FrontController();
        $controller->run();
    }
}