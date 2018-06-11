<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01contact
 */
namespace P01C;

class Session
{
    private static $key = 'p01contact';

    private static function init()
    {
        if (session_id() === '') {
            session_start();
        }
        if (!self::exists()) {
            $_SESSION[self::$key] = [];
        }
    }
    /**
     * Return if the session data exists, or if the given key exists.$_COOKIE
     *
     * @param string $key
     * @return bool
     */
    public static function exists($key = null)
    {
        $sessionExist = !empty($_SESSION) && !empty($_SESSION[self::$key]);
        if ($key === null) {
            return $sessionExist;
        }
        return $sessionExist && isset($_SESSION[self::$key][$key]);
    }
    /**
     * Set the given key to the given value.
     *
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public static function set($key, $val)
    {
        if (!self::exists()) {
            self::init();
        }
        $_SESSION[self::$key][$key] = $val;
    }
    /**
     * Get the given key data.
     *
     * @param string $key
     * @param mixed $default (optional) Value to return if the key doesn't exist.
     * @return mixed `$default` or `null`
     */
    public static function get($key, $default = null)
    {
        if (!self::exists($key)) {
            return $default;
        }
        return $_SESSION[self::$key][$key];
    }
    /**
     * Add value to the array named key and shift old
     * entries until the array is of given size.
     *
     * @param string $key
     * @param mixed $val
     * @param integer $size
     * @return void
     */
    public static function stack($key, $val, $size = 2)
    {
        if (!self::exists()) {
            self::init();
        }
        $arr = self::get($key);
        if (!isset($arr)) {
            $arr = [];
        }
        if (!is_array($arr)) {
            return;
        }
        array_push($arr, $val);
        while (count($arr) > $size) {
            array_shift($arr);
        }
        self::set($key, $arr);
    }
    /**
     * Return the session data, in html.
     *
     * @return string
     */
    public static function report()
    {
        if (!self::exists()) {
            return;
        }
        $out = '<h3>$_SESSION :</h3>';
        $out.= preint($_SESSION, true);
        return $out;
    }
}
