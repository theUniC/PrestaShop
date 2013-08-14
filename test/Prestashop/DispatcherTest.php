<?php

namespace Prestashop;

use PHPUnit_Framework_TestCase;

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    public function testDispatch()
    {
        $_SERVER['HTTP_HOST'] = 'localhost:8000.';
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $dispatcher = Dispatcher::getInstance();
        $response = $dispatcher->dispatch();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertContains('<!-- This is the index page, a comment that will be only printed in the index page -->', $response->getContent());
    }
}