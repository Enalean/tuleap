<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

class Feedback
{

    /**
     * @var array
     */
    public $logs;

    /**
     * @var FeedbackFormatter
     */
    private $formatter;

    public const INFO =  'info';
    public const WARN  = 'warning';
    public const ERROR = 'error';
    public const DEBUG = 'debug';

    public function __construct()
    {
        $this->logs = array();
        $this->setFormatter(new FeedbackFormatter());
    }

    public function setFormatter(FeedbackFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function log($level, $msg, $purify = CODENDI_PURIFIER_CONVERT_HTML)
    {
        if (!is_array($msg)) {
            $msg = array($msg);
        }
        foreach ($msg as $m) {
            $this->logs[] = array('level' => $level, 'msg' => $m, 'purify' => $purify);
        }
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    public function fetch()
    {
        return $this->formatter->format($this->logs);
    }

    public function fetchAsPlainText()
    {
           $txt = '';
        foreach ($this->logs as $log) {
            $txt .= $log['level'] . ': ' . $log['msg'] . "\n";
        }
        return $txt;
    }

    /**
     * @return array of error messages
     */
    public function fetchErrors()
    {
        $errors = array();
        foreach ($this->logs as $log) {
            if ($log['level'] == self::ERROR) {
                $errors[] = $log['msg'];
            }
        }

        return $errors;
    }

    public function display()
    {
        echo $this->htmlContent();
    }

    public function htmlContent()
    {
        return '<div id="feedback" data-test="feedback">' . $this->fetch() . '</div>';
    }

    public function hasWarningsOrErrors()
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === self::WARN || $log['level'] === self::ERROR) {
                return true;
            }
        }
        return false;
    }

    public function hasErrors()
    {
        foreach ($this->logs as $log) {
            if ($log['level'] === self::ERROR) {
                return true;
            }
        }
        return false;
    }

    public function clearErrors()
    {
        foreach ($this->logs as $key => $log) {
            if ($log['level'] == self::ERROR) {
                unset($this->logs[$key]);
            }
        }
    }
}
