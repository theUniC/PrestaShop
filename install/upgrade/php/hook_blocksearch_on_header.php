<?php

/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
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
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
function hook_blocksearch_on_header()
{
    use Prestashop\Db\Db;
    if ($id_module = Db::getInstance()->getValue('SELECT `id_module` FROM `' . _DB_PREFIX_ . 'module` WHERE `name` = \'blocksearch\'')) {
        $id_hook = Db::getInstance()->getValue('
			SELECT `id_hook`
			FROM `' . _DB_PREFIX_ . 'hook`
			WHERE `name` = \'header\'
		');
        $position = Db::getInstance()->getValue('
			SELECT MAX(`position`)
			FROM `' . _DB_PREFIX_ . 'hook_module`
			WHERE `id_hook` = ' . (int) $id_hook . '
		');
        Db::getInstance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'hook_module` (`id_module`, `id_hook`, `position`) 
			VALUES (' . (int) $id_module . ', ' . (int) $id_hook . ', ' . ($position + 1) . ')
		');
    }
}