<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');
require_once(CODENDI_CLI_DIR.'/lib/PHP_BigFile.class.php');

class CLI_Action_Frs_AddFile extends CLI_Action {
    function __construct() {
        parent::__construct('addFile', 'Add the the file to a release.');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'release_id',
            'description'    => '--release_id=<package_id>    Id of the release the returned file belong to.',
        ));
        $this->addParam(array(
            'name'           => 'uploaded_file',
            'description'    => '--uploaded_file=<file_name>  file name of the file to add (file must already be in the incoming dir).',
            'soap'           => false,
        ));
        $this->addParam(array(
            'name'           => 'local_file',
            'description'    => '--local_file=<file_location> local file location to add (the file will be uploaded on the server)',
            'soap'           => false,
        ));
        $this->addParam(array(
            'name'           => 'type_id',
            'description'    => '--type_id=<type_id>          Id of the type of the file.',
        ));
        $this->addParam(array(
            'name'           => 'processor_id',
            'description'    => '--processor_id=<processor_id> Id of the processor of the file',
        ));
        $this->addParam(array(
            'name'           => 'reference_md5',
            'description'    => '--reference_md5=<reference_md5> Md5 checksum of the file located in the incoming directory on the server.'
             ));
        $this->addParam(array(
            'name'           => 'comment',
            'description'    => '--comment=<comment>    A comment/description of the uploaded file',
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
    function validate_type_id(&$type_id) {
        if (!$type_id) {
            $type_id = 9999;
        }
        return true;
    }
    function validate_processor_id(&$processor_id) {
        if (!$processor_id) {
            $processor_id = 9999;
        }
        return true;
    }
    function validate_reference_md5(&$reference_md5) {
         if (!$reference_md5) {
             $reference_md5 = '';
         }
         return true;
     }
    function before_soapCall(&$loaded_params) {
        $this->soapCommand = 'addUploadedFile';
        if (!$loaded_params['others']['uploaded_file'] && !$loaded_params['others']['local_file']) {
            exit_error("You must specify a file name with either the --local_file or --uploaded_file parameter, depending the way you want to add the file.");
        } else {
            if (!$loaded_params['others']['local_file']) {
                // we will test if the file is present in the incoming directory
                $uploaded_files = $GLOBALS['soap']->call("getUploadedFiles", array('group_id' => $loaded_params['soap']['group_id']));
                if (! in_array($loaded_params['others']['uploaded_file'], $uploaded_files)) {
                    exit_error("File '". $loaded_params['others']['uploaded_file'] ."' not found in incoming directory.");
                }
                $loaded_params['soap']['filename']  = $loaded_params['others']['uploaded_file'];
            } else {
                $localFileLocation = $loaded_params['others']['local_file'];
                if (!file_exists($localFileLocation)) {
                    exit_error("File '". $localFileLocation ."' doesn't exist");
                } else if (!is_readable($localFileLocation)) {
                    exit_error("Could not open '". $localFileLocation ."' for reading");
                } else {
                    // TODO : use PHP_BigFile
                    //$path = PHP_BigFile::stream(realpath($localFileLocation));
                    $path = realpath($localFileLocation);
                    $GLOBALS['LOG']->add('Calculating md5 checksum of the file ...');
                    $loaded_params['soap']['reference_md5'] = PHP_BigFile::getMd5Sum($path);
                    $GLOBALS['LOG']->add('Md5 checksum calculated.');
                    $offset = 0;
                    $chunkSize = $GLOBALS['soap']->getFileChunkSize();
                    $startTime = microtime(true);
                    $totalTran = 0;
                    $i = 0;
                    /* During this loop the file in construction in the incoming directory
                     * may be corrupted by concurrent access, such as releasing it
                     * or use of addFileChunk again with the same filename.
                     * This corruption will be automatically detected at the end of upload
                     * when comparing the md5 sums and file will not be released.
                     */
                    do {
                        $offset = $i * $chunkSize;
                        $contents = file_get_contents($path, false, NULL, $offset, $chunkSize);
                        $cLength = strlen($contents);
                        $contents = base64_encode($contents);
                        if ($i == 0) {
                            $firstChunk = true;
                        } else {
                            $firstChunk = false;
                        }
                        $addedSize = $GLOBALS['soap']->call("addFileChunk", array('filename' => basename($path), 'contents' => $contents, 'first_chunk' => $firstChunk));
                        if ($addedSize == $cLength) {
                            $totalTran += $cLength;
                            $i++;
                        } else {
                            exit_error("Upload of the file failed");
                        }
                    } while ($cLength >= $chunkSize);
                    $endTime = microtime(true);
                    $transRate = $totalTran / ($endTime - $startTime);
                    $GLOBALS['LOG']->add('Transfer rate: '.size_readable($transRate, null, 'bi', '%.2f %s/s'));

                    $loaded_params['soap']['filename']  = basename($loaded_params['others']['local_file']);
                    $loaded_params['soap']['is_upload'] = true;
                }
            }

            // sort the parameters in the right order
            uksort($loaded_params['soap'], array($this, "sort_parameters"));

        }
    }

    function confirmation($loaded_params) {
        if (!array_key_exists('noask', $loaded_params['others']) || !$loaded_params['others']['noask']) {
            if ($loaded_params['others']['local_file']) {
                if (filesize($loaded_params['others']['local_file']) == 0) {
                    echo "You're about to add an empty file (with size 0):\n";
                    if (!$this->user_confirm("Do you want to proceed?")) {
                        exit_error("Submission aborted");
                    }
                }
            }
        }
        return true;
    }

    function sort_parameters($p1, $p2) {
        $order = array('group_id', 'package_id', 'release_id', 'filename', 'type_id', 'processor_id', 'reference_md5', 'comment', 'is_upload');
        $order_flip = array_flip($order);
        return $order_flip[$p1] > $order_flip[$p2];
    }

}
