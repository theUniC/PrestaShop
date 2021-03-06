<?php

use Prestashop\Tools;
use Prestashop\Product;
use Prestashop\Validate;
use Prestashop\Context;
use \FavoriteProduct;
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
/**
 * @deprecated 1.5.0 This file is deprecated, use moduleFrontController instead
 */
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
include dirname(__FILE__) . '/FavoriteProduct.php';
if (Tools::getValue('action') && Tools::getValue('id_product') && Context::getContext()->cookie->id_customer) {
    if (Tools::getValue('action') == 'remove') {
        // check if product exists
        $product = new Product((int) Tools::getValue('id_product'));
        if (!Validate::isLoadedObject($product)) {
            die('0');
        }
        $favorite_product = FavoriteProduct::getFavoriteProduct((int) Context::getContext()->cookie->id_customer, (int) $product->id);
        if ($favorite_product) {
            if ($favorite_product->delete()) {
                die('0');
            }
        }
    } elseif (Tools::getValue('action') == 'add') {
        $product = new Product((int) Tools::getValue('id_product'));
        // check if product exists
        if (!Validate::isLoadedObject($product) || FavoriteProduct::isCustomerFavoriteProduct((int) Context::getContext()->cookie->id_customer, (int) $product->id)) {
            die('1');
        }
        $favorite_product = new FavoriteProduct();
        $favorite_product->id_product = $product->id;
        $favorite_product->id_customer = (int) Context::getContext()->cookie->id_customer;
        $favorite_product->id_shop = (int) Context::getContext()->shop->id;
        if ($favorite_product->add()) {
            die('0');
        }
    }
}
die('1');