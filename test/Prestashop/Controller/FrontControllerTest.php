<?php

namespace Prestashop\Controller;

use Faker\Factory as FakerFactory;
use Mockery;
use PHPUnit_Framework_TestCase;
use Prestashop\Cart;
use Prestashop\Configuration;
use Prestashop\Context;
use Prestashop\Cookie;
use Prestashop\Db\Db;
use ReflectionObject;
use stdClass;

/**
 * Class FrontControllerTest
 *
 * @package Prestashop\Controller
 */
class FrontControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var FrontController
     */
    protected $frontController;

    /**
     * @var Cookie
     */
    protected $cookie;

    protected function setUp()
    {
        $_SERVER['REQUEST_URI'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_GET = array();

        $context = Context::getContext();

        $this->cookie = Mockery::mock('\Prestashop\Cookie');
        $this->cookie->id_cart = null;
        $this->cookie->id_lang = null;
        $this->cookie->id_currency = null;
        $this->cookie->id_guest = null;
        $this->cookie->id_customer = null;
        $this->cookie->shouldReceive('write');
        $this->cookie->shouldReceive('__unset');
        $this->cookie->shouldReceive('mylogout');
        $this->cookie->shouldReceive('disallowWriting');

        $context->cookie = $this->cookie;

        $this->frontController = new FrontController();
    }

    protected function tearDown()
    {
        Configuration::loadConfiguration();
        FrontController::$initialized = false;
        $this->frontController = $this->cookie = null;
    }

    /**
     * @test
     * @group init
     */
    public function it_should_call_parent_init_method()
    {
        $this->frontController->init();

        $this->assertTrue(defined('_PS_BASE_URL_'));
        $this->assertTrue(defined('_PS_BASE_URL_SSL_'));
    }

    /**
     * @test
     * @group init
     */
    public function it_should_return_a_redirect_response_when_ssl_is_enabled()
    {
        Configuration::set('PS_SSL_ENABLED', true);

        $this->frontController->ssl = true;

        $response = $this->frontController->init();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertTrue($response->headers->getCacheControlDirective('no-cache'));
    }

    /**
     * @test
     * @group init
     */
    public function it_should_return_a_redirect_response_when_ssl_is_enabled_but_ssl_is_forced_to_false()
    {
        $_SERVER['HTTPS'] = 'On';
        Configuration::set('PS_SSL_ENABLED', true);

        $response = $this->frontController->init();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertTrue($response->headers->getCacheControlDirective('no-cache'));
    }

    /**
     * @test
     * @group init
     */
    public function it_should_remove_the_account_created_key_from_the_cookie_if_present()
    {
        $cookie = new Cookie('#test#');
        $context = Context::getContext();
        $context->cookie = $cookie;
        $context->cookie->account_created = true;

        $this->frontController->init();

        $this->assertArrayHasKey('account_created', $context->smarty->getTemplateVars());
        $this->assertFalse(isset($context->cookie->account_created));
    }

    /**
     * @test
     * @group init
     */
    public function it_should_return_a_redirect_response_if_authentication_is_required()
    {
        $this->frontController->auth = true;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $this->frontController->init());
    }

    /**
     * @test
     * @group init
     */
    public function it_should_logout_when_logout_is_requested_and_customer_is_active()
    {
        $_GET['logout'] = true;

        $customer = Mockery::mock('\Prestashop\Customer')->shouldDeferMissing();
        $customer->logged = true;
        $customer->shouldReceive('logout');

        Context::getContext()->customer = $customer;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $this->frontController->init());
    }

    /**
     * @test
     * @group init
     */
    public function it_should_logout_when_mylogout_querystring_parameter_has_been_set()
    {
        $_GET['mylogout'] = true;

        $customer = Mockery::mock('\Prestashop\Customer')->shouldDeferMissing();
        $customer->logged = false;
        $customer->shouldReceive('isBanned')->andReturn(false);

        Context::getContext()->customer = $customer;

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $this->frontController->init());
    }

    /**
     * @test
     * @group init
     */
    public function it_should_recover_the_already_existing_cart_which_the_id_is_stored_in_the_cookie()
    {
        $expected = new Cart(2);
        $this->cookie->id_cart = 2;

        $this->frontController->init();

        $cart = Context::getContext()->cart;
        $this->assertSame($expected->id, $cart->id);
    }

    /**
     * @test
     * @group init
     */
    public function it_should_redirect_to_the_canonical_url()
    {
        $_SERVER['REQUEST_URI'] = './';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->frontController->php_self = 'test';

        $response = $this->frontController->init();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertTrue($response->headers->getCacheControlDirective('no-cache'));
    }

    /**
     * @test
     * @group init
     */
    public function it_should_display_maintenance_page()
    {
        Configuration::set('PS_SHOP_ENABLE', false);
        Configuration::set('PS_MAINTENANCE_IP', '');

        $response = $this->frontController->init();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * @test
     * @group init
     */
    public function it_should_display_restricted_country_page()
    {
        $reflectedFrontController = new ReflectionObject($this->frontController);
        $prop = $reflectedFrontController->getProperty('restrictedCountry');
        $prop->setAccessible(true);
        $prop->setValue($this->frontController, true);

        $response = $this->frontController->init();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * @test
     * @group init
     * @group current
     */
    public function it_should_issue_a_404_when_liveedit_requested_but_the_user_dont_have_access()
    {
        $_GET['live_edit'] = true;

        $response = $this->frontController->init();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
    }
}