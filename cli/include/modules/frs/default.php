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
// $PARAMS[0] is "frs" (the name of this module) and $PARAMS[1] is the name of the function
$module_name = array_shift($PARAMS);        // Pop off module name
$function_name = array_shift($PARAMS);        // Pop off function name

switch ($function_name) {
case "packages":
    frs_do_packagelist();
    break;
case "addpackage":
    frs_do_addpackage();
    break;
case "releases":
    frs_do_releaselist();
    break;
case "addrelease":
    frs_do_addrelease();
    break;
case "files":
    frs_do_filelist();
    break;
case "getfile":
    frs_do_getfile();
    break;
case "addfile":
    frs_do_addfile();
    break;
default:
    exit_error("Unknown function name: ".$function_name);
    break;
}

///////////////////////////////
/**
 * frs_do_packagelist - List of packages
 */
function frs_do_packagelist() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of packages that belongs to a project.
Parameters:
--project=<name>: Name of the project the returned packages belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    $user_id = $SOAP->getSessionUserID();
    
    $res = $SOAP->call("getPackages", array("group_id" => $group_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

/**
 * frs_do_addpackage - Add a new package
 */
function frs_do_addpackage() {
    global $PARAMS, $SOAP, $LOG;

    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Add a new package in frs manager.
Parameters:
--project=<name>: Name of the project in which this package will be added. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--name=<package_name>: Name of the package
--status_id=<status_id>: status of this package
--rank=<rank>: Rank of the package in the package list.
EOF;
        return;
    }

    // get the group id giving the project name
    // the project name is optionnal. If it is not filled, the client will search the used project in the sessions values.
    $group_id = get_working_group($PARAMS);
    
    $name = get_parameter($PARAMS, "name", true);
    if (!$name || strlen($name) == 0) {
        exit_error("You must specify the name of the package with the --name parameter");
    }
    
    $status_id = get_parameter($PARAMS, "status_id", true);
    if (! isset($status_id)) {
        // status_id is optionnal, by default, set to 1 (active)
        $status_id = 1;
    }
    
    $rank = get_parameter($PARAMS, "rank", true);
    if (! isset($rank)) {
        // rank is optionnal, by default, set to 0
        $rank = 0;
    }

    $approve_license = get_parameter($PARAMS, "approve_license", true);
    if (! isset($approve_license)) {
        // approve_license is optionnal, by default, set to 1 (approved)
        $approve_license = 1;
    }
    $approve_license = ($approve_license == 0 ? false : true);

    $cmd_params = array(
                    "group_id"        => $group_id,
                    "package_name"    => $name,
                    "status_id"       => $status_id,
                    "rank"            => $rank,
                    "approve_license" => $approve_license
                );
                
    $res = $SOAP->call("addPackage", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }

    show_output($res);

}

/**
 * frs_do_releaselist - List of releases
 */
function frs_do_releaselist() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of releases that belongs to a package.
Parameters:
--project=<name>: Name of the project the returned releases belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--package_id=<package_id>: Id of the package the returned releases belong to.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    $user_id = $SOAP->getSessionUserID();
    
    $package_id = get_parameter($PARAMS, "package_id", true);
    if (! isset($package_id)) {
        exit_error("You must specify the ID of the package with the --package_id parameter");
    }
    
    $res = $SOAP->call("getReleases", array("group_id" => $group_id, "package_id" => $package_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

/**
 * frs_do_addrelease - Add a new release
 */
function frs_do_addrelease() {
    global $PARAMS, $SOAP, $LOG;

    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Add a new release in frs manager.
Parameters:
--project=<name>: Name of the project in which this release will be added. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--package_id=<package_id>: Id of the package the release will belong to
--name=<release_name>: Name of the release
--notes=<notes>: Notes associated with this release
--changes=<changes>: Change log associated with this release
--status_id=<status_id>: status of this release
EOF;
        return;
    }

    // get the group id giving the project name
    // the project name is optionnal. If it is not filled, the client will search the used project in the sessions values.
    $group_id = get_working_group($PARAMS);
    
    $package_id = get_parameter($PARAMS, "package_id", true);
    if (! isset($package_id)) {
        exit_error("You must specify the ID of the package your release belong to with the --package_id parameter");
    }
    
    $name = get_parameter($PARAMS, "name", true);
    if (!$name || strlen($name) == 0) {
        exit_error("You must specify the name of the release with the --name parameter");
    }
    
    $notes = get_parameter($PARAMS, "notes", true);
    if (!$notes || strlen($notes) == 0) {
        $notes = '';
    }
    
    $changes = get_parameter($PARAMS, "changes", true);
    if (!$changes || strlen($changes) == 0) {
        $changes = '';
    }
    
    $status_id = get_parameter($PARAMS, "status_id", true);
    if (! isset($status_id)) {
        // status_id is optionnal, by default, set to 1 (active)
        $status_id = 1;
    }

    $cmd_params = array(
                    "group_id"        => $group_id,
                    "package_id"      => $package_id,
                    "name"            => $name,
                    "notes"           => $notes,
                    "changes"         => $changes,
                    "status_id"       => $status_id
                );
                
    $res = $SOAP->call("addRelease", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }

    show_output($res);

}

/**
 * frs_do_filelist - List of files
 */
function frs_do_filelist() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Returns the list of files that belongs to a release.
Parameters:
--project=<name>: Name of the project the returned releases belong to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--package_id=<package_id>: Id of the package the returned files belong to.
--release_id=<package_id>: Id of the release the returned files belong to.
EOF;
        return;
    }

    $group_id = get_working_group($PARAMS);
    $user_id = $SOAP->getSessionUserID();
    
    $package_id = get_parameter($PARAMS, "package_id", true);
    if (! isset($package_id)) {
        exit_error("You must specify the ID of the package with the --package_id parameter");
    }
    
    $release_id = get_parameter($PARAMS, "release_id", true);
    if (! isset($release_id)) {
        exit_error("You must specify the ID of the release with the --release_id parameter");
    }
    
    $res = $SOAP->call("getFiles", array("group_id" => $group_id, "package_id" => $package_id, "release_id" => $release_id));
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

function frs_do_getfile() {
    global $PARAMS, $SOAP, $LOG;
    
    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Get the content of the file
Parameters:
--project=<name>: Name of the project the file belongs to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--package_id=<package_id>: Id of the package the file belongs to.
--release_id=<package_id>: Id of the release the file belongs to.
--output=<location>: name of the file to write the file to
EOF;
        return;
    }
    
    if (!($package_id = get_parameter($PARAMS, "package_id", true))) {
        exit_error("You must define a package with the --package parameter");
    }

    if (!($release_id = get_parameter($PARAMS, "release_id", true))) {
        exit_error("You must define a release with the --release parameter");
    }

    if (!($file_id = get_parameter($PARAMS, "file_id", true))) {
        exit_error("You must define a file with the --id parameter");
    }

    // Should we save the contents to a file?
    $output = get_parameter($PARAMS, "output", true); 
    if (isset($output) && trim($output) != '') {
        if (file_exists($output)) {
            $sure = get_user_input("File $output already exists. Do you want to overwrite it? (y/n): ");
            if (strtolower($sure) != "y" && strtolower($sure) != "yes") {
                exit_error("Retrieval of file aborted");
            }
        }
    }

    $group_id = get_working_group($PARAMS);

    $cmd_params = array(
                    "group_id" => $group_id,
                    "package_id" => $package_id,
                    "release_id" => $release_id,
                    "file_id" => $file_id
                );

    $res = $SOAP->call("getFile", $cmd_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    $file = base64_decode($res);

    if ($output) {
        while (!($fh = @fopen($output, "wb"))) {
            echo "Couldn't open file ".$output." for writing.\n";
            $output = "";
            while (!$output) {
                $output = get_user_input("Please specify a new file name: ");
            }
        }
        
        fwrite($fh, $file, strlen($file));
        fclose($fh);
        
        echo "File retrieved successfully.\n";
    } else {
        echo $file;     // if not saving to a file, output to screen
    }
}

function frs_do_addfile() {
    global $PARAMS, $SOAP, $LOG;

    if (get_parameter($PARAMS, "help")) {
        echo <<<EOF
Add the the file to a release.
Parameters:
--project=<name>: Name of the project the file will be added to. If you specified the name of
    the working project when you logged in, this parameter is not needed.
--package_id=<package_id>: Id of the package the file will belong to.
--release_id=<package_id>: Id of the release the file will belong to.
--file=<file_location>: file to add
--type_id=<type_id>: Id of the type of the file.
--processor_id=<processor_id>: Id of the processor of the file
EOF;
        return;
    }

    $package_id = get_parameter($PARAMS, "package_id", true);
    if (! isset($package_id)) {
        exit_error("You must specify the ID of the package with the --package_id parameter");
    }
    
    $release_id = get_parameter($PARAMS, "release_id", true);
    if (! isset($release_id)) {
        exit_error("You must specify the ID of the release with the --release_id parameter");
    }
    
    $file_name = get_parameter($PARAMS, "file", true);
    if (! $file_name) {
        exit_error("You must define a file with the --file parameter");
    } else if (!file_exists($file_name)) {
        exit_error("File '$file_name' doesn't exist");
    } else if (!($fh = fopen($file_name, "rb"))) {
        exit_error("Could not open '$file_name' for reading");
    }
    
    $type_id = get_parameter($PARAMS, "type_id", true);
    if (! isset($type_id)) {
        $type_id = 9999;            // 9999 = "other"
    }
    
    $processor_id = get_parameter($PARAMS, "processor_id", true);
    if (! isset($processor_id)) {
        $processor_id = 9999;            // 9999 = "other"
    }

    $name = basename($file_name);
    $contents = fread($fh, filesize($file_name));
    $base64_contents = base64_encode($contents);
    
    fclose($fh);
    
    $group_id = get_working_group($PARAMS);
    
    $add_params = array(
                    "group_id"        => $group_id,
                    "package_id"      => $package_id,
                    "release_id"      => $release_id,
                    "filename"        => $name,
                    "base64_contents" => $base64_contents,
                    "type_id"         => $type_id,
                    "processor_id"    => $processor_id
                );

    $res = $SOAP->call("addFile", $add_params);
    if (($error = $SOAP->getError())) {
        $LOG->add($SOAP->responseData);
        exit_error($error, $SOAP->faultcode);
    }
    
    show_output($res);
}

?>