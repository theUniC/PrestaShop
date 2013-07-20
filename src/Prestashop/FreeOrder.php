<?php

namespace Prestashop;

/**
 * Class FreeOrder to use PaymentModule (abstract class, cannot be instancied)
 */
class FreeOrder extends PaymentModule
{
    public $active = 1;
    public $name = 'free_order';
    public $displayName = 'free_order';
}