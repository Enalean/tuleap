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
     * @access private
     */
    var $isValid;

    /**
     * Constructor
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
     * Return the variable name on which rules must applies.
     *
     * @access private
     */
    function getKey() {
        return $this->key;
    }

    /**
     * Add a new rule in this validation.
     *
     * ou can add a custom error message that will bypass the default one that
     * comes with the rule.
     * @param Rule   Reference on rule.
     * @param String Error message.
     */
    function addRule(&$rule, $message=false) {
        $this->rules[] =& $rule;
        $this->errors[] = $message;
    }

    /**
     * The value is required.
     *
     * All rules must succeed (as usual). Empty / null values are forbidden
     * (raise an error). And all failure generate an error (instead of a
     * warning).
     */
    function required() {
        $this->isRequired = true;
    }

    /**
     * Turn feedback off.
     */
    function disableFeedback() {
        $this->useFeedback = false;
    }

    /**
     * Set a global error message that will replace all other messages.
     *
     * Note: If no error, no message raised. The message is raised with either
     * 'warning' or 'error' level according to required();
     * @param String Error message
     */
    function setErrorMessage($msg) {
        $this->globalErrorMessage = $msg;
    }

    /**
     * Append feebback in the global Response object.
     * @access private
     */
    function addFeedback($level, $error) {
        $GLOBALS['Response']->addFeedback($level, $error);
    }

    /**
     * Generate error message according to settings.
     *
     * Takes in account user requirement 'required' and
     * 'disableFeedback'. Empty error messages are disarded.
     * @access private
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
     * Prepare error message on Rule:isValid result.
     *
     * If the test succeeded, the error message is cleared (either custom or
     * built-in messages).
     * @access private
     * @param Integer Index of the Rule that was applied.
     * @param Boolean Result of the test.
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
     * Apply each rule on the given value and prepare feedback.
     *
     * @access private
     * @param mixed Value to test.
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
     * Run validation on given value.
     *
     * @param mixed Value to test.
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

/**
 * Check that value is a decimal integer greater or equal to zero.
 */
class Valid_UInt
extends Valid {
    function validate($value) {
        $this->addRule(new Rule_Int());
        $this->addRule(new Rule_GreaterOrEqual(0));
        return parent::validate($value);
    }

}

?>
