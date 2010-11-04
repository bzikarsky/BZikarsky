<?php

namespace BZikarsky\Lang;

/**
 * This class provides an enum-like type (as in Java) for PHP
 *
 * To create an enum, the enum has to extends this Enum class and defined
 * a static protected array $enum with its entites.
 * This array can be either a normal collection, e.g.
 *
 * <code>
 *     protected static $enum = array('FOO', 'BAR');
 * </code>
 *
 * or a hash, defining optional arguments for Enum::init(), which can be
 * overwritten, e.g.:
 *
 * <code>
 *     protected static $enum = array(
 *         'MALE'   => array('male', 'm', 'Dear Mr.',),
 *         'FEMALE' => array('female', 'f', 'Dear Ms.')
 *     );
 *
 *     protected function init($value, $short, $address)
 *     {
 *         // ...
 *     }
 * </code>
 *
 * ATTENTION: If you use the numeric-array-form, the array is converted
 *            automatically to the hash-form as soon the first enum instance
 *            is created
 *
 * Best practises:
 *  - Create enums always as final, extending a specific enum instance will
 *    lead to confusion
 *  - Always refer to enum types with all uppercase letters
 *
 *
 * @author    Benjamin Zikarsky <benjamin@zikarsky.de>
 * @copyright Copyright (c) benjamin Zikarsky
 * @license   http://opensource.org/licenses/bsd-license.php NewBSD
 * @example   http://gist.github.com/638426
 */
abstract class Enum
{
    /**
     * collection all enum instances
     *
     * @var array
     */
    private static $instances = array();
    
    /**
     * enum's subtype
     *
     * @var string
     */
    private $type = null;
    
    /**
     * class constructor
     *
     * @param string $type enum subtype
     * @param array  $args initialization arguments
     */
    private final function __construct($type, array $args)
    {
        $this->type = $type;
        
        // check if there is a init 
        if (method_exists($this, 'init')) {
            call_user_func_array(array($this, 'init'), $args);
        }
    }
    
    /**
     * Catches static calls to provide an easy interface for enum creation, e.g.
     *    $fooEnum = EnumFoo::BAR();
     *
     * @param string $name
     * @param array $args (unused)
     * @return Enum
     */
    public final static function __callStatic($name, $args)
    {
        return self::get($name);
    }
    
    /**
     * Catches calls to undefined methods to provide an easy interface for
     * enum type checks, e.g.:
     *    $enumFoo->isBar()
     *
     * @param string $name
     * @param array $args (unused)
     * @return boolean;
     */
    public final function  __call($name, $args)
    {
        $matches = array();
        if (preg_match('#^is([a-z][a-z0-9]+)$#i', $name, $matches)) {
            return $this->is($matches[1]);
        }
        
        throw new \RuntimeException(sprintf(
            "Undefined method %s::%s", get_called_class(), $name
        ));
    }
    
    /**
     * Provides a default implementation for string casts
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("%s::%s", get_called_class(), $this->type);
    }
    
    /**
     * Get enum instance and create it, if not yet existent
     *
     * @param string $name
     * @return Enum
     */
    public final static function get($name)
    {
        $name = strtoupper($name);
        $enum = get_called_class();
        
        // check if this is the first instance of this enum type
        if (!array_key_exists($enum, self::$instances)) {
            // create instance storage
            self::$instances[$enum] = array();
            
            // check if there is an enum array
            if (!is_array(static::$enum)) {
                throw new \LogicException("\$enum has to be an array");
            }
            
            // check for non-associative array, e.g.:
            //    self:$enum = array('FOO', 'BAR')
            // and transform to verbose form:
            //    self::$enum = array('FOO' => array(), 'BAR' => array()))
            if (!count(array_diff_key(static::$enum, array_keys(array_keys(static::$enum))))) {
                static::$enum = array_fill_keys(static::$enum, array());
            }
        }
        
        // check for valid enum (after the transformation form static::$enum)
        if (!array_key_exists($name, static::$enum)) {
            throw new \RuntimeException(sprintf(
                "Type error (%s does not exist in %s", $name, get_called_class()
            ));
        }
        
        // create new instances
        if (!array_key_exists($name, self::$instances[$enum])) {
            self::$instances[$enum][$name] = new $enum($name, static::$enum[$name]);
        }
        
        return self::$instances[$enum][$name];
    }
    
    /**
     * Check if enum is equal in type (and enum)
     *
     * @param string|Enum $name
     * @return boolean
     */
    public final function is($name)
    {
        if ($name instanceof self) {
            return $name === $this;
        }
        
        $name = strtoupper($name);
        return $name == $this->type;
    }
}
