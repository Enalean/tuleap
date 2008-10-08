<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
class ErrorHandler {
    
    function __construct() {
        set_error_handler(array($this, 'onerror'), error_reporting());
    }
    
    function onerror($errno, $errstr='', $errfile='', $errline='')
    {
        
        // if error has been supressed with an @
        if (error_reporting() == 0) {
            return;
        }

        // check if function has been called by an exception
        if(func_num_args() == 5) {
            // called by trigger_error()
            $exception = null;
            list($errno, $errstr, $errfile, $errline) = func_get_args();
    
            $backtrace = debug_backtrace();
    
        } else {
            // caught exception
            $exc = func_get_arg(0);
            $errno   = $exc->getCode();
            $errstr  = $exc->getMessage();
            $errfile = $exc->getFile();
            $errline = $exc->getLine();
    
            $backtrace = array_reverse($exc->getTrace());
        }
        
        $errorType = array (
                   E_ERROR            => 'ERROR',
                   E_WARNING        => 'WARNING',
                   E_PARSE          => 'PARSING ERROR',
                   E_NOTICE         => 'NOTICE',
                   E_CORE_ERROR     => 'CORE ERROR',
                   E_CORE_WARNING   => 'CORE WARNING',
                   E_COMPILE_ERROR  => 'COMPILE ERROR',
                   E_COMPILE_WARNING => 'COMPILE WARNING',
                   E_USER_ERROR     => 'USER ERROR',
                   E_USER_WARNING   => 'USER WARNING',
                   E_USER_NOTICE    => 'USER NOTICE',
                   E_STRICT         => 'STRICT NOTICE'
                   );
        if (defined('E_RECOVERABLE_ERROR')) {
            $errorType['E_RECOVERABLE_ERROR'] = 'RECOVERABLE ERROR';
        }
        
        // create error message
        if (array_key_exists($errno, $errorType)) {
            $err = $errorType[$errno];
        } else {
            $err = 'CAUGHT EXCEPTION';
        }
    
        $errMsg = "$errstr in $errfile on line $errline";
        $trace = '';
        // start backtrace
        foreach ($backtrace as $v) {
            if (isset($v['class'])) {
                $trace .= 'in class '.$v['class'].'->'.$v['function'].'(';
            } else {
                $trace .= 'in function '.$v['function'].'(';
            }
            
            if (isset($v['args']) && !empty($v['args'])) {
                $args = array();
                foreach($v['args'] as $arg ) {
                    $args[] = $this->getArgument($arg);
                }
                $trace .= implode(', ', $args);
            }
            $trace .= ')';
            $trace .= '<div style="padding-left:30px; margin-bottom:0.8em; font-size:0.8em; color:gray;">&nbsp;';
            if (isset($v['file'])) {
                $trace .= $v['file'];
                if (isset($v['line'])) {
                    $trace .= ' @ '.$v['line'];
                }
            }
            $trace .= '</div>';
        }

        // display error msg
        echo '<center><table style="background:white; border:1px solid red;">';
        echo '<tr><th colspan="2">There is an error</th></tr>';
        echo '<tr valign="top"><td><strong>'. $err .'</strong></td><td>'.nl2br($errMsg).'</td></tr>';
        echo '<tr valign="top"><td>Backtrace</td><td>'.nl2br($trace).'</td></tr>';
        echo '</table></center>';
    
        // what to do
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
            case E_PARSE:
            case E_STRICT:
                exit('<p>aborting.</p>');
                break;
    
            default:
                return;
                break;
    
        }
    
    } // end of errorHandler()
    
    function getArgument($arg) {
        switch (strtolower(gettype($arg))) {
    
            case 'string':
                return( '"'.str_replace( array("\n"), array(''), $arg ).'"' );
    
            case 'boolean':
                return (bool)$arg;
    
            case 'object':
                return 'object('.get_class($arg).')';
    
            case 'array':
                $ret = 'array(';
                $separtor = '';
    
                foreach ($arg as $k => $v) {
                    $ret .= $separtor.$this->getArgument($k).' => '.$this->getArgument($v);
                    $separtor = ', ';
                }
                $ret .= ')';
    
                return $ret;
    
            case 'resource':
                return 'resource('.get_resource_type($arg).')';
    
            default:
                return var_export($arg, true);
        }
    }
}
?>
