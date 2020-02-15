<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

class Valid
{
    /**
     * @access private
     */
    public $errors;

    /**
     * @access private
     */
    public $key;

    /**
     * @access private
     */
    public $rules;

    /**
     * @access private
     */
    public $isRequired;

    /**
     * @access private
     */
    public $useFeedback;

    /**
     * @access private
     */
    public $globalErrorMessage;

    /**
     * @access private
     */
    public $isValid;

    /**
     * Constructor
     */
    public function __construct($key = null)
    {
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
    public function getKey()
    {
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
    public function addRule($rule, $message = false)
    {
        $this->rules[]  = $rule;
        $this->errors[] = $message;
    }

    /**
     * The value is required.
     *
     * All rules must succeed (as usual). Empty / null values are forbidden
     * (raise an error). And all failure generate an error (instead of a
     * warning).
     */
    public function required()
    {
        $this->isRequired = true;
    }

    /**
     * Turn feedback off.
     */
    public function disableFeedback()
    {
        $this->useFeedback = false;
    }

    /**
     * Set a global error message that will replace all other messages.
     *
     * Note: If no error, no message raised. The message is raised with either
     * 'warning' or 'error' level according to required();
     * @param String Error message
     */
    public function setErrorMessage($msg)
    {
        $this->globalErrorMessage = $msg;
    }

    /**
     * Return true if given value is empty
     *
     * @access private
     * @param mixed Value to test
     * @return bool
     */
    public function isValueEmpty($value)
    {
        return ($value === '' || $value === false || $value === null);
    }

    /**
     * Append feebback in the global Response object.
     * @access private
     */
    public function addFeedback($level, $error)
    {
        $GLOBALS['Response']->addFeedback($level, $error);
    }

    /**
     * Generate error message according to settings.
     *
     * Takes in account user requirement 'required' and
     * 'disableFeedback'. Empty error messages are disarded.
     * @access private
     */
    public function populateFeedback()
    {
        if ($this->useFeedback) {
            $level = 'warning';
            if ($this->isRequired) {
                $level = 'error';
            }
            if ($this->globalErrorMessage !== null &&
               !$this->isValid) {
                $this->addFeedback($level, $this->globalErrorMessage);
            } else {
                foreach ($this->errors as $error) {
                    if ($error != '') {
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
    public function errorMessage($i, $result)
    {
        if ($result === true) {
            $this->errors[$i] = '';
        } else {
            if ($this->errors[$i] === false) {
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
    public function checkEachRules($value)
    {
        $isValid = true;
        $rCtr = count($this->rules);
        for ($i = 0; $i < $rCtr; $i++) {
            $valid = $this->rules[$i]->isValid($value);
            $this->errorMessage($i, $valid);
            $isValid = $isValid && $valid;
        }
        if ($isValid && $this->isRequired && $this->isValueEmpty($value)) {
            $this->isValid = false;
        } else {
            $this->isValid = $isValid;
        }
        $this->populateFeedback();
    }

    /**
     * Run validation on given value.
     *
     * @param mixed Value to test.
     */
    public function validate($value)
    {
        if ($this->isRequired
           || (!$this->isRequired && !$this->isValueEmpty($value))) {
            $this->checkEachRules($value);
            return $this->isValid;
        }
        return true;
    }
}
