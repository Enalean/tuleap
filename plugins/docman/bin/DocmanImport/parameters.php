<?php
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

function getParameter(&$parameter_array, $parameter, $require_value = false)
{
    for ($i = 0; $i < count($parameter_array); $i++) {
        $res = array();
        if (preg_match("/^\\-\\-(.+)/s", $parameter_array[$i], $res)) {    // several-character parameter? (IE, "--username=john")
            $passed_string = $res[1];
            // is it --parameter=value or just --parameter?
            if (preg_match("/(.+)=(.+)/s", $passed_string, $res)) {
                $passed_parameter = $res[1];
                $passed_value = $res[2];
                $has_value = true;
            } else {
                $passed_parameter = $passed_string;
                $has_value = false;
            }

            if (!is_array($parameter)) {
                $search_array = array($parameter);
            } else {
                $search_array = $parameter;
            }

            foreach ($search_array as $alias) {
                if ($alias == $passed_parameter) {        // Match
                    if ($has_value) {
                        return $passed_value;
                    } elseif ($require_value) {
                        return null;        // Requires a value but none is passed
                    } else {
                        return true;        // notify parameter was passed
                    }
                }
            }
        } elseif (preg_match("/^\\-(.+)/s", $parameter_array[$i], $res)) {    // Single character parameter? (IE "-z") or a group of flags (IE "-zxvf")
            $passed_parameter = $res[1];
            if (strlen($passed_parameter) == 1) {        // Some flag like "-x" or parameter "-U username"
                // Check to see if there is a value associated to this parameter, like in "-U username".
                // To do this, we must see the following string in the parameter array
                if (($i + 1) < count($parameter_array) && !preg_match("/^\\-/", $parameter_array[$i + 1])) {
                    $i++;        // position in value
                    $passed_value = $parameter_array[$i];
                    $has_value = true;
                } else {
                    $has_value = false;
                }
            } else {        // Several flags grouped into one string like "-zxvf"
                $has_value = false;
            }

            if (!is_array($parameter)) {
                $search_array = array($parameter);
            } else {
                $search_array = $parameter;
            }

            foreach ($search_array as $alias) {
                if (strlen($alias) == 1) {
                    if (strpos($passed_parameter, $alias) !== false) {    // Found a match
                        if ($has_value) {
                            return $passed_value;
                        } elseif ($require_value) {
                            return null;
                        } else {
                            return true;        // indicates that the flag was set
                        }
                    }
                }
            }
        }
    }

    return null;
}
