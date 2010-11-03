<?php

define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
require_once BASE_PATH .'/vendor/symfony/src/Symfony/Component/HttpFoundation/UniversalClassLoader.php';


$loader = new \Symfony\Component\HttpFoundation\UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'   => BASE_PATH . '/vendor/symfony/src',
    'Zend'      => BASE_PATH . '/vendor/library',
    'BZikarsky' => BASE_PATH . '/library'
));

$loader->register();
