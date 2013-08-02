<?php

use Prestashop\Configuration;
if (!defined('_PS_VERSION_')) {
    die;
}
function upgrade_module_0_9($object)
{
    Configuration::updateValue('BLOCKSPECIALS_NB_CACHES', 20);
    return $object->registerHook('addproduct') && $object->registerHook('updateproduct') && $object->registerHook('deleteproduct');
}