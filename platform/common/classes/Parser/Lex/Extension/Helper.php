<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2015-2016
 * @license The MIT License, http://opensource.org/licenses/MIT
 */

class Parser_Lex_Extension_Helper extends Parser_Lex_Extension {

    public static $_counter_increment = true;

    public function __construct() {

        parent::__construct();
    }

    public function __call($name, $args) {

        if (function_exists($name) && $this->_is_function_allowed($name)) {

            $attributes = $this->get_attributes();

            return call_user_func_array($name, $attributes);
        }

        return $this->_function_not_found($name);
    }

    protected function _set_type(& $value, $type) {

        // "boolean" (or, since PHP 4.2.0, "bool")
        // "integer" (or, since PHP 4.2.0, "int")
        // "float" (only possible since PHP 4.2.0, for older versions use the deprecated variant "double")
        // "string"
        // "array"
        // "object"
        // "null" (since PHP 4.2.0)
        $type = isset($type) ? (trim(strtolower(@ (string) $type))) : null;

        if ($type === null) {
            return true;
        }

        switch ($type) {

            case 'bool':

                $type = 'boolean';
                break;

            case 'int':

                $type = 'integer';
                break;

            case 'double':

                $type = 'float';
                break;
        }

        return @ settype($value, $type);
    }

    protected function _set_display(& $value, $mode) {

        // "print_d"
        // "print_r"
        // "var_dump"
        // "var_export"
        $mode = isset($mode) ? (strtolower(@ (string) $mode)) : null;

        switch ($mode) {

            case 'print_d':

                $value = print_d($value, true);
                break;

            case 'print_r':

                $value = print_r($value, true);
                break;

            case 'var_dump':

                ob_start();
                var_dump($value);
                $result = ob_get_clean();
                $value = $result;
                break;

            case 'var_export':

                $value = var_export($value, true);
                break;
        }
    }

    protected function _type($type) {

        $attributes = $this->get_attribute_values();

        $value = isset($attributes[0]) ? $attributes[0] : null;

        $this->_set_type($value, $type);

        return $value;
    }

    protected function _display($display) {

        $attributes = $this->get_attribute_values();

        $value = isset($attributes[0]) ? $attributes[0] : null;

        $this->_set_display($value, $display);

        return $value;
    }

    protected function _utf8($function, $bool_detect_list = array()) {

        if (!$this->_is_function_allowed($function, $message)) {
            return $message;
        }

        $this->detect_boolean_attributes($bool_detect_list);
        $attributes = $this->get_attributes();

        return IS_UTF8_CHARSET
            ? call_user_func_array(array('UTF8', $function), $attributes)
            : call_user_func_array($function, $attributes);
    }

    //--------------------------------------------------------------------------

    public function add() {

        $name = $this->get_attribute(0);
        $value = $this->get_attribute(1, 0);

        if (!isset($name)) {
            return;
        }

        $name = trim(@ (string) $name);

        if ($name == '') {
            return;
        }

        $var = & $this->parser_instance->getVariableRef(
            $name,
            $this->parser_instance->parser_data
        );

        $var = $var + $value;
    }

    public function _func_array() {

        return $this->_type('array');
    }

    public function bool() {

        return $this->_type('boolean');
    }

    public function boolean() {

        return $this->_type('boolean');
    }

    public function character_limiter() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('text');

        return call_user_func_array($name, $this->get_attributes());
    }

    public function config() {

        $item = $this->get_attribute(0);

        if (!isset($item)) {
            return;
        }

        $item = trim(@ (string) $item);

        if ($item == '') {
            return;
        }

        if ($this->parser->is_blacklisted_config_setting($item)) {
            return;
        }

        return config_item($item);
    }

    public function count() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->detect_boolean_attributes(array(1));
        $attributes = $this->get_attribute_values();

        if (isset($attributes[1])) {
            $attributes[1] = $attributes[1] ? 1 : 0;
        }

        return call_user_func_array($name, $attributes);
    }

    public function counter() {

        static $count = array();

        $key = $this->get_attribute('identifier', 'default');

        if (!isset($count[$key])) {
            $count[$key] = $this->get_attribute('start', 1);
        } elseif (self::$_counter_increment) {
            ($this->get_attribute('mode') == 'subtract') ? $count[$key]-- : $count[$key]++;
        }

        self::$_counter_increment = true;

        return (str_to_bool($this->get_attribute('return', true))) ? $count[$key] : null;
    }

    public function show_counter() {

        self::$_counter_increment = false;

        return self::counter();
    }

    public function double() {

        return $this->_type('float');
    }

    public function _func_empty() {

        if (!$this->_is_function_allowed('empty', $message)) {
            return $message;
        }

        $attributes = $this->get_attribute_values();

        return empty($attributes[0]);
    }

    public function float() {

        return $this->_type('float');
    }

    public function get() {

        $attributes = $this->get_attribute_values();

        $name = isset($attributes[0]) ? (trim(@ (string) $attributes[0])) : null;
        $value = null;

        if ($name != '') {

            $no_value = new Parser_Lex_No_Value;

            $var = $this->parser_instance->getVariable(
                $name,
                $this->parser_instance->parser_data,
                $no_value
            );

            if ($var !== $no_value) {
                $value = $var;
            }
        }

        return $value;
    }

    public function gmap_url() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('url');

        $this->detect_boolean_attributes(array(3));
        $attributes = $this->get_attribute_values();

        return call_user_func_array($name, $attributes);
    }

    public function gravatar() {

        $this->load->library('gravatar');

        $this->detect_boolean_attributes(array('url-only'));

        $email = $this->get_attribute('email', '');
        $size = $this->get_attribute('size', '50');
        $rating = $this->get_attribute('rating', 'g');
        $url_only = $this->get_attribute('url-only', false);

        $gravatar_url = $this->gravatar->get($email, $size, null, null, $rating);

        if ($url_only) {
            return $gravatar_url;
        }

        return '<img src="'.$gravatar_url.'" alt="Gravatar" class="gravatar" />';
    }

    public function highlight_phrase() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('text');

        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function humanize() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('inflector');

        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function int() {

        return $this->_type('integer');
    }

    public function integer() {

        return $this->_type('integer');
    }

    public function _func_isset() {

        if (!$this->_is_function_allowed('isset', $message)) {
            return $message;
        }

        $attributes = $this->get_attributes();

        if (!empty($attributes)) {

            foreach ($attributes as $value) {

                if (!isset($value)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function lang() {

        $attributes = $this->get_attribute_values();

        if (count($attributes) < 1) {
            return null;
        }

        $line = $attributes[0];

        if (count($attributes) == 1) {
            return $this->lang->line($line);
        }

        $params = array_slice($attributes, 1);

        return $this->lang->line($line, $params);
    }

    public function ltrim() {

        return $this->_utf8(__FUNCTION__);
    }

    public function thumbnail() {

        $this->load->helper('thumbnail');

        $this->detect_boolean_attributes(array(3, 4, 5));
        $attributes = $this->get_attribute_values();

        $src = (isset($attributes[0]) && $attributes[0] != '') ? $attributes[0] : null;
        $width = (isset($attributes[1]) && $attributes[1] != '') ? $attributes[1] : null;
        $height = (isset($attributes[2]) && $attributes[2] != '') ? $attributes[2] : null;
        $no_crop = isset($attributes[3]) ? $attributes[3] : null;
        $keep_canvas_size = isset($attributes[4]) ? $attributes[4] : null;
        $dynamic_output = isset($attributes[5]) ? $attributes[5] : null;

        return thumbnail($src, $width, $height, $no_crop, $keep_canvas_size, $dynamic_output);
    }

    public function _func_null() {

        return $this->_type('null');
    }

    public function object() {

        return $this->_type('object');
    }

    public function preg_match() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $attributes = $this->get_attribute_values();

        if (count($attributes) >= 3) {

            $matches_attr = trim(@ (string) $attributes[2]);

            if (is_str_to_bool($matches_attr)) {

                $matches = array();
                $attributes[2] = & $matches;

            } else {

                $attributes[2] = & $this->parser_instance->getVariableRef(
                    $matches_attr,
                    $this->parser_instance->parser_data
                );
                $attributes[2] = array();
            }
        }

        return call_user_func_array($name, $attributes);
    }

    public function preg_match_all() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $attributes = $this->get_attribute_values();

        if (count($attributes) >= 3) {

            $matches_attr = trim(@ (string) $attributes[2]);

            if (is_str_to_bool($matches_attr)) {

                $matches = array();
                $attributes[2] = & $matches;

            } else {

                $attributes[2] = & $this->parser_instance->getVariableRef(
                    $matches_attr,
                    $this->parser_instance->parser_data
                );
                $attributes[2] = array();
            }
        }

        return call_user_func_array($name, $attributes);
    }

    public function preg_replace() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $attributes = $this->get_attribute_values();

        if (count($attributes) >= 5) {

            $count_attr = trim(@ (string) $attributes[4]);

            if (is_str_to_bool($count_attr)) {

                $count = 0;
                $attributes[4] = & $count;

            } else {

                $attributes[4] = & $this->parser_instance->getVariableRef(
                    $count_attr,
                    $this->parser_instance->parser_data
                );
                $attributes[4] = 0;
            }
        }

        return call_user_func_array($name, $attributes);
    }

    public function print_d() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        return $this->_display($name);
    }

    public function print_r() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        return $this->_display($name);
    }

    public function rand_string() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('string');

        $attributes = $this->get_attribute_values();

        $length = isset($attributes[0]) ? $attributes[0] : 10;

        return rand_string($length);
    }

    public function rtrim() {

        return $this->_utf8(__FUNCTION__);
    }

    public function safe_mailto() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('url');

        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function set() {

        $attributes = $this->get_attribute_values();

        if (count($attributes) < 1) {
            return;
        }

        $name = trim(@ (string) $attributes[0]);

        if ($name == '') {
            return;
        }

        $value = isset($attributes[1]) ? $attributes[1] : null;
        $type = isset($attributes[2]) ? ($attributes[2]) : null;

        $success = $this->_set_type($value, $type);

        $this->parser_instance->setVariable(
            $name,
            $value,
            $this->parser_instance->parser_data
        );
    }

    public function slugify() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('url');

        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function str_replace() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $attributes = $this->get_attributes();

        if (count($attributes) >= 4) {

            $i = 0;

            foreach ($attributes as $key => $count_attr) {

                if ($i == 3) {
                    break;
                }

                $i++;
            }

            if (is_str_to_bool($count_attr)) {

                $count = 0;
                $attributes[$key] = & $count;

            } else {

                $attributes[$key] = & $this->parser_instance->getVariableRef(
                    $count_attr,
                    $this->parser_instance->parser_data
                );
                $attributes[$key] = 0;
            }
        }

        return call_user_func_array($name, $attributes);
    }

    public function string() {

        return $this->_type('string');
    }

    public function stripos() {

        return $this->_utf8(__FUNCTION__);
    }

    public function strlen() {

        return $this->_utf8(__FUNCTION__);
    }

    public function strpos() {

        return $this->_utf8(__FUNCTION__);
    }

    public function strtolower() {

        return $this->_utf8(__FUNCTION__);
    }

    public function strtoupper() {

        return $this->_utf8(__FUNCTION__);
    }

    public function sub() {

        $name = $this->get_attribute(0);
        $value = $this->get_attribute(1, 0);

        if (!isset($name)) {
            return;
        }

        $name = trim(@ (string) $name);

        if ($name == '') {
            return;
        }

        $var = & $this->parser_instance->getVariableRef(
            $name,
            $this->parser_instance->parser_data
        );

        $var = $var - $value;
    }

    public function substr() {

        return $this->_utf8(__FUNCTION__);
    }

    public function timespan() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('date');

        $timestamp = date($this->get_attribute(0, now()));

        return timespan($timestamp, time());
    }

    public function trim() {

        return $this->_utf8(__FUNCTION__);
    }

    public function var_export() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        return $this->_display($name);
    }

    public function var_dump() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        return $this->_display($name);
    }

    public function ucfirst() {

        return $this->_utf8(__FUNCTION__);
    }

    public function ucwords() {

        return $this->_utf8(__FUNCTION__);
    }

    public function url_title() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('url');

        $this->detect_boolean_attributes(array(2, 3));
        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function xss_clean() {

        return $this->security->xss_clean($this->get_attribute(0));
    }

    public function word_limiter() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('text');

        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function word_wrap() {

        if (!$this->_is_function_allowed($name = __FUNCTION__, $message)) {
            return $message;
        }

        $this->load->helper('text');

        $attributes = $this->get_attributes();

        return call_user_func_array($name, $attributes);
    }

    public function wordwrap() {

        return $this->_utf8(__FUNCTION__, array(3));
    }

}
