<?php

namespace Prestashop\Controller\Admin;

use Prestashop\Controller\AdminController;
use Prestashop\Shop\Shop;
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
class AdminContactsController extends AdminController
{
    public function __construct()
    {
        $this->table = 'contact';
        $this->className = 'Contact';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'), 'confirm' => $this->l('Delete selected items?')));
        $this->fields_list = array('id_contact' => array('title' => $this->l('ID'), 'align' => 'center', 'width' => 25), 'name' => array('title' => $this->l('Title'), 'width' => 130), 'email' => array('title' => $this->l('Email address'), 'width' => 130), 'description' => array('title' => $this->l('Description'), 'width' => 150));
        parent::__construct();
    }
    public function renderForm()
    {
        $this->fields_form = array('legend' => array('title' => $this->l('Contacts'), 'image' => '../img/admin/contact.gif'), 'input' => array(array('type' => 'text', 'label' => $this->l('Title'), 'name' => 'name', 'size' => 33, 'required' => true, 'lang' => true, 'desc' => $this->l('Contact name (e.g. Customer Support)')), array('type' => 'text', 'label' => $this->l('Email address'), 'name' => 'email', 'size' => 33, 'required' => false, 'desc' => $this->l('Emails will be sent to this address')), array('type' => 'radio', 'label' => $this->l('Save messages?'), 'name' => 'customer_service', 'required' => false, 'class' => 't', 'is_bool' => true, 'desc' => $this->l('If enabled, all messages will be saved in the "Customer Service" page under the "Customer" menu.'), 'values' => array(array('id' => 'customer_service_on', 'value' => 1, 'label' => $this->l('Enabled')), array('id' => 'customer_service_off', 'value' => 0, 'label' => $this->l('Disabled')))), array('type' => 'textarea', 'label' => $this->l('Description'), 'name' => 'description', 'required' => false, 'lang' => true, 'cols' => 36, 'rows' => 5, 'desc' => $this->l('Further information regarding this contact'))), 'submit' => array('title' => $this->l('Save   '), 'class' => 'button'));
        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = array('type' => 'shop', 'label' => $this->l('Shop association:'), 'name' => 'checkBoxShopAsso');
        }

        return parent::renderForm();
    }
}