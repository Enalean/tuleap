<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateDocument.class.php');
require_once(CODENDI_CLI_DIR.'/lib/PHP_BigFile.class.php');

class CLI_Action_Docman_CreateFile extends CLI_Action_Docman_CreateDocument  {

    function __construct() {
        parent::__construct('createFile', 'Create a document of type file');
        $this->setSoapCommand('createDocmanFile');

        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<local_file_location>    content of the file',
            'soap'     => false,
        ));
    }

    function validate_content(&$content) {
        $error = '';
        if (!isset($content) || trim($content) == '') {
            $error = "You must specify the location of the file with the --content parameter";
        } else if (!file_exists($content)) {
            $error = "File '$content' doesn't exist";
        } else if (!fopen($content, 'rb')) {
            $error = "Could not open '$content' for reading";
        }
        if ($error) {
            echo $this->help();
            exit_error($error);
        }
        return true;
    }

    /**
     * Compares the local and the distant checksums and throw an error if they are different
     */
    function checkChecksum($group_id, $item_id, $filename) {
        $this->setSoapCommand('getDocmanFileMD5sum');
        $soap_params = array(
            'group_id' => $group_id,
            'item_id'  => $item_id,
        );

        $local_checksum = PHP_BigFile::getMd5Sum($filename);

        // For very big files, the checksum can take several minutes to be computed, so we set the socket timeout to 10 minutes
        $default_socket_timeout = ini_set('default_socket_timeout', 600);

        $distant_checksum = $GLOBALS['soap']->call('getDocmanFileMD5sum', $soap_params, $this->use_extra_params());

        //revert default_socket_timeout
        if ($default_socket_timeout !== false) {
            ini_set('default_socket_timeout', $default_socket_timeout);
        }

        if ($local_checksum == $distant_checksum) {
            echo "File uploaded successfully\n";
        } else {
            echo "ERROR: Local and remote checksums are not the same. You should remove the document on the server, and try to create it again.\n";
        }
    }

    function after_loadParams(&$loaded_params) {
        parent::after_loadParams($loaded_params);

        $filename = $loaded_params['others']['content'];

        $loaded_params['soap']['file_size'] = filesize($filename);
        $loaded_params['soap']['file_name'] = $filename;
        if (function_exists('mime_content_type')) {
            $loaded_params['soap']['mime_type'] = mime_content_type($filename);
        } else {
            $loaded_params['soap']['mime_type'] = 'application/octet-stream';
        }
    }

    function soapCall($soap_params, $use_extra_params = true) {
        $filename = $soap_params['file_name'];

        // How many chunks do we have to send
        if ($soap_params['file_size'] == 0) {
            $chunk_count = 1;
        } else {
            $chunk_count = ceil($soap_params['file_size'] / $GLOBALS['soap']->getFileChunkSize());
        }

        for ($chunk_offset = 0; $chunk_offset < $chunk_count; $chunk_offset++) {
            // Display progression indicator
            echo "\rSending file (". intval($chunk_offset / $chunk_count * 100) ."%)";

            // Retrieve the current chunk of the file
            $contents = file_get_contents($filename, null, null, $chunk_offset * $GLOBALS['soap']->getFileChunkSize(), $GLOBALS['soap']->getFileChunkSize());
            $soap_params['content'] = base64_encode($contents);
            $soap_params['chunk_offset'] = $chunk_offset;
            $soap_params['chunk_size'] = $GLOBALS['soap']->getFileChunkSize();

            // Send the chunk
            if (!$chunk_offset) {
                // If this is the first chunk, then use the original soapCommand...
                $item_id = $GLOBALS['soap']->call($this->soapCommand, $soap_params, $use_extra_params);

                // And reinit soap_params with the new item's id
                $soap_params = array(
                    'group_id' => $soap_params['group_id'],
                    'item_id'  => $item_id,
                );
            } else {
                // If this is not the first chunk, then we have to append the chunk
                $GLOBALS['soap']->call('appendDocmanFileChunk', $soap_params, $use_extra_params);
            }
        }
        // Finish!
        echo "\rSending file (100%)\n";

        // Check that the local and remote file are the same
        $this->checkChecksum($soap_params['group_id'], $item_id, $filename);

        // The soap result is the new item's id
        return $item_id;
    }
}
