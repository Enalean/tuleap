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

    public function log($message, $level = 'info') {
        parent::log("[WF] $message", $level);
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
        call_user_func_array(array($this, 'logMethodAndItsArguments'), $arguments);
    }

    /**
     * Logs the end of a method. Used in debug mode.
     *
     * @param string $calling_method
     * @param mixed  ...              Parameters of the calling method
     */
    public function end($calling_method) {
        $arguments = func_get_args();
        array_unshift($arguments, __FUNCTION__);
        call_user_func_array(array($this, 'logMethodAndItsArguments'), $arguments);
    }

    private function logMethodAndItsArguments() {
        $arguments      = func_get_args();
        $method         = ucfirst(array_shift($arguments));
        $calling_method = array_shift($arguments);
        $arguments      = implode(', ', $arguments);
        $this->debug("$method $calling_method($arguments)");
    }
}
