<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_GetFile extends CLI_Action {

    private $fileChunkSize = 6000000; // ~6 Mo

    function __construct() {
        parent::__construct('getFile', 'Get the content of the file');
        $this->setSoapCommand('getFileChunk');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'release_id',
            'description'    => '--release_id=<release_id>    Id of the release the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'file_id',
            'description'    => '--file_id=<file_id>    Id of the file.',
        ));
        $this->addParam(array(
            'name'           => 'output',
            'description'    => '--output=<location>          (Optional) Name of the file to write the file to',
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

    function soapCall($soap_params, $use_extra_params = true) {
        // Prepare SOAP parameters
        $callParams = $soap_params;
        unset($callParams['output']);
        $callParams['offset']     = 0;
        $callParams['chunk_size'] = $this->fileChunkSize;

        // Manage screen/file output
        $output = false;
        if ($soap_params['output']) {
            $output = $soap_params['output'];
        }
        if ($output !== false) {
            while (!($fd = @fopen($output, "wb"))) {
                echo "Couldn't open file ".$output." for writing.\n";
                $output = "";
                while (!$output) {
                    $output = get_user_input("Please specify a new file name: ");
                }
            }
        }

        $i = 0;
        do {
            $callParams['offset'] = $i * $this->fileChunkSize;
            $content = base64_decode($GLOBALS['soap']->call($this->soapCommand, $callParams, $use_extra_params));
            $cLength = strlen($content);
            if ($output !== false) {
                $written = fwrite($fd, $content);
                if ($written != $cLength) {
                    throw new Exception('Received '.$cLength.' of data but only '.$written.' written on Disk');
                }
            } else {
                echo $content;
            }
            $i++;
        } while ($cLength >= $this->fileChunkSize);

        if ($output !== false) {
            fclose($fd);
        }
    }

    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {}
}

?>