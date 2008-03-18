<?php

class Feedback {
    var $logs;
    function Feedback() {
        $this->logs = array();
    }
    function log($level, $msg, $purify=CODEX_PURIFIER_CONVERT_HTML) {
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
        $hp =& CodeX_HTMLPurifier::instance();
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
            $found = ($log['level'] == 'warning' || $log['level'] == 'error');
       }
       return $found;
    }
}

?>
