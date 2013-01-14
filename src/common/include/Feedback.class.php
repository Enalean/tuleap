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

class Feedback {
    const INFO =  'info';
    const WARN  = 'warning';
    const ERROR = 'error';

    var $logs;
    function Feedback() {
        $this->logs = array();
    }
    function log($level, $msg, $purify=CODENDI_PURIFIER_CONVERT_HTML) {
        if(!is_array($msg)) {
            $msg = array($msg);
        }
        foreach($msg as $m) {
            $this->logs[] = array('level' => $level, 'msg' => $m, 'purify' => $purify);
        }
    }
    function fetch() {
        $html = '';
        $old_level = null;
        $hp =& Codendi_HTMLPurifier::instance();
        foreach($this->logs as $log) {
            if (!is_null($old_level) && $old_level != $log['level']) {
                $html .= '</ul>';
            }
            if (is_null($old_level) || $old_level != $log['level']) {
                $old_level = $log['level'];
                $html .= '<ul class="feedback_'. $log['level'] .'">';
            }
            $html .= '<li>'. $hp->purify($log['msg'], $log['purify']) .'</li>';
        }
        if (!is_null($old_level)) {
            $html .= '</ul>';
        }
        return $html;
    }
    function fetchAsPlainText() {
    	   $txt = '';
       foreach($this->logs as $log) {
       	   $txt .= $log['level'] .': '. $log['msg'] ."\n"; 
       }
       return $txt;
    }
    
    function display() {
        echo '<div id="feedback">'.$this->fetch().'</div>';
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
