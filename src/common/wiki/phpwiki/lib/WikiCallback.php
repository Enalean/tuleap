<?php //-*-php-*-
rcs_id('$Id: WikiCallback.php,v 1.3 2004/11/01 10:43:56 rurban Exp $');

/**
 * A callback
 *
 * This is a virtual class.
 *
 * Subclases of WikiCallback can be used to represent either
 * global function callbacks, or object method callbacks.
 *
 * @see WikiFunctionCb, WikiMethodCb.
 */
class WikiCallback
{
    /**
     * Convert from Pear-style callback specification to a WikiCallback.
     *
     * This is a static member function.
     *
     * @param $pearCb mixed
     * For a global function callback, $pearCb should be a string containing
     * the name of the function.
     * For an object method callback, $pearCb should be a array of length two:
     * the first element should contain (a reference to) the object, the second
     * element should be a string containing the name of the method to be invoked.
     * @return object Returns the appropriate subclass of WikiCallback.
     * @access public
     */
    function callback ($pearCb) {
        if (is_string($pearCb))
            return new WikiFunctionCb($pearCb);
        else if (is_array($pearCb)) {
            list($object, $method) = $handler;
            return new WikiMethodCb($object, $method);
        }
        trigger_error("WikiCallback::new: bad arg", E_USER_ERROR);
    }
    
    /**
     * Call callback.
     *
     * @param ? mixed This method takes a variable number of arguments (zero or more).
     * The callback function is called with the specified arguments.
     * @return mixed The return value of the callback.
     * @access public
     */
    function call () {
        return $this->call_array(func_get_args());
    }

    /**
     * Call callback (with args in array).
     *
     * @param $args array Contains the arguments to be passed to the callback.
     * @return mixed The return value of the callback.
     * @see call_user_func_array.
     * @access public
     */
    function call_array ($args) {
        trigger_error('pure virtual', E_USER_ERROR);
    }

    /**
     * Convert to Pear callback.
     *
     * @return string The name of the callback function.
     *  (This value is suitable for passing as the callback parameter
     *   to a number of different Pear functions and methods.) 
     * @access public
     */
    function toPearCb() {
        trigger_error('pure virtual', E_USER_ERROR);
    }
}

/**
 * Global function callback.
 */
class WikiFunctionCb
    extends WikiCallback
{
    /**
     * Constructor
     *
     * @param $functionName string Name of global function to call.
     * @access public
     */
    function WikiFunctionCb ($functionName) {
        $this->functionName = $functionName;
    }

    function call_array ($args) {
        return call_user_func_array($this->functionName, $args);
    }

    function toPearCb() {
        return $this->functionName;
    }
}

/**
 * Object Method Callback.
 */
class WikiMethodCb
    extends WikiCallback
{
    /**
     * Constructor
     *
     * @param $object object Object on which to invoke method.
     * @param $methodName string Name of method to call.
     * @access public
     */
    function WikiMethodCb(&$object, $methodName) {
        $this->object = &$object;
        $this->methodName = $methodName;
    }

    function call_array ($args) {
        $method = &$this->methodName;
        //$obj = &$this->object;

        // This should work, except PHP's before 4.0.5 (which includes mine)
        // don't have 'call_user_method_array'.
        if (check_php_version(4,0,5)) {
            return call_user_func_array(array(&$this->object, $method), $args);
        }

        // This should work, but doesn't.  At least in my PHP, the object seems
        // to get passed by value, rather than reference, so any changes to the
        // object made by the called method get lost.
        /*
        switch (count($args)) {
        case 0: return call_user_method($method, $obj);
        case 1: return call_user_method($method, $obj, $args[0]);
        case 2: return call_user_method($method, $obj, $args[0], $args[1]);
        case 3: return call_user_method($method, $obj, $args[0], $args[1], $args[2]);
        case 4: return call_user_method($method, $obj, $args[0], $args[1], $args[2], $args[3]);
        default: trigger_error("Too many arguments to method callback", E_USER_ERROR);
        }
        */

        // This seems to work, at least for me (so far):
        switch (count($args)) {
        case 0: return $this->object->$method();
        case 1: return $this->object->$method($args[0]);
        case 2: return $this->object->$method($args[0], $args[1]);
        case 3: return $this->object->$method($args[0], $args[1], $args[2]);
        case 4: return $this->object->$method($args[0], $args[1], $args[2], $args[3]);
        default: trigger_error("Too many arguments to method callback", E_USER_ERROR);
        }
    }

    function toPearCb() {
        return array($this->object, $this->methodName);
    }
}

/**
 * Anonymous function callback.
 */
class WikiAnonymousCb
    extends WikiCallback
{
    /**
     * Constructor
     *
     * @param $args string Argument declarations
     * @param $code string Function body
     * @see create_function().
     * @access public
     */
    function WikiAnonymousCb ($args, $code) {
        $this->function = create_function($args, $code);
    }

    function call_array ($args) {
        return call_user_func_array($this->function, $args);
    }

    function toPearCb() {
        trigger_error("Can't convert WikiAnonymousCb to Pear callback",
                      E_USER_ERROR);
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
