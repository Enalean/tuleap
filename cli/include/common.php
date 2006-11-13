<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 */

/**
 * common.php - Common functions
 */

/**
 * exit_error - Exits the program displaying an error and returning an error code
 */
function exit_error($msg, $errcode=1) {
    echo "Fatal error: ".$msg."\n";
    exit (intval($errcode));
}

/**
 * get_parameter - Get a specified parameter from the command line.
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

function get_parameter(&$parameter_array, $parameter, $require_value=false) {
    for ($i=0; $i < count($parameter_array); $i++) {
        $res = array();
        if (preg_match("/^\\-\\-(.+)/",$parameter_array[$i],$res)) {    // several-character parameter? (IE, "--username=john")
            $passed_string = $res[1];
            // is it --parameter=value or just --parameter?
            if (preg_match("/(.+)=(.+)/", $passed_string, $res)) {
                $passed_parameter = $res[1];
                $passed_value = $res[2];
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
            
        } else if (preg_match("/^\\-(.+)/",$parameter_array[$i],$res)) {    // Single character parameter? (IE "-z") or a group of flags (IE "-zxvf")
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
 * get_group_id - Given a group UNIX name, returns the group ID or 0 if the group doesn't exists
 *
 * @param    string    UNIX name of the project
 */
function get_group_id($unix_group_name) {
    static $cached_res = array();
    global $SOAP;
    
    if (array_key_exists($unix_group_name, $cached_res)) {
        return $cached_res[$unix_group_name];
    }
    
    $res = $SOAP->call("getGroupByName", array("unix_group_name" => $unix_group_name));
    
    if (($error = $SOAP->getError()) || !is_array($res) || count($res) == 0) {        // An error here means that no group was found
        $group_id = 0;
    } else {
        $group_id = $res["group_id"];
    }
    
    $cached_res[$unix_group_name] = $group_id;
    return $group_id;
}


/**
 * get_working_group - Return the ID of the group the user is currently working with. The name of the group can be defined
 * on the session or in the parameters.
 *
 * @param array An array of parameters to look for the defined group. If the group isn't in the parameters, looks in the session
 * @param bool Specify if we should abort the program if the group isn't found
 */
function get_working_group(&$params, $die=true) {
    global $SOAP;

    $group_name = get_parameter($params, "project", true);
    if ($group_name) {
        $group_id = get_group_id($group_name);
        if (!$group_id) {
            if ($die) exit_error("Invalid project \"".$group_name."\"");
            else return false;
        }
    } else {
        $group_id = $SOAP->getSessionGroupID();
        if (!$group_id) {
            if ($die) exit_error("You must specify a project using the --project=parameter");
            else return false;
        }
    }

    return $group_id;
}

/**
 * get_user_input - Receive input from the user
 *
 * @param string Text to show to the user
 * @param bool Specify if input shouldn't be shown (useful when asking for passwords)
 */
function get_user_input($text, $hide=false) {
    if ($text) echo $text;
    if ($hide) @exec("stty -echo");        // disable echo of the input (only works in UNIX)
    $input = trim(fgets(STDIN));
    if ($hide) {
        @exec("stty echo");
        echo "\n";
    }
    return $input;
}

/**
 * check_date - Check if a date entered by the user is correctly formatted and it is valid.
 * @param    string    Date
 * @return    string    String with the error (if any)
 */
function check_date($date) {
    $pieces = array();
    if (!preg_match("/^([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})\$/", $date, $pieces)) {
        return "Must be in format YYYY-MM-DD";
    }
    
    $year = intval($pieces[1]);
    $month = intval($pieces[2]);
    $day = intval($pieces[3]);
    
    if (!checkdate($month, $day, $year)) {
        return "Is not a valid date";
    }
    
    return "";
}

/**
 * convert_date - Convert a date entered by the user in format YYYY-MM-DD to a timestamp
 * to be inserted in the database.
 * 
 * This function assumes the date has the correct format
 * @param    string    Date
 * @return    int
 */
function convert_date($date) {
    $pieces = array();
    preg_match("/^([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})\$/", $date, $pieces);
    
    $year = intval($pieces[1]);
    $month = intval($pieces[2]);
    $day = intval($pieces[3]);
    
    return mktime(0, 0, 0, $month, $day, $year);
}

/**
 * show_output - Format a SOAP result in order to display it on the user's screen
 *
 * @param    array    Result of a SOAP call
 * @param    array    Titles to assign to each field (optional).
 */
function show_output($result, $fieldnames = array()) {
    // There are 3 types of output: a scalar (int, string), a vector or a matrix (table-like).
    // Try to guess which output is the best for $result
    if (is_array($result)) {
        if (count($result) == 0) {
            echo "No results\n";
            return;
        }
        
        if (isset($result[0]) && is_array($result[0])) {
            show_matrix($result, $fieldnames);
        } else {
            show_vector($result, $fieldnames);
        }
    } else {
        show_scalar($result, $fieldnames);
    }
}

function show_scalar($result, $fieldnames = array()) {
    $title = (isset($fieldnames[0])) ? $fieldnames[0] : "Result";
    // convert to string (may be an int)
    $result = "$result";
    $length = max(strlen($result), strlen($title));
    $length = $length + 2;        // +2 for having spaces at the beginning and the end

    // show the title
    echo "+".str_repeat("-", $length)."+\n";
    echo "|".center_text($title, $length)."|\n";
    echo "+".str_repeat("-", $length)."+\n";
    
    // show the item
    echo "| ".$result.str_repeat(" ", $length-strlen($result)-1)."|\n";
    echo "+".str_repeat("-", $length)."+\n";
}

function show_vector($result, $fieldnames = array()) {
    // There are two types of vector: those that are a set of items and those
    // that are just like a row in the database. For the second case, reuse the
    // show_matrix function considering the vector as a 1-row matrix
    if (!isset($result[0])) {
        // This happens when $result is an indexed array (i.e. $result["fieldname"]. In this
        // case consider $result as a 1 row matrix
        $foo_matrix = array();
        $foo_matrix[] = $result;
        show_matrix($foo_matrix, $fieldnames);
        return;
    }
    
    $title = (isset($fieldnames[0])) ? $fieldnames[0] : "Result";
    
    $length = strlen($title);
    // get the maximum length for a single item
    foreach ($result as $item) {
        if (is_array($item)) continue;        // shouldn't happen
        $length = max(strlen($item), $length);
    }
    $length = $length + 2;        // +2 for having spaces at the beginning and the end
    
    // show the title
    echo "+".str_repeat("-", $length)."+\n";
    echo "|".center_text($title, $length)."|\n";
    echo "+".str_repeat("-", $length)."+\n";
    
    // show each item
    foreach ($result as $item) {
        echo "| ".$item.str_repeat(" ", $length-strlen($item)-1)."|\n";
    }
    
    // show last line
    echo "+".str_repeat("-", $length)."+\n";
}

function show_matrix($result, $fieldnames = array()) {
    $titles = array();
    $lengths = array();
    
    // this is for showing multidimensional arrays
    static $recursive_id = 1;
    
    foreach ($result as $row) {
        foreach ($row as $colname => $value) {
            if (!isset($titles[$colname])) {
                if (!isset($fieldnames[$colname])) {
                    $titles[$colname] = $colname;
                } else {
                    $titles[$colname] = $fieldnames[$colname];
                }
            }
            
            if (!is_array($value)) {
                if (!isset($lengths[$colname]) || $lengths[$colname] < strlen($value)+2) {
                    $lengths[$colname] = max(strlen($value), strlen($titles[$colname]));
                    $lengths[$colname] += 2;
                }
            } else {
                $lengths[$colname] = strlen($titles[$colname]) + 2;
            }
        }
    }

    // show the titles
    foreach ($titles as $colname => $title) {
        $length = $lengths[$colname];
        echo "+".str_repeat("-", $length);
    }
    echo "+\n";
    foreach ($titles as $colname => $title) {
        $length = $lengths[$colname];
        echo "|".center_text($title, $length);
    }
    echo "|\n";
    foreach ($titles as $colname => $title) {
        $length = $lengths[$colname];
        echo "+".str_repeat("-", $length);
    }
    echo "+\n";
    
    $recursive_items = array();
    // now show the values
    foreach ($result as $row) {
        foreach ($row as $colname => $value) {
            // recursively show the multi dimensional array
            if (is_array($value)) {
                if (array_key_exists($colname, $fieldnames)) $rec_titles = $fieldnames[$colname];
                else $rec_titles = array();
                $recursive_items[$recursive_id] = array("titles" => $rec_titles, "values" => $value);
                // show the reference # instead
                $value = "[".$recursive_id."]";
                $recursive_id++;
            }
            
            $length = $lengths[$colname];
            if (is_array($value)) continue;
            echo "| ".$value.str_repeat(" ", $length-strlen($value)-1);
        }
        echo "|\n";
    }
    
    // show last line
    foreach ($titles as $colname => $title) {
        $length = $lengths[$colname];
        echo "+".str_repeat("-", $length);
    }
    echo "+\n";
    
    // now recursively show the multidimensional array
    foreach ($recursive_items as $id => $item) {
        echo "\n";
        echo "[".$id."]:\n";
        show_output($item["values"], $item["titles"]);
    }
}


/**
 * center_text - Given a text and a length, returns a string of length $lenght with $text located in
 * the middle (the string is padded with whitespaces).
 *
 * @param    string
 * @param    int
 */
function center_text($text, $length) {
    if (strlen($text) >= $length) return $text;
    $delta = $length - strlen($text);
    $pad_left = floor($delta/2);
    $pad_right = $delta - $pad_left;
    return str_repeat(" ", $pad_left).$text.str_repeat(" ", $pad_right);
}
?>
