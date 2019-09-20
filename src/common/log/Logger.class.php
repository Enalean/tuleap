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

interface Logger
{
    public const DEBUG = 'debug';
    public const INFO  = 'info';
    public const WARN  = 'warn';
    public const ERROR = 'error';

    function debug($message);

    function info($message);

    /**
     * Logs a warning into the log file.
     *
     * @param String    $message the message to log.
     * @param Exception $e       the exception to log.
     */
    function warn($message, ?Exception $e = null);

    /**
     * Logs a warning into the log file.
     *
     * @param String    $message the message to log.
     * @param Exception $e       the exception to log.
     */
    function error($message, ?Exception $e = null);

    /**
     * @deprecated use explicit methods
     */
    function log($message, $level = null);
}
