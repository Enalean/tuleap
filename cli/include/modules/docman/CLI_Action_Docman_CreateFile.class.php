<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateDocument.class.php');

class CLI_Action_Docman_CreateFile extends CLI_Action_Docman_CreateDocument  {
    
    private $chunk_size = 6000000; // ~6 Mo
    private $current_chunk_offset = 0;
    private $filename;

    function CLI_Action_Docman_CreateFile() {
        $this->CLI_Action_Docman_CreateDocument('createFile', 'Create a document of type file');
        $this->setSoapCommand('createDocmanFile');
        
        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<local_file_location>    content of the file',
            'soap'     => false,
        ));
    }
    
    function validate_content(&$content) {
        if (!isset($content) || trim($content) == '') {
            echo $this->help();
            exit_error("You must specify the location of the file with the --content parameter");
        }
        return true;
    }

    /**
     * Load the next file chunk and set the corresponding SOAP parameters
     */
    function loadChunk(&$loaded_params) {
        if (isset($this->filename)) {
            $chunk_offset = $this->current_chunk_offset++;
            $contents = file_get_contents($this->filename, null, null, $chunk_offset * $this->chunk_size, $this->chunk_size);
            $loaded_params['soap']['content'] = base64_encode($contents);
            $loaded_params['soap']['chunk_offset'] = $chunk_offset;
            $loaded_params['soap']['chunk_size'] = $this->chunk_size;
        }
    }
    
    /**
     * Compares the local and the distant checksums and throw an error if they are different
     */
    function checkChecksum(&$loaded_params, $item_id) {
        $this->setSoapCommand('getDocmanFileMD5sum');
        $loaded_params['soap'] = array(
            'group_id' => $loaded_params['soap']['group_id'],
            'item_id'  => $item_id,
            'version'  => 0,
        );

        $local_checksum = md5_file($this->filename);

        // For very big files, the checksum can take several minutes to be computed, so we set the socket timeout to 10 minutes
        ini_set('default_socket_timeout', 600);

        $distant_checksum = $this->soapCall($loaded_params['soap'], $this->use_extra_params());

        if ($local_checksum == $distant_checksum) {
            echo "File uploaded successfully\n";
        } else {
            exit_error("Local and remote checksums are not the same. You should remove the document on the server, and try to create it again.");
        }
    }
    
    function after_loadParams(&$loaded_params) {
        parent::after_loadParams($loaded_params);
        
        if (!isset($loaded_params['others']['content']) || trim($loaded_params['others']['content']) == '') {
            echo $this->help();
            exit_error("You must specify the content of the document with the --content parameter, according to the document type");
        }

        $this->filename = $loaded_params['others']['content'];

        if (!file_exists($this->filename)) {
            exit_error("File '". $this->filename ."' doesn't exist");
        } else if (!($fh = fopen($this->filename, "rb"))) {
            exit_error("Could not open '$this->filename' for reading");
        } else {
            $this->loadChunk($loaded_params);
            $loaded_params['soap']['file_size'] = filesize($this->filename);
            $loaded_params['soap']['file_name'] = $this->filename;
            $loaded_params['soap']['mime_type'] = mime_content_type($this->filename);
            echo "Sending file (0%)";
        }
    }
    
    function execute($params) {
        $soap_result = null;
        if ($this->module->getParameter($params, array('h', 'help'))) {
            echo $this->help();
        } else {
            $loaded_params = $this->loadParams($params);
            $this->after_loadParams($loaded_params);
                
            try {
                //print_r($loaded_params['soap']);
                $soap_result = $this->soapCall($loaded_params['soap'], $this->use_extra_params());
            } catch (SoapFault $fault) {
                $GLOBALS['LOG']->add($GLOBALS['soap']->__getLastResponse());
                exit_error($fault, $fault->getCode());
            }

            $item_id = $soap_result;
            $filesize = filesize($this->filename);

            $modulo = $filesize % $this->chunk_size;
            $chunk_count = ($filesize - $modulo) / $this->chunk_size;
            if ($modulo != 0) {
                $chunk_count++;
            }

            $this->setSoapCommand('appendDocmanFileChunk');

            $loaded_params['soap'] = array(
                'group_id' => $loaded_params['soap']['group_id'],
                'item_id' => $item_id,
            );

            // Send the other chunks
            while($this->current_chunk_offset < $chunk_count) {
                echo "\rSending file (".intval($this->current_chunk_offset / $chunk_count * 100).'%)';
                $this->loadChunk($loaded_params);
                try {
                    $soap_result2 = $this->soapCall($loaded_params['soap'], $this->use_extra_params());
                } catch (SoapFault $fault) {
                    $GLOBALS['LOG']->add($GLOBALS['soap']->__getLastResponse());
                    exit_error($fault, $fault->getCode());
                }
            }
            echo "\rSending file (100%)\n";
             
            $this->checkChecksum($loaded_params, $item_id);

            $this->soapResult($params, $soap_result, array(), $loaded_params);
        }
        return $soap_result;
    }
}

?>
