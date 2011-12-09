<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');
require_once(CODENDI_CLI_DIR.'lib/PHP_BigFile.class.php');

class CLI_Action_Frs_GetFile extends CLI_Action {

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
        $this->addParam(array(
            'name'           => 'remote_name',
            'description'    => '--remote_name          (Optional) Use this if you want to retrieve the filename from the server instead of using --output',
            'parameters'     => array('remote_name'),
            'value_required' => false,
            'soap'           => false,
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

    // Manage screen/file output
    function manageOutput($soap_params, &$output, &$fd, $remoteName) {
        $output = false;
        if ($soap_params['output']) {
            $output = $soap_params['output'];
        } elseif ($remoteName) {
            $fileInfo = $GLOBALS['soap']->call('getFileInfo', $soap_params);
            $output   = basename($fileInfo->file_name);
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
    }

    function after_loadParams(&$loaded_params) {
        $loaded_params['soap']['remote_name'] = $loaded_params['others']['remote_name'];
    }

    function soapCall($soap_params, $use_extra_params = true) {
        // Prepare SOAP parameters
        $callParams = $soap_params;
        $remoteName = $callParams['remote_name'];
        unset($callParams['remote_name']);
        unset($soap_params['remote_name']);
        unset($callParams['output']);
        $callParams['offset']     = 0;
        $callParams['chunk_size'] = $GLOBALS['soap']->getFileChunkSize();

        $startTime = microtime(true);
        $totalTran = 0;
        $i = 0;
        do {
            $callParams['offset'] = $i * $GLOBALS['soap']->getFileChunkSize();
            $content = base64_decode($GLOBALS['soap']->call($this->soapCommand, $callParams, $use_extra_params));
            if ($i == 0) {
                $this->manageOutput($soap_params, $output, $fd, $remoteName);
            }
            $cLength = strlen($content);
            if ($output !== false) {
                $written = fwrite($fd, $content);
                if ($written != $cLength) {
                    throw new Exception('Received '.$cLength.' of data but only '.$written.' written on Disk');
                }
            } else {
                echo $content;
            }
            $totalTran += $cLength;
            $i++;
        } while ($cLength >= $GLOBALS['soap']->getFileChunkSize());
        $endTime = microtime(true);
        $GLOBALS['LOG']->add('File download completed');

        $transRate = $totalTran / ($endTime - $startTime);
        $GLOBALS['LOG']->add('Transfer rate: '.size_readable($transRate, null, 'bi', '%.2f %s/s'));

        if ($output !== false) {
            fclose($fd);

            unset($callParams['offset']);
            unset($callParams['chunk_size']);

            $fileInfo = $GLOBALS['soap']->call('getFileInfo', $callParams, $use_extra_params);
            if ($fileInfo->computed_md5) {
                $GLOBALS['LOG']->add('Compute downloaded file\'s md5 sum');
                $localChecksum = PHP_BigFile::getMd5Sum($output);
                if ($localChecksum != $fileInfo->computed_md5) {
                    exit_error("File transfer faild: md5 checksum locally computed doesn't match remote one ($fileInfo->computed_md5)");
                } else {
                    echo "File retrieved successfully (md5 checksum verified).\n";
                }
            } else {
                echo "File retrieved successfully.\n";
            }
        }
    }

    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {}
}

?>