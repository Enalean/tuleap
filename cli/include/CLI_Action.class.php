<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

class CLI_Action {

    var $name;
    var $description;
    var $module;
    var $soap;
    var $params;
    var $soapCommand;
    function __construct($name, $description) {
        $this->name              = $name;
        $this->soapCommand       = $name;
        $this->description       = $description;
        $this->params            = array();
        $this->addParam(array(
            'name'           => 'quiet',
            'description'    => '--quiet or -q      Quiet-mode. Suppress result output.',
            'parameters'     => array('q', 'quiet'),
            'value_required' => false,
            'soap'           => false,
        ));
        $this->addProjectParam();
    }
    function addProjectParam() {
        $this->addParam(array(
            'name'           => 'group_id',
            'description'    => '--project=<name>   Name of the project the item belongs to. If you specified the name of
      the working project when you logged in, this parameter is not needed.',
            'parameters'     => array('project'),
        ));
    }
    function getName() {
        return $this->name;
    }
    function getDescription() {
        return $this->description;
    }
    function setModule(&$module) {
        $this->module =& $module;
    }
    function setSoapCommand($commandName) {
        $this->soapCommand = $commandName;
    }
    function addParam($param) {
        $this->params[] = $param;
    }
    function getAllParams() {
        return $this->params;
    }
    function help() {
        $help = $this->getName() .":\n";
        $help .= $this->getDescription() ."\n\n";
        $help .= "Available parameters:\n";
        foreach($this->params as $param) {
            $help .= "   ". $param['description'] ."\n";
        }
        $help .= "\n   --help    Show this screen\n";
        return $help;
    }

    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        if (!$loaded_params['others']['quiet']) $this->show_output($soap_result, $fieldnames);
    }
    function loadParams($params) {
        $all_params = array('soap' => array(), 'others' => array());
        foreach($this->params as $param) {
            $search_for = $param['name'];
            if (isset($param['parameters'])) {
                $search_for = $param['parameters'];
            }
            $type = 'soap';
            if (isset($param['soap']) && !$param['soap']) {
                $type = 'others';
            }
            $value_required = !isset($param['value_required']) || $param['value_required'];
            if (isset($param['method'])) {
                $t = call_user_func_array($param['method'], array(&$params));
            } else {
                $t = $this->module->getParameter($params, $search_for, $value_required);
            }
            if (!method_exists($this, 'validate_'. $param['name'])
                ||
                call_user_func_array(array($this, 'validate_'. $param['name']), array(&$t))
            ) {
                $all_params[$type][$param['name']] = $t;
            }
        }
        return $all_params;
    }
    function confirmation($params) {
        return true;
    }
    function before_soapCall(&$loaded_params) {
    }
    function after_loadParams(&$loaded_params) {
    }
    function use_extra_params() {
        return true;
    }
    function soapCall($soap_params, $use_extra_params = true) {
        return $GLOBALS['soap']->call($this->soapCommand, $soap_params, $use_extra_params);
    }
    /*function soapError() {
        if (($error = $GLOBALS['soap']->getError())) {
            $GLOBALS['LOG']->add($GLOBALS['soap']->responseData);
            exit_error($error, $GLOBALS['soap']->faultcode);
        }
    }*/

    function execute($params) {
    	$soap_result = null;
        if ($this->module->getParameter($params, array('h', 'help'))) {
            echo $this->help();
        } else {
            $loaded_params = $this->loadParams($params);
            $this->after_loadParams($loaded_params);
            if ($this->confirmation($loaded_params)) {
                $this->before_soapCall($loaded_params);
                try {
                	$soap_result = $this->soapCall($loaded_params['soap'], $this->use_extra_params());
                } catch (SoapFault $fault) {
                    $GLOBALS['LOG']->add($GLOBALS['soap']->__getLastResponse());
                    exit_error($fault, $fault->getCode());
                }
                $this->soapResult($params, $soap_result, array(), $loaded_params);
            }
        }
        return $soap_result;
    }

    function validate_group_id(&$group_id) {
        $group_id = $this->get_working_group($group_id);
        return true;
    }

    function user_confirm($msg) {
        $sure = $this->module->get_user_input($msg. " (y/n): ");
        return strtolower($sure) == 'yes' || strtolower($sure) == 'y';
    }
    /**
     * show_output - Format a SOAP result in order to display it on the user's screen
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * @param    array    Result of a SOAP call
     * @param    array    Titles to assign to each field (optional).
     */
    function show_output($result, $fieldnames = array()) {
        // There are 3 types of output: a scalar (int, string), a vector or a matrix (table-like).
        // Try to guess which output is the best for $result
        if (is_object($result)) {

            $this->show_object($result, $fieldnames);

        } elseif(is_array($result)) {

            if (count($result) == 0) {
                echo "No results\n";
                return;
            }

            if (isset($result[0]) && is_array($result[0])) {
                $this->show_matrix($result, $fieldnames);
            } elseif (isset($result[0]) && is_object($result[0])) {
                $this->show_matrix($result, $fieldnames);
            } else {
                $this->show_vector($result, $fieldnames);
            }
        } else {
            $this->show_scalar($result, $fieldnames);
        }
    }

    /**
     * Check if the object returned by a SOAP call is valid
     * @see CLI_Action::show_object()
     *
     * @param array $row Result of a SOAP call
     *
     * @return Boolean
     */
    private function isIterable($row) {
        return (is_object($row) && get_class($row) === 'stdClass') || is_array($row);
    }

    function show_object($result, $fieldnames = array()) {
        $titles = array();
        $lengths = array();

        // this is for showing multidimensional arrays
        static $recursive_id = 1;

        foreach ($result as $colname => $value) {
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

        // show the titles
        foreach ($titles as $colname => $title) {
            $length = $lengths[$colname];
            echo "+".str_repeat("-", $length);
        }
        echo "+\n";
        foreach ($titles as $colname => $title) {
            $length = $lengths[$colname];
            echo "|".$this->center_text($title, $length);
        }
        echo "|\n";
        foreach ($titles as $colname => $title) {
            $length = $lengths[$colname];
            echo "+".str_repeat("-", $length);
        }
        echo "+\n";

        $recursive_items = array();
        // now show the values
        foreach ($result as $colname => $value) {
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
            $this->show_output($item["values"], $item["titles"]);
        }
    }


    function show_objects($result, $fieldnames = array()) {
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
            echo "|".$this->center_text($title, $length);
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
            $this->show_output($item["values"], $item["titles"]);
        }
    }

    /**
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     */
    function show_scalar($result, $fieldnames = array()) {
        $title = (isset($fieldnames[0])) ? $fieldnames[0] : "Result";
        // convert to string (may be an int)
        $result = "$result";
        $length = max(strlen($result), strlen($title));
        $length = $length + 2;        // +2 for having spaces at the beginning and the end

        // show the title
        echo "+".str_repeat("-", $length)."+\n";
        echo "|".$this->center_text($title, $length)."|\n";
        echo "+".str_repeat("-", $length)."+\n";

        // show the item
        echo "| ".$result.str_repeat(" ", $length-strlen($result)-1)."|\n";
        echo "+".str_repeat("-", $length)."+\n";
    }

    /**
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     */
    function show_vector($result, $fieldnames = array()) {
        // There are two types of vector: those that are a set of items and those
        // that are just like a row in the database. For the second case, reuse the
        // show_matrix function considering the vector as a 1-row matrix
        if (!isset($result[0])) {
            // This happens when $result is an indexed array (i.e. $result["fieldname"]. In this
            // case consider $result as a 1 row matrix
            $foo_matrix = array();
            $foo_matrix[] = $result;
            $this->show_matrix($foo_matrix, $fieldnames);
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
        echo "|".$this->center_text($title, $length)."|\n";
        echo "+".str_repeat("-", $length)."+\n";

        // show each item
        foreach ($result as $item) {
            echo "| ".$item.str_repeat(" ", $length-strlen($item)-1)."|\n";
        }

        // show last line
        echo "+".str_repeat("-", $length)."+\n";
    }

    /**
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     */
    function show_matrix($result, $fieldnames = array()) {
        $titles = array();
        $lengths = array();

        // this is for showing multidimensional arrays
        static $recursive_id = 1;

        foreach ($result as $row) {
            if ($this->isIterable($row)) {
                foreach ($row as $colname => $value) {
                    if (!isset($titles[$colname])) {
                        if (!isset($fieldnames[$colname])) {
                            $titles[$colname] = $colname;
                        } else {
                            $titles[$colname] = $fieldnames[$colname];
                        }
                    }
                }
            }
        }
        // Multi-line string handling
        // A row containing a N-line cell will give N rows in the array $result
        $rowArrays = array();
        $result2 = array();
        foreach ($result as $i => $row) {
            if ($this->isIterable($row)) {
                foreach ($row as $colname => $value) {
                    if (is_array($value)) {
                        $lengths[$colname] = strlen($titles[$colname]) + 2;
                        $rowArrays[$i][0][$colname] = $value;
                    } else {
                        $lines = explode("\n", $value);

                        foreach ($lines as $j => $line) {
                            if (!isset($lengths[$colname]) || $lengths[$colname] < strlen($line)+2) {
                                $lengths[$colname] = max(strlen($line), strlen($titles[$colname]));
                                $lengths[$colname] += 2;
                            }

                            if (!isset($rowArrays[$i][$j])) {
                                foreach ($titles as $colname2 => $v) {
                                    $rowArrays[$i][$j][$colname2] = '';
                                }
                            }
                            $rowArrays[$i][$j][$colname] = $line;
                        }
                    }
                }
                foreach ($rowArrays[$i] as $row) {
                    $result2[] = $row;
                }
            }
        }
        $result = $result2;

        // show the titles
        foreach ($titles as $colname => $title) {
            $length = $lengths[$colname];
            echo "+".str_repeat("-", $length);
        }
        echo "+\n";
        foreach ($titles as $colname => $title) {
            $length = $lengths[$colname];
            echo "|".$this->center_text($title, $length);
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
            if ($this->isIterable($row)) {
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
            $this->show_output($item["values"], $item["titles"]);
        }
    }
    /**
     * center_text - Given a text and a length, returns a string of length $lenght with $text located in
     * the middle (the string is padded with whitespaces).
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
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

    /**
     * get_group_id - Given a group UNIX name, returns the group ID or 0 if the group doesn't exists
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * @param    string    UNIX name of the project
     */
    function get_group_id($unix_group_name) {
        if (!isset($this->cached_group_id[$unix_group_name])) {
            try {

                $res = $GLOBALS['soap']->call("getGroupByName", array(/*"sessionKey" => $GLOBALS['soap']->session_string, */ "unix_group_name" => $unix_group_name));

                if (!is_object($res)) {        // An error here means that no group was found
                    $this->cached_group_id[$unix_group_name] = 0;
                } else {
                    $this->cached_group_id[$unix_group_name] = $res->group_id;
                }

             } catch (SoapFault $fault) {
                $this->cached_group_id[$unix_group_name] = 0;
            }
        }
        return $this->cached_group_id[$unix_group_name];
    }
    var $cached_group_id;

    /**
     * get_working_group - Return the ID of the group the user is currently working with. The name of the group can be defined
     * on the session or in the parameters.
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
     * @param array An array of parameters to look for the defined group. If the group isn't in the parameters, looks in the session
     * @param bool Specify if we should abort the program if the group isn't found
     */
    function get_working_group($group_name, $die=true) {
        if ($group_name) {
            $group_id = $this->get_group_id($group_name);
            if (!$group_id) {
                if ($die) exit_error("Invalid project \"".$group_name."\"");
                else return false;
            }
        } else {
            $group_id = $GLOBALS['soap']->getSessionGroupID();
            if (!$group_id) {
                if ($die) exit_error("You must specify a project using the --project=parameter");
                else return false;
            }
        }
        return $group_id;
    }

    /**
     * check_date - Check if a date entered by the user is correctly formatted and it is valid.
     *
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
     *
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
     * extracted from GForge Command-line Interface
     * contained in GForge.
     * Copyright 2005 GForge, LLC
     * http://gforge.org/
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

}
