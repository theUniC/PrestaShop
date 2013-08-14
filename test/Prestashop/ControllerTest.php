<?php

namespace Prestashop;

use PHPUnit_Framework_TestCase;
use Prestashop\Controller\FrontController;

class RedirectController extends FrontController
{
    public function __construct()
    {
        parent::__construct();

        $this->redirect_after = 'http://google.es';
    }
}

class ControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function should_return_a_response_object_when_run_is_called()
    {
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $controller = new FrontController();
        Context::getContext()->smarty->assign('HOOK_HOME', 'test');
        $controller->setTemplate(__DIR__ . '/../../themes/default/index.tpl');
        $response =  $controller->run();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertContains('<!-- This is the index page, a comment that will be only printed in the index page -->', $response->getContent());
    }

    /**
     * @test
     */
    public function should_return_a_redirect_response_object_when_redirect_after_is_set()
    {
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $controller = new RedirectController();
        $response = $controller->run();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
    }
}