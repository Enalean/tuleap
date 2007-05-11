<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_GetFile extends CLI_Action {
    function CLI_Action_Frs_GetFile() {
        $this->CLI_Action('getFile', 'Get the content of the file');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'release_id',
            'description'    => '--release_id=<package_id>    Id of the release the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'file_id',
            'description'    => '--release_id=<package_id>    Id of the release the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'output',
            'description'    => '--output=<location>          (Optional) Name of the file to write the file to',
            'soap'           => false
        ));
    }
    function validate_package_id(&$package_id) {
        if (!$package_id) {
            exit_error("You must specify the ID of the package with the --package_id parameter");
        }
        return true;
    }
    function validate_release_id(&$release_id) {
        if (!$release_id) {
            exit_error("You must specify the ID of the release with the --release_id parameter");
        }
        return true;
    }
    function validate_file_id(&$file_id) {
        if (!$file_id) {
            exit_error("You must specify the ID of the file with the --file_id parameter");
        }
        return true;
    }
    function validate_output(&$output) {
        if ($output) {
            $output = trim($output);
            if ($output && file_exists($output)) {
                if (!$this->user_confirm("File $output already exists. Do you want to overwrite it?")) {
                    exit_error("Retrieval of file aborted");
                }
            }
        }
        return true;
    }
    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        $file = base64_decode($soap_result);
    
        if ($loaded_params['others']['output']) {
            $output = $loaded_params['others']['output'];
            while (!($fh = @fopen($output, "wb"))) {
                echo "Couldn't open file ".$output." for writing.\n";
                $output = "";
                while (!$output) {
                    $output = get_user_input("Please specify a new file name: ");
                }
            }
            
            fwrite($fh, $file, strlen($file));
            fclose($fh);
            
            if (!$loaded_params['others']['quiet']) echo "File retrieved successfully.\n";
        } else {
            if (!$loaded_params['others']['quiet']) echo $file;     // if not saving to a file, output to screen
        }
    }
}

?>
