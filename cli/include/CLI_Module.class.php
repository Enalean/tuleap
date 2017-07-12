<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

class CLI_Module {

    var $name;
    var $description;
    var $params;
    function __construct($name, $description) {
        $this->name         = $name;
        $this->description = $description;
        $this->params       = array();
        $this->actions      = array();
    }
    function getName() {
        return $this->name;
    }
    function getDescription() {
        return $this->description;
    }
    function addAction($action) {
        $this->actions[$action->getName()] = $action;
        $action->setModule($this);
    }
    function getAllActions() {
        return $this->actions;
    }
    function execute($params) {
        $result = null;
        $action_name = array_shift($params);
        if (isset($this->actions[$action_name])) {
            $result = $this->actions[$action_name]->execute($params);
        } else {
            echo $this->help();
        }
        return $result;
    }

    function help() {
        $help = $this->getName() .":\n";
        $help .= $this->getDescription() ."\n\n";
        if (count($this->actions)) {
            $help .= "Available actions:\n";
            foreach($this->actions as $action) {
                $help .= "  * ". $action->getName() ."\n    ". $action->getDescription() ."\n";
            }
            $help .= "\n";
        }
        return $help;
    }
    /**
     * getParameter - Get a specified parameter from the command line.
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * Given an array of parameters passed by the command line, this function
     * searches the specified parameter in that array.
     * For example, if we want the "name" parameter in the following command:
     * $ ./script --name="john" --lastname="doe"
     * this function will return the string "john".
     * There is an option to give aliases for a certain parameter. For example, these
     * commands can be equivalent:
     * $ ./script -n "john" --lastname="doe"
     * $ ./script --name="john" --lastname="doe"
     * $ ./script -n "john" -l "doe"
     * This is done by passing an array to the parameter "name".
     * In the case of "flags", this function returns "true" is the flag is specified,
     * for instance:
     * $ ./script -v
     * $ ./script --verbose
     * This function also detects when several flags are grouped into one, for example:
     * $ ./script -abc
     * instead of
     * $ ./script -a -b -c
     * (this only works with one-character flags)
     * Note that parameter names with more than one character are assumed to be preceded by
     * "--" (like in "--name") parameters with one character are assumed to be preceded by
     * a single "-" (like in "-n")
     *
     * @param array    Array of parameters where we should look
     * @param mixed    A string that specifies the name of the parameter to look for, or an
     *     array of aliases (ej: array("name", "n"))
     * @param bool Indicate if the parameter MUST have a value associated to it, and that it is
     *    not just a flag. This can also be seen as "isn't a flag" value
     */

    function getParameter(&$parameter_array, $parameter, $require_value=false) {
        for ($i=0; $i < count($parameter_array); $i++) {
            $res = array();
            if (preg_match("/^\\-\\-(.+)/s",$parameter_array[$i],$res)) {    // several-character parameter? (IE, "--username=john")
                $passed_string = $res[1];
                // is it --parameter=value or just --parameter?
                $res = preg_split("/=(.+)/", $passed_string, -1, PREG_SPLIT_DELIM_CAPTURE);
                if (isset($res[1])) {
                    $passed_parameter = $res[0];
                    $passed_value = $res[1];
                    $has_value = true;
                } else {
                    $passed_parameter = $passed_string;
                    $has_value = false;
                }

                if (!is_array($parameter)) $search_array = array($parameter);
                else $search_array = $parameter;

                foreach ($search_array as $alias) {
                    if ($alias == $passed_parameter) {        // Match
                        if ($has_value) return $passed_value;
                        else if ($require_value) return null;        // Requires a value but none is passed
                        else return true;        // notify parameter was passed
                    }
                }

            } else if (preg_match("/^\\-(.+)/s",$parameter_array[$i],$res)) {    // Single character parameter? (IE "-z") or a group of flags (IE "-zxvf")
                $passed_parameter = $res[1];
                if (strlen($passed_parameter) == 1) {        // Some flag like "-x" or parameter "-U username"
                    // Check to see if there is a value associated to this parameter, like in "-U username".
                    // To do this, we must see the following string in the parameter array
                    if (($i+1) < count($parameter_array) && !preg_match("/^\\-/", $parameter_array[$i+1])) {
                        $i++;        // position in value
                        $passed_value = $parameter_array[$i];
                        $has_value = true;
                    } else {
                        $has_value = false;
                    }
                } else {        // Several flags grouped into one string like "-zxvf"
                    $has_value = false;
                }

                if (!is_array($parameter)) $search_array = array($parameter);
                else $search_array = $parameter;

                foreach ($search_array as $alias) {
                    if (strlen($alias) == 1) {
                        if (strpos($passed_parameter, $alias) !== false) {    // Found a match
                            if ($has_value) return $passed_value;
                            else if ($require_value) return null;
                            else return true;        // indicates that the flag was set
                        }
                    }
                }
            }
        }

        return null;
    }
    /**
     * get_user_input - Receive input from the user
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * @param string Text to show to the user
     * @param bool Specify if input shouldn't be shown (useful when asking for passwords)
     */
    function get_user_input($text, $hide=false) {
        if ($hide && PHP_OS == 'WINNT') {
            $hide = false;  // disable echo does not work in Windows
        }
        if ($text) echo $text;
        if ($hide) @exec("stty -echo");        // disable echo of the input (only works in UNIX)
        $input = trim(fgets(STDIN));
        if ($hide) {
            @exec("stty echo");
            echo "\n";
        }
        return $input;
    }

}
