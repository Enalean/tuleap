<?php
/**
  * Copyright (c) Sogilis, 2016. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
  */

/**
 * Extends SimpleErrorTrappingInvoker to enable trapping PHP errors and log
 * stack trace doing so.
 */
class TuleapErrorTrappingInvoker extends SimpleErrorTrappingInvoker {

    /**
     *    Invokes a test method and dispatches any
     *    untrapped errors. Called back from
     *    the visiting runner.
     *    @param string $method    Test method to call.
     *    @access public
     */
    public function invoke($method) {
        $queue = &$this->_createErrorQueue();
        set_error_handler('TuleapTestErrorHandler', (E_ALL | E_STRICT) & ~E_RECOVERABLE_ERROR);
        SimpleInvokerDecorator::invoke($method);
        restore_error_handler();
        $queue->tally();
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
function TuleapTestErrorHandler($severity, $message, $filename = null, $line = null, $super_globals = null, $mask = null) {
    $severity = $severity & error_reporting();
    if ($severity) {
        restore_error_handler();
        if (ini_get('log_errors')) {
            $label = SimpleErrorQueue::getSeverityAsString($severity);
            error_log("Tuleap $label: $message in $filename on line $line");
            foreach(debug_backtrace() as $i => $frame) {
                if ($i == 0) continue;
                error_log(sprintf("%3d. %s%s%s() %s:%d",
                    $i,
                    isset($frame['class']) ? $frame['class'] : '',
                    isset($frame['type']) ? $frame['type'] : '',
                    $frame['function'], $frame['file'], $frame['line']));
            }
        }
        $context = &SimpleTest::getContext();
        $queue = &$context->get('SimpleErrorQueue');
        $queue->add($severity, $message, $filename, $line);
        set_error_handler('TuleapTestErrorHandler');
    }
    return true;
}

