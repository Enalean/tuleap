<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/log/Logger.class.php';

class BackendLogger implements Logger{
    /**
     * Log message in codendi_syslog
     *
     * @param string $message The error message that should be logged.
     * @param string $level   The level of the message "info", "warn", ...
     * 
     * @return boolean true on success or false on failure
     */
    public function log($message, $level = 'info') {
        return error_log(date('c')." [$level] $message\n", 3, $GLOBALS['codendi_log']."/codendi_syslog");
    }

    public function debug($message) {
        $this->log($message, 'debug');
    }

    public function info($message) {
        $this->log($message, 'info');
    }
    
    public function error($message, Exception $e = null) {
        $this->log($message, 'error');
    }

    public function fatal($message, Exception $e = null) {
        $this->log($message, 'fatal');
    }

    public function warn($message, Exception $e = null) {
        $this->log($message, 'warning');
    }

}

?>
