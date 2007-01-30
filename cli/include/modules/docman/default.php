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


// function to execute
// $PARAMS[0] is "docman" (the name of this module) and $PARAMS[1] is the name of the function
$module_name = array_shift($PARAMS);        // Pop off module name
$function_name = array_shift($PARAMS);        // Pop off function name

switch ($function_name) {
case "delete":
    docman_do_delete();
    break;
case "monitor":
    docman_do_monitor();
    break;
case "properties":
    docman_do_propertieslist();
    break;
default:
    exit_error("Unknown function name: ".$function_name);
    break;
}

///////////////////////////////
/**
 * docman_do_delete - delete an item
 */
function docman_do_delete() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of documents that belongs to a project.
Parameters:
--id=<item_id>: ID of the item we want to delete
--project=<name>: Name of the project the document belongs to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    
    $item_id = get_parameter($PARAMS, "id", true);
    if (!isset($item_id)) {
        exit_error("You must specify the ID of the document with the --id parameter");
    }
    
    $res = $SOAP->call("delete", array("group_id" => $group_id, "item_id" => $item_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}
/**
 * docman_do_monitor - monitor an item
 */
function docman_do_monitor() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Monitor an item
Parameters:
--id=<item_id>: ID of the item we want to monitor
--project=<name>: Name of the project the document belongs to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    
    $item_id = get_parameter($PARAMS, "id", true);
    if (!isset($item_id)) {
        exit_error("You must specify the ID of the document with the --id parameter");
    }
    
    $res = $SOAP->call("monitor", array("group_id" => $group_id, "item_id" => $item_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

/**
 * docman_do_propertieslist - List of document properties
 */
function docman_do_propertieslist() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of documents that belongs to a project.
Parameters:
--id=<item_id>: ID of the item we want to get the properties
--project=<name>: Name of the project the document belongs to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    
    $item_id = get_parameter($PARAMS, "id", true);
    if (!isset($item_id)) {
        exit_error("You must specify the ID of the document with the --id parameter");
    }
    
    $res = $SOAP->call("getProperties", array("group_id" => $group_id, "item_id" => $item_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

?>