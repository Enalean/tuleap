<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id: errors.php,v 1.14 2006/02/06 06:05:18 lastcraft Exp $
     */

    /** @ignore - PHP5 compatibility fix. */
    if (! defined('E_STRICT')) {
        define('E_STRICT', 2048);
    }

    /**#@+
     * Includes SimpleTest files.
     */
    require_once(dirname(__FILE__) . '/invoker.php');

    /**
     *    Extension that traps errors into an error queue.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleErrorTrappingInvoker extends SimpleInvokerDecorator {

        /**
         *    Stores the invoker to wrap.
         *    @param SimpleInvoker $invoker  Test method runner.
         */
        function SimpleErrorTrappingInvoker(&$invoker) {
            $this->SimpleInvokerDecorator($invoker);
        }

        /**
         *    Invokes a test method and dispatches any
         *    untrapped errors. Called back from
         *    the visiting runner.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function invoke($method) {
            set_error_handler('simpleTestErrorHandler');
            parent::invoke($method);
            $queue = &SimpleErrorQueue::instance();
            while (list($severity, $message, $file, $line, $globals) = $queue->extract()) {
                $severity = SimpleErrorQueue::getSeverityAsString($severity);
                $test_case = &$this->getTestCase();
                $test_case->error($severity, $message, $file, $line);
            }
            restore_error_handler();
        }
    }

    /**
     *    Singleton error queue used to record trapped
     *    errors.
	 *	  @package	SimpleTest
	 *	  @subpackage	UnitTester
     */
    class SimpleErrorQueue {
        var $_queue;

        /**
         *    Starts with an empty queue.
         *    @access public
         */
        function SimpleErrorQueue() {
            $this->clear();
        }

        /**
         *    Adds an error to the front of the queue.
         *    @param $severity        PHP error code.
         *    @param $message         Text of error.
         *    @param $filename        File error occoured in.
         *    @param $line            Line number of error.
         *    @param $super_globals   Hash of PHP super global arrays.
         *    @access public
         */
        function add($severity, $message, $filename, $line, $super_globals) {
            array_push(
                    $this->_queue,
                    array($severity, $message, $filename, $line, $super_globals));
        }

        /**
         *    Pulls the earliest error from the queue.
         *    @return     False if none, or a list of error
         *                information. Elements are: severity
         *                as the PHP error code, the error message,
         *                the file with the error, the line number
         *                and a list of PHP super global arrays.
         *    @access public
         */
        function extract() {
            if (count($this->_queue)) {
                return array_shift($this->_queue);
            }
            return false;
        }

        /**
         *    Discards the contents of the error queue.
         *    @access public
         */
        function clear() {
            $this->_queue = array();
        }

        /**
         *    Tests to see if the queue is empty.
         *    @return        True if empty.
         */
        function isEmpty() {
            return (count($this->_queue) == 0);
        }

        /**
         *    Global access to a single error queue.
         *    @return        Global error queue object.
         *    @access public
         *    @static
         */
        function &instance() {
            static $queue = false;
            if (! $queue) {
                $queue = new SimpleErrorQueue();
            }
            return $queue;
        }

        /**
         *    Converst an error code into it's string
         *    representation.
         *    @param $severity  PHP integer error code.
         *    @return           String version of error code.
         *    @access public
         *    @static
         */
        function getSeverityAsString($severity) {
            static $map = array(
                    E_STRICT => 'E_STRICT',
                    E_ERROR => 'E_ERROR',
                    E_WARNING => 'E_WARNING',
                    E_PARSE => 'E_PARSE',
                    E_NOTICE => 'E_NOTICE',
                    E_CORE_ERROR => 'E_CORE_ERROR',
                    E_CORE_WARNING => 'E_CORE_WARNING',
                    E_COMPILE_ERROR => 'E_COMPILE_ERROR',
                    E_COMPILE_WARNING => 'E_COMPILE_WARNING',
                    E_USER_ERROR => 'E_USER_ERROR',
                    E_USER_WARNING => 'E_USER_WARNING',
                    E_USER_NOTICE => 'E_USER_NOTICE');
            return $map[$severity];
        }
    }

    /**
     *    Error handler that simply stashes any errors into the global
     *    error queue. Simulates the existing behaviour with respect to
     *    logging errors, but this feature may be removed in future.
     *    @param $severity        PHP error code.
     *    @param $message         Text of error.
     *    @param $filename        File error occoured in.
     *    @param $line            Line number of error.
     *    @param $super_globals   Hash of PHP super global arrays.
     *    @static
     *    @access public
     */
    function simpleTestErrorHandler($severity, $message, $filename, $line, $super_globals) {
        if ($severity = $severity & error_reporting()) {
            restore_error_handler();
            if (ini_get('log_errors')) {
                $label = SimpleErrorQueue::getSeverityAsString($severity);
                error_log("$label: $message in $filename on line $line");
            }
            $queue = &SimpleErrorQueue::instance();
            $queue->add($severity, $message, $filename, $line, $super_globals);
            set_error_handler('simpleTestErrorHandler');
        }
    }
?>