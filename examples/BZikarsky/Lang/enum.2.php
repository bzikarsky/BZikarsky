<?php

require_once dirname(__FILE__) . "/../../autoload.php";

use BZikarsky\Lang\Enum;

class Gender extends Enum
{
    protected static $enum = array(
        'MALE' => array('male', 'm'),
        'FEMALE' => array('female', 'f')
    );


    protected $adjective = '';
    protected $short = '';

    protected function init($value, $short)
    {
        $this->value = $value;
        $this->short = $short;
    }
    
    
    public function getShort()
    {
        return $this->short;
    }
    
    public function getValue()
    {
        return $this->value;
    }
}


$gender  = Gender::MALE();

printf("%s (%s)\n", $gender->getValue(), $gender->getShort());