<?php

require_once dirname(__FILE__) . "/../../autoload.php";

use BZikarsky\Lang\Enum;


class Month extends Enum
{
    protected static $enum = array(
        'JANUARY',
        'FEBRUARY',
        'MARCH',
        'APRIL',
        'MAY',
        'JUNE',
        'JULY',
        'AUGUST',
        'SEPTEMBER',
        'OCTOBER',
        'NOVEMBER',
        'DECEMBER'
    );
}


$month = Month::get('JANUARY');

echo $month, "\n";
assert($month->isJanuary());
assert($month->is('JANUARY'));
assert($month->is(Month::JANUARY()));
assert(false == $month->isMarch());
assert(false == $month->is('March'));
