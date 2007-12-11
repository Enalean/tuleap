<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package CodeX
 */

require_once('common/valid/Rule.class.php');

/**
 * @package CodeX
 */
class Valid {
    /**
     * @access private
     */
    var $errors;

    /**
     * @access private
     */
    var $key;

    /**
     * @access private
     */
    var $rules;

    /**
     * @access private
     */
    var $isRequired;

    /**
     * @access private
     */
    var $useFeedback;

    /**
     * @access private
     */
    var $globalErrorMessage;

    /**
     *
     */
    var $isValid;

    /**
     *
     */
    function Valid($key = null) {
        $this->key = $key;
        $this->errors = array();
        $this->rules = array();
        $this->isRequired = false;
        $this->useFeedback = true;
        $this->globalErrorMessage = null;
        $this->isValid;
    }

    /**
     *
     */
    function getKey() {
        return $this->key;
    }

    /**
     *
     */
    function addRuleRef(&$r, $message=false) {
        $this->rules[] =& $r;
        $this->errors[] = $message;
    }

    /**
     *
     */
    function addRule($r, $message=false) {
        $this->addRuleRef($r, $message);
    }

    /**
     *
     */
    function required() {
        $this->isRequired = true;
    }

    /**
     *
     */
    function disableFeedback() {
        $this->useFeedback = false;
    }

    /**
     *
     */
    function setErrorMessage($msg) {
        $this->globalErrorMessage = $msg;
    }

    /**
     *
     */
    function addFeedback($level, $error) {
        $GLOBALS['Response']->addFeedback($level, $error);
    }

    /**
     *
     */
    function populateFeedback() {
        if($this->useFeedback) {
            $level = 'warning';
            if($this->isRequired) {
                $level = 'error';
            }
            if($this->globalErrorMessage !== null &&
               !$this->isValid) {
                $this->addFeedback($level, $this->globalErrorMessage);
            } else {
                foreach($this->errors as $error) {
                    if($error != '') {
                        $this->addFeedback($level, $error);
                    }
                }
            }
        }
    }

    /**
     *
     */
    function errorMessage($i, $result) {
        if($result === true) {
            $this->errors[$i] = '';
        } else {
            if($this->errors[$i] === false) {
                $this->errors[$i] = $this->rules[$i]->getErrorMessage($this->key);
            }
        }
    }

    /**
     *
     */
    function checkEachRules($value) {
        $isValid = true;
        $rCtr = count($this->rules);
        for($i = 0; $i < $rCtr; $i++) {
            $valid = $this->rules[$i]->isValid($value);
            $this->errorMessage($i, $valid);
            $isValid = $isValid && $valid;
        }
        $this->isValid = $isValid;
        $this->populateFeedback();
    }

    /**
     *
     */
    function validate($value) {
        if($this->isRequired
           || (!$this->isRequired && $value != '' && $value !== false && $value !== null)) {
            $this->checkEachRules($value);
            return $this->isValid;
        }
        return true;
    }
}


?>
