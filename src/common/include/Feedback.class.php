<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'FeedbackFormatter.class.php';

class Feedback {

    /**
     * @var array
     */
    public $logs;

    /**
     * @var FeebackFormatter
     */
    private $formatter;

    const INFO =  'info';
    const WARN  = 'warning';
    const ERROR = 'error';
    const DEBUG = 'debug';

    function __construct() {
        $this->logs = array();
        $this->setFormatter(new FeedbackFormatter());
    }

    function setFormatter(FeedbackFormatter $formatter) {
        $this->formatter = $formatter;
    }

    function log($level, $msg, $purify=CODENDI_PURIFIER_CONVERT_HTML) {
        if(!is_array($msg)) {
            $msg = array($msg);
        }
        foreach($msg as $m) {
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

    function fetch() {
        return $this->formatter->format($this->logs);
    }

    function fetchAsPlainText() {
    	   $txt = '';
       foreach($this->logs as $log) {
       	   $txt .= $log['level'] .': '. $log['msg'] ."\n"; 
       }
       return $txt;
    }

    /**
     * @return array of error messages
     */
    function fetchErrors() {
        $errors = array();
        foreach ($this->logs as $log) {
            if ($log['level'] == self::ERROR) {
                $errors[] = $log['msg'];
            }
        }

        return $errors;
    }
    
    function display() {
        echo $this->htmlContent();
    }

    public function htmlContent() {
        return '<div id="feedback">'.$this->fetch().'</div>';
    }

    function hasWarningsOrErrors() {
    	   $found = false;
       reset($this->logs);
       while(!$found && list(,$log) = each($this->logs)) {
            $found = ($log['level'] == self::WARN || $log['level'] == self::ERROR);
       }
       return $found;
    }
    
    function hasErrors() {
       $found = false;
       reset($this->logs);
       while(!$found && list(,$log) = each($this->logs)) {
            $found = ($log['level'] == self::ERROR);
       }
       return $found;
    }
}

?>
