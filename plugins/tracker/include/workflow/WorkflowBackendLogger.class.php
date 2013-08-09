<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * A BackendLogger dedicated to the workflow. It prefix all message by [WF] tag.
 */
class WorkflowBackendLogger extends BackendLogger {

    private static $indentation_prefix    = '';
    private static $indentation_start     = '┌ ';
    private static $indentation_increment = '│ ';
    private static $indentation_end       = '└ ';

    private static function indent() {
        self::$indentation_prefix .= self::$indentation_increment;
    }

    private static function unindent() {
        self::$indentation_prefix = mb_substr(self::$indentation_prefix, 0, -mb_strlen(self::$indentation_increment, 'UTF-8'), 'UTF-8');
    }

    public function log($message, $level = 'info') {
        parent::log('[WF] '. self::$indentation_prefix . $message, $level);
    }

    /**
     * Logs the start of a method. Used in debug mode.
     *
     * @param string $calling_method
     * @param mixed  ...              Parameters of the calling method
     */
    public function start($calling_method) {
        $arguments = func_get_args();
        array_unshift($arguments, __FUNCTION__);
        array_unshift($arguments, self::$indentation_start);
        call_user_func_array(array($this, 'logMethodAndItsArguments'), $arguments);
        self::indent();
    }

    /**
     * Logs the end of a method. Used in debug mode.
     *
     * @param string $calling_method
     * @param mixed  ...              Parameters of the calling method
     */
    public function end($calling_method) {
        self::unindent();
        $arguments = func_get_args();
        array_unshift($arguments, __FUNCTION__);
        array_unshift($arguments, self::$indentation_end);
        call_user_func_array(array($this, 'logMethodAndItsArguments'), $arguments);
    }

    private function logMethodAndItsArguments() {
        $arguments      = func_get_args();
        $prefix         = array_shift($arguments);
        $method         = ucfirst(array_shift($arguments));
        $calling_method = array_shift($arguments);
        $arguments      = implode(', ', $arguments);
        $this->debug("$prefix$method $calling_method($arguments)");
    }
}
