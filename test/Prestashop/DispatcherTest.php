<?php

namespace Prestashop;

use PHPUnit_Framework_TestCase;
use ReflectionObject;

class DispatcherTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        $_SERVER['HTTP_HOST'] = 'prestashop.local';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Dispatcher::$instance = null;
        $this->dispatcher = Dispatcher::getInstance();
    }

    protected function tearDown()
    {
        $_SERVER['HTTP_HOST'] = $_SERVER['REQUEST_URI'] = $_SERVER['REMOTE_ADDR'] = $_SERVER['REQUEST_METHOD'] = null;

        $this->dispatcher = $_GET['controller'] = null;
    }

    public function testDispatch()
    {
        $response = $this->dispatcher->dispatch();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertContains('<!-- This is the index page, a comment that will be only printed in the index page -->', $response->getContent());
    }

    /**
     * @test
     * @group getController
     */
    public function it_should_set_the_controller_into_the_querystring_when_the_controller_is_already_set()
    {
        $reflectedDispatcher = new ReflectionObject($this->dispatcher);
        $controllerProperty = $reflectedDispatcher->getProperty('controller');
        $controllerProperty->setAccessible(true);
        $controllerProperty->setValue($this->dispatcher, 'test');

        $this->assertEquals('test', $this->dispatcher->getController());
        $this->assertArrayHasKey('controller', $_GET);
        $this->assertEquals('test', $_GET['controller']);
    }

    /**
     * @test
     * @group getController
     */
    public function it_should_remap_controller_parameters_to_the_querystring_or_post_array()
    {
        $_GET['controller'] = 'test?foo=bar';

        $this->dispatcher->getController();

        $this->assertArrayHasKey('foo', $_GET);
        $this->assertEquals('bar', $_GET['foo']);
    }

    public function routesDataProvider()
    {
        return [
            ['/es/3-musica-ipods', 'category'],
            ['/es/1__applestore', 'supplier'],
            ['/es/1_apple-computer-inc', 'manufacturer'],
            ['/es/content/1-entrega', 'cms'],
            ['/es/content/category/1-entrega', 'cms'],
            ['/es/module/test/test', 'test'],
            ['/es/musica-ipods/1-ipod-nano.html', 'product'],
            ['/es/1-ipod-nano.html', 'product'],
            ['/es/3-musica-ipods/test', 'category'],
        ];
    }

    /**
     * @test
     * @group getController
     * @dataProvider routesDataProvider
     */
    public function it_should_match_the_correct_route_when_dispatching($requestUri, $expectedController)
    {
        $_SERVER['REQUEST_URI'] = $requestUri;

        Dispatcher::$instance = null;
        $this->dispatcher = Dispatcher::getInstance();
        $this->assertEquals($expectedController, $this->dispatcher->getController());
    }

    /**
     * @test
     * @group getController
     */
    public function it_should_match_module_routes_when_dispatching_and_apply_the_correct_mapping()
    {
        $_SERVER['REQUEST_URI'] = '/es/module/testmodule/module-testmodule-testmodulecontroller';

        Dispatcher::$instance = null;
        $this->dispatcher = Dispatcher::getInstance();
        $this->assertEquals('testmodulecontroller', $this->dispatcher->getController());
        $this->assertEquals('module', $_GET['fc']);
    }
}