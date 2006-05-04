<?php
rcs_id('$Id: Tools.php 2691 2006-03-02 15:31:51Z guerin $');
/*
 Copyright 2002 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


/**
 * Class for Configuration properties
 * 
 * Class provides the functions to get and set 
 * valid values for configuration properties.
 * @author Joby Walker<zorloc@imperium.org>
 */
class ConfigValue {
    
    /** 
    * Name of the Value.
    * @var string
    * @access protected
    */
    var $name;
    /** 
    * The current value.
    * @var mixed
    * @access protected
    */
    var $currentValue;
    /** 
    * The default value.
    * @var mixed
    * @access protected
    */
    var $defaultValue;
    /** 
    * Array with a short and full description.
    * @var array
    * @access protected
    */
    var $description;
    /** 
    * Validator object to validate a new value.
    * @var object
    * @access protected
    */
    var $validator;
    
    /**
    * Constructor
    * 
    * Initializes instance variables from parameter array.
    * @param array $params Array with properties of the config value.
    */
    function ConfigValue($params){
        $this->name = $params['name'];
        $this->section = $params['section'];
        $this->defaultValue = $params['defaultValue'];
        $this->description = $params['description'];
        $this->validator = &$params['validator'];
        $this->currentValue = $this->getStarting();
    }
    
    /**
    * Static method to get the proper subclass.
    * 
    * @param array $params Config Values properties.
    * @return object A subclass of ConfigValue.
    * @static
    */
    function getConfig($params){
        if (isset($params['validator'])) {
            $params['validator'] = &Validator::getValidator($params['validator']);
        }
        return new ConfigValue($params);
    }

    /**
    * Determines if the value is valid.
    * 
    * If the parameter is a valid value for this config value returns
    * true, false else.
    * @param mixed $value Value to be checked for validity.
    * @return boolean True if valid, false else.
    */
    function valid($value){
        if ($this->validator->validate($value)) {
            return true;
        }
        trigger_error("Value for \'" . $this->name . "\' is invalid.",
                      E_USER_WARNING);
        return false;
    }

    /**
    * Determines the value currently being used.
    * 
    * Just returns the default value.
    * @return mixed The currently used value (the default).
    */
    function getStarting(){
        return $this->defaultValue;
    }
    
    /**
    * Get the currently selected value.
    * 
    * @return mixed The currently selected value.
    */
    function getCurrent(){
        return $this->currentValue;
    }

    /**
    * Set the current value to this.
    * 
    * Checks to see if the parameter is a valid value, if so it
    * sets the parameter to currentValue.
    * @param mixed $value The value to set.
    */    
    function setCurrent($value){
        if ($this->valid($value)) {
            $this->currentValue = $value;
        }
    }
    
    /**
    * Get the Name of the Value
    * @return mixed Name of the value.
    */
    function getName(){
        return $this->name;
    }
    
    /**
    * Get the default value of the Value
    * @return mixed Default value of the value.
    */
    function getDefaultValue(){
        return $this->defaultValue;
    }
    
    /**
    * Get the Short Description of the Value
    * @return mixed Short Description of the value.
    */
    function getShortDescription(){
        return $this->description['short'];
    }

    /**
    * Get the Full Description of the Value
    * @return mixed Full Description of the value.
    */
    function getFullDescription(){
        return $this->description['full'];
    }
}




/**
* Abstract base Validator Class
* @author Joby Walker<zorloc@imperium.org>
*/
class Validator {

    /**
    * Constructor
    * 
    * Dummy constructor that does nothing.
    */
    function Validator(){
        return;
    }

    /**
    * Dummy valitate method -- always returns true.
    * @param mixed $value Value to check.
    * @return boolean Always returns true.
    */
    function validate($value){
        return true;
    }
    
    /**
    * Get the proper Valitator subclass for the provided parameters
    * @param array $params Initialization values for Validator.
    * @return object Validator subclass for use with the parameters.
    * @static
    */
    function getValidator($params){
        extract($params, EXTR_OVERWRITE);
        $class = 'Validator' . $type;
        if (isset($list)){
            $class .= 'List';
            return new $class ($list);
        } elseif (isset($range)) {
            $class .= 'Range';
            return new $class ($range);
        } elseif (isset($pcre)){
            $class .= 'Pcre';
            return new $class ($pcre);
        }
        return new $class ();
    
    }

}

/**
* Validator subclass for use with boolean values
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorBoolean extends Validator {

    /**
    * Checks the parameter to see if it is a boolean, returns true if
    * it is, else false.
    * @param boolean $boolean Value to check to ensure it is a boolean.
    * @return boolean True if parameter is boolean.
    */
    function validate ($boolean){
        if (is_bool($boolean)) {
            return true;
        }
        return false;
    }
}

/**
* Validator subclass for use with integer values with no bounds.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorInteger extends Validator {

    /**
    * Checks the parameter to ensure that it is an integer.
    * @param integer $integer Value to check.
    * @return boolean True if parameter is an integer, false else.
    */
    function validate ($integer){
        if (is_int($integer)) {
            return true;
        }
        return false;
    }
}

/**
* Validator subclass for use with integer values to be bound within a range.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorIntegerRange extends ValidatorInteger {

    /** 
    * Minimum valid value
    * @var integer
    * @access protected
    */
    var $minimum;
    
    /** 
    * Maximum valid value
    * @var integer
    * @access protected
    */
    var $maximum;

    /**
    * Constructor
    * 
    * Sets the minimum and maximum values from the parameter array.
    * @param array $range Minimum and maximum valid values.
    */
    function ValidatorIntegerRange($range){
        $this->minimum = $range['minimum'];
        $this->maximum = $range['maximum'];
        return;
    }
    
    /**
    * Checks to ensure that the parameter is an integer and within the desired 
    * range.
    * @param integer $integer Value to check. 
    * @return boolean True if the parameter is an integer and within the 
    * desired range, false else.
    */
    function validate ($integer){
        if (is_int($integer)) {
            if (($integer >= $this->minimum) && ($integer <= $this->maximum)) {
                return true;
            }
        }
        return false;
    }

}

/**
* Validator subclass for use with integer values to be selected from a list.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorIntegerList extends ValidatorInteger {

    /** 
    * Array of potential valid values
    * @var array
    * @access protected
    */
    var $intList;
    
    /**
    * Constructor
    * 
    * Saves parameter as the instance variable $intList.
    * @param array List of valid values.
    */
    function ValidatorIntegerList($intList){
        $this->intList = $intList;
        return;
    }

    /**
    * Checks the parameter to ensure that it is an integer, and 
    * within the defined list.
    * @param integer $integer Value to check.
    * @return boolean True if parameter is an integer and in list, false else.
    */
    function validate ($integer){
        if (is_int($integer)) {
            if (in_array($integer, $this->intList, true)) {
                return true;
            }
        }
        return false;
    }

}

/**
* Validator subclass for string values with no bounds
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorString extends Validator {

    /**
    * Checks the parameter to ensure that is is a string.
    * @param string $string Value to check.
    * @return boolean True if parameter is a string, false else.
    */
    function validate ($string){
        if (is_string($string)) {
            return true;
        }
        return false;
    }

}

/**
* Validator subclass for string values to be selected from a list.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorStringList extends ValidatorString {

    /** 
    * Array of potential valid values
    * @var array
    * @access protected
    */
    var $stringList;
    
    /**
    * Constructor
    * 
    * Saves parameter as the instance variable $stringList.
    * @param array List of valid values.
    */
    function ValidatorStringList($stringList){
        $this->stringList = $stringList;
        return;
    }

    /**
    * Checks the parameter to ensure that is is a string, and within 
    * the defined list.
    * @param string $string Value to check.
    * @return boolean True if parameter is a string and in the list, false else.
    */
    function validate($string){
        if (is_string($string)) {
            if (in_array($string, $this->stringList, true)) {
                return true;
            }
        }
        return false;
    }

}

/**
* Validator subclass for string values that must meet a PCRE.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorStringPcre extends ValidatorString {

    /** 
    * PCRE to validate value
    * @var array
    * @access protected
    */
    var $pattern;

    /**
    * Constructor
    * 
    * Saves parameter as the instance variable $pattern.
    * @param array PCRE pattern to determin validity.
    */
    function ValidatorStringPcre($pattern){
        $this->pattern = $pattern;
        return;
    }

    /**
    * Checks the parameter to ensure that is is a string, and matches the 
    * defined pattern.
    * @param string $string Value to check.
    * @return boolean True if parameter is a string and matches the pattern,
    * false else.
    */
    function validate ($string){
        if (is_string($string)) {
            if (preg_match($this->pattern, $string)) {
                return true;
            }
        }
        return false;
    }
}

/**
* Validator subclass for constant values.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorConstant extends Validator {

    /**
    * Checks the parameter to ensure that is is a constant.
    * @param string $constant Value to check.
    * @return boolean True if parameter is a constant, false else.
    */
    function validate ($constant){
        if (defined($constant)) {
            return true;
        }
        return false;
    }
}

/**
* Validator subclass for constant values to be selected from a list.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorConstantList extends Validator {

    /** 
    * Array of potential valid values
    * @var array
    * @access protected
    */
    var $constantList;

    /**
    * Constructor
    * 
    * Saves parameter as the instance variable $constantList.
    * @param array List of valid values.
    */
    function ValidatorConstantList($constantList){
        $this->constantList = $constantList;
        return;
    }

    /**
    * Checks the parameter to ensure that is is a constant, and within 
    * the defined list.
    * @param string $constant Value to check.
    * @return boolean True if parameter is a constant and in the list, false else.
    */
    function validate ($constant){
        if (defined($constant)) {
            if (in_array($constant, $this->constantList, true)) {
                return true;
            }
        }
        return false;
    }
}

/**
* Validator subclass for an array.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorArray extends Validator {

    /*
    * Checks to ensure that the parameter is an array then passes the
    * array on to validMembers() to ensure that each member of the 
    * array is valid.
    * @param array $array Value to check.
    * @return boolean True if the value is and array and members are valid, false else.
    */
    function validate($array){
        if(is_array($array)){
            return $this->validMembers($array);
        }
        return false;
    }
    
    /**
    * Checks to ensure that the members of the array are valid.  Always true here.
    * @param array $array Array of members to check
    * @return boolean Always true since there are no restrictions on the members.
    */
    function validMembers($array){
        return true;
    }
}

/**
* Validator subclass for an array of strings.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorArrayString extends Validator {

    /**
    * Checks to ensure that the members of the array are valid strings.
    * @param array $array Array of members to check
    * @return boolean True if the members are valid strings, false else.
    */
    function validMembers($array){
        foreach ($array as $member){
            if (!is_string($member)) {
                return false;
            }
        }
        return true;
    }
}

/**
* Validator subclass for an array of strings that must be in a list of 
* defined values.
* @author Joby Walker<zorloc@imperium.org>
*/
class ValidatorArrayStringList extends Validator {

    /** 
    * Array of potential valid values
    * @var array
    * @access protected
    */
    var $stringList;

    /**
    * Constructor
    * 
    * Saves parameter as the instance variable $stringList.
    * @param array List of valid values.
    */
    function ValidatorArrayStringList($stringList){
        $this->stringList = $stringList;
        return;
    }

    /**
    * Checks to ensure that the members of the array are valid strings and 
    * within the defined list.
    * @param array $array Array of members to check
    * @return boolean True if the members are valid strings are in the defined list, 
    * false else.
    */
    function validMembers($array){
        foreach ($array as $member){
            if(!in_array($member, $stringList, true)){
                return false;
            }
        }
        return true;
    }

}



//$Log$
//Revision 1.6  2004/04/16 23:30:41  zorloc
//More work for new ini config system.  Tools has array type removed and first implimentations of config-dist.ini and IniConfig.php.  Will update config-dist.ini again soon.
//
//Revision 1.5  2003/12/07 19:25:41  carstenklapp
//Code Housecleaning: fixed syntax errors. (php -l *.php)
//
//Revision 1.3  2003/01/28 18:53:25  zorloc
//Added some more Validator subclasses to handle arrays of for which the
//validation criteria should be the same for all members.
//
//Revision 1.2  2003/01/28 06:31:00  zorloc
//Mostly done but ConfigArray will probably need some more helper methods.
//
//Revision 1.1  2003/01/23 00:32:04  zorloc
//Initial work for classes to hold configuration constants/variables. Base
//ConfigValue class and subclasses for constants and variables.
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>