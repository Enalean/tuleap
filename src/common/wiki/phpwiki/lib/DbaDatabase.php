<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

require_once('lib/ErrorManager.php');

define('DBA_DATABASE_DEFAULT_TIMEOUT', 5);

class DbaDatabase
{
    public function __construct($filename, $mode = false, $handler = 'gdbm')
    {
        $this->_file = $filename;
        $this->_handler = $handler;
        $this->_timeout = DBA_DATABASE_DEFAULT_TIMEOUT;
        $this->_dbh = false;
        if (function_exists("dba_handlers")) { // since 4.3.0
            if (!in_array($handler, dba_handlers())) {
                $this->_error(
                    sprintf(
                        _("The DBA handler %s is unsupported!") . "\n" .
                            _("Supported handlers are: %s"),
                        $handler,
                        join(",", dba_handlers())
                    )
                );
            }
        }
        if ($mode) {
            $this->open($mode);
        }
    }

    public function set_timeout($timeout)
    {
        $this->_timeout = $timeout;
    }

    public function open($mode = 'w')
    {
        if ($this->_dbh) {
            return;             // already open.
        }

        $watchdog = $this->_timeout;

        global $ErrorManager;
        $this->_dba_open_error = false;
        $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_dba_open_error_handler'));

        // oops, you don't have DBA support.
        if (!function_exists("dba_open")) {
            echo "You don't seem to have DBA support compiled into PHP.";
        }

        if (strlen($mode) == 1) {
            // PHP 4.3.x Windows lock bug workaround: http://bugs.php.net/bug.php?id=23975
            if (isWindows()) {
                $mode .= "-";             // suppress locking, or
            } elseif ($this->_handler != 'gdbm') {     // gdbm does it internally
                $mode .= "d";             // else use internal locking
            }
        }
        while (($dbh = dba_open($this->_file, $mode, $this->_handler)) < 1) {
            if ($watchdog <= 0) {
                break;
            }
            flush();
            // "c" failed, try "w" instead.
            if (substr($mode, 0, 1) == "c" and file_exists($this->_file)) {
                $mode = "w";
            }
            // conflict: wait some random time to unlock (see ethernet)
            $secs = 0.5 + ((double) rand(1, 32767) / 32767);
            sleep($secs);
            $watchdog -= $secs;
            if (strlen($mode) == 2) {
                $mode = substr($mode, 0, -1);
            }
        }
        $ErrorManager->popErrorHandler();

        if (!$dbh) {
            if (($error = $this->_dba_open_error)) {
                $error->errno = E_USER_ERROR;
                $error->errstr .= "\nfile: " . $this->_file
                               .  "\nmode: " . $mode
                               .  "\nhandler: " . $this->_handler;
                $ErrorManager->handleError($error);
            } else {
                trigger_error("dba_open failed", E_USER_ERROR);
            }
        }
        $this->_dbh = $dbh;
        return !empty($dbh);
    }

    public function close()
    {
        if ($this->_dbh) {
            dba_close($this->_dbh);
        }
        $this->_dbh = false;
    }

    public function exists($key)
    {
        return dba_exists($key, $this->_dbh);
    }

    public function fetch($key)
    {
        $val = dba_fetch($key, $this->_dbh);
        if ($val === false) {
            return $this->_error("fetch($key)");
        }
        return $val;
    }

    public function insert($key, $val)
    {
        if (!dba_insert($key, $val, $this->_dbh)) {
            return $this->_error("insert($key)");
        }
    }

    public function replace($key, $val)
    {
        if (!dba_replace($key, $val, $this->_dbh)) {
            return $this->_error("replace($key)");
        }
    }


    public function firstkey()
    {
        return dba_firstkey($this->_dbh);
    }

    public function nextkey()
    {
        return dba_nextkey($this->_dbh);
    }

    public function delete($key)
    {
        if (!dba_delete($key, $this->_dbh)) {
            return $this->_error("delete($key)");
        }
    }

    public function get($key)
    {
        return dba_fetch($key, $this->_dbh);
    }

    public function set($key, $val)
    {
        $dbh = &$this->_dbh;
        if (dba_exists($key, $dbh)) {
            if ($val !== false) {
                if (!dba_replace($key, $val, $dbh)) {
                    return $this->_error("store[replace]($key)");
                }
            } else {
                if (!dba_delete($key, $dbh)) {
                    return $this->_error("store[delete]($key)");
                }
            }
        } else {
            if (!dba_insert($key, $val, $this->_dbh)) {
                return $this->_error("store[insert]($key)");
            }
        }
    }

    public function sync()
    {
        if (!dba_sync($this->_dbh)) {
            return $this->_error("sync()");
        }
    }

    public function optimize()
    {
        if (!dba_optimize($this->_dbh)) {
            return $this->_error("optimize()");
        }
        return 1;
    }

    public function _error($mes)
    {
        trigger_error("DbaDatabase: $mes", E_USER_WARNING);
        return false;

        trigger_error("$this->_file: dba error: $mes", E_USER_ERROR);
    }

    public function _dump()
    {
        $dbh = &$this->_dbh;
        for ($key = $this->firstkey($dbh); $key; $key = $this->nextkey($dbh)) {
            printf("%10s: %s\n", $key, $this->fetch($key));
        }
    }

    public function _dba_open_error_handler($error)
    {
        $this->_dba_open_error = $error;
        return true;
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
