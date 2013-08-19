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
class WorkflowBackendLogger implements Logger {

    /** @var string */
    const WF_PREFIX = '[WF] ';

    /** @var string */
    const INDENTATION_START     = '┌ ';

    /** @var string */
    const INDENTATION_INCREMENT = '│ ';

    /** @var string */
    const INDENTATION_END       = '└ ';

    /** @var string */
    private $indentation_prefix    = '';

    /** @var BackendLogger */
    private $backend_logger;

    /** @var string */
    private $fingerprint = '';

    public function __construct(BackendLogger $backend_logger) {
        $this->backend_logger = $backend_logger;
    }

    /** @see Logger::debug() */
    public function debug($message) {
        $this->log($message, Feedback::DEBUG);
    }

    /** @see Logger::info() */
    public function info($message) {
        $this->log($message, Feedback::INFO);
    }

    /** @see Logger::error() */
    public function error($message, Exception $e = null) {
        $this->log($this->backend_logger->generateLogWithException($message, $e), Feedback::ERROR);
    }

    /** @see Logger::warn() */
    public function warn($message, Exception $e = null) {
        $this->log($this->backend_logger->generateLogWithException($message, $e), Feedback::WARN);

    }

    /** @see Logger::log() */
    public function log($message, $level = Feedback::INFO) {
        $prefix  = self::WF_PREFIX;
        if ($this->fingerprint) {
            $prefix .= "[{$this->fingerprint}] ";
        }
        $prefix .= $this->indentation_prefix;
        $this->backend_logger->log($prefix . $message, $level);
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
        array_unshift($arguments, self::INDENTATION_START);
        call_user_func_array(array($this, 'logMethodAndItsArguments'), $arguments);
        $this->indent();
    }

    /**
     * Logs the end of a method. Used in debug mode.
     *
     * @param string $calling_method
     * @param mixed  ...              Parameters of the calling method
     */
    public function end($calling_method) {
        $this->unindent();
        $arguments = func_get_args();
        array_unshift($arguments, __FUNCTION__);
        array_unshift($arguments, self::INDENTATION_END);
        call_user_func_array(array($this, 'logMethodAndItsArguments'), $arguments);
    }

    /**
     * Define the fingerprint of the logger.
     *
     * At the name implies, once defined, a fingerprint cannot be changed.
     *
     * @param string $the_fingerprint
     */
    public function defineFingerprint($fingerprint) {
        if (! $this->fingerprint) {
            $this->fingerprint = $fingerprint;
        }
    }

    private function logMethodAndItsArguments() {
        $arguments      = func_get_args();
        $prefix         = array_shift($arguments);
        $method         = ucfirst(array_shift($arguments));
        $calling_method = array_shift($arguments);
        $arguments      = implode(', ', $arguments);
        $this->debug("$prefix$method $calling_method($arguments)");
    }

    private function indent() {
        $this->indentation_prefix .= self::INDENTATION_INCREMENT;
    }

    private function unindent() {
        $this->indentation_prefix = mb_substr(
            $this->indentation_prefix,
            0,
            - mb_strlen(self::INDENTATION_INCREMENT, 'UTF-8'),
            'UTF-8'
        );
    }
}
