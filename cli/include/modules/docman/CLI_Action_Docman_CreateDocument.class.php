<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateItem.class.php');

class CLI_Action_Docman_CreateDocument extends CLI_Action_Docman_CreateItem  {

    private $chunk_size = 6000000; // ~6 Mo
    private $current_chunk_offset = 0;
    private $filename;

    function CLI_Action_Docman_CreateDocument() {
        $this->CLI_Action_Docman_CreateItem('createDocument', 'Create a document');

        $this->addParam(array(
            'name'           => 'obsolescence_date',
            'description'    => '--obsolescence_date=<yy-mm-dd|yyyy-mm-dd>    Date when the document will be obsolete',
            'soap'     => true,
        ));
        
        $this->addParam(array(
            'name'           => 'type',
            'description'    => '--type=<file|embedded_file|wiki|link|empty>    nature of the document',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<local_file_location>|<url>|<WikiPage>|<raw content>    content of the document, according to the type of the document',
            'soap'     => false,
        ));
    }

    function validate_parent_id(&$parent_id) {
        if (!isset($parent_id)) {
            echo $this->help();
            exit_error("You must specify the parent ID of the document with the --parent_id parameter");
        }
        return true;
    }
    function validate_title(&$title) {
        if (!isset($title) || trim($title) == '') {
            echo $this->help();
            exit_error("You must specify the title of the document with the --title parameter");
        }
        return true;
    }
    function validate_type(&$type) {
        $allowed_types= array("file", "embedded_file", "wiki", "link", "empty");
        if (! isset($type) || !in_array($type, $allowed_types)) {
            echo $this->help();
            exit_error("You must specify the type of the document with the --type parameter, taking the value {".implode(",", $allowed_types)."}");
        }
        return true;
    }
    function validate_ordering(&$ordering) {
        $allowed_ordering = array("begin", "end");
        if (isset($ordering)) {
            // check that the value is allowed
            if (!in_array($ordering, $allowed_ordering)) {
                echo $this->help();
                exit_error("You must specify the ordering of the document with the --ordering parameter, taking the value {".implode(",", $allowed_ordering)."}");
            }
        } else {
            // $ordering is not set
            $ordering = "begin";
        }
        return true;
    }
    function validate_obsolescence_date(&$date) {
        if (isset($date)) {
            $match = preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', $date, $m);
            if (!$m) {
                echo $this->help();
                exit_error('Obsolete date format must be: yyyy-mm-dd or yy-mm-dd');
            } else {
                $month  = $m[2];
                $day    = $m[3];
                
                if ($month > 12 || $day > 31 || $month < 1 ||  $day < 1) {
                    echo $this->help();
                    exit_error('Obsolescence date format must be: yyyy-mm-dd or yy-mm-dd. Please respect the correct ranges: 1 < mm < 12; 1 < dd < 31');
                }
            }
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

    function after_loadParams(&$loaded_params) {
        parent::after_loadParams($loaded_params);
         
        $type = $loaded_params['others']['type'];
        if ($type != 'empty') {

            if (!isset($loaded_params['others']['content']) || trim($loaded_params['others']['content']) == '') {
                echo $this->help();
                exit_error("You must specify the content of the document with the --content parameter, according to the document type");
            }

            switch ($type) {
                case 'file':
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
                    break;
                case 'embedded_file':
                case 'wiki':
                case 'link':
                    $loaded_params['soap']['content'] = $loaded_params['others']['content']; break;
            }
        }
    }

    function execute($params) {
        $soap_result = null;
        if ($this->module->getParameter($params, array('h', 'help'))) {
            echo $this->help();
        } else {
            $loaded_params = $this->loadParams($params);
            $this->after_loadParams($loaded_params);
            	
            // This command will create the document (if it's a file, the first chunk will be sent)
            $type = $loaded_params['others']['type'];
            switch ($type) {
                case 'file':			$soapCommand = 'createDocmanFile'; break;
                case 'embedded_file':	$soapCommand = 'createDocmanEmbeddedFile'; break;
                case 'wiki':			$soapCommand = 'createDocmanWikiPage'; break;
                case 'link':			$soapCommand = 'createDocmanLink'; break;
                case 'empty':			$soapCommand = 'createDocmanEmptyDocument'; break;
            }
            $this->setSoapCommand($soapCommand);

            try {
                $soap_result = $this->soapCall($loaded_params['soap'], $this->use_extra_params());
            } catch (SoapFault $fault) {
                $GLOBALS['LOG']->add($GLOBALS['soap']->__getLastResponse());
                exit_error($fault, $fault->getCode());
            }

            // If it's a file, send the other chunks
            if ($type == 'file') {
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
            }
            $this->soapResult($params, $soap_result, array(), $loaded_params);

        }
        return $soap_result;
    }

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
}

?>
