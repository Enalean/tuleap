<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_CreateDocument extends CLI_Action {
	
	private $chunk_size = 6000000; // ~6 Mo
	private $current_chunk_offset = 0;
	private $filename;
    
    function CLI_Action_Docman_CreateDocument() {
        $this->CLI_Action('createDocument', 'Create a document');
        $this->addParam(array(
            'name'           => 'parent_id',
            'description'    => '--parent_id=<item_id>     ID of the parent the document will be created in'
        ));
        $this->addParam(array(
            'name'           => 'title',
            'description'    => '--title=<title>     Title of the new folder'
        ));
        $this->addParam(array(
            'name'           => 'description',
            'description'    => '--description=<description>     Description of the new document'
        ));
        $this->addParam(array(
            'name'           => 'type',
            'description'    => '--type=<file|link|wiki|embedded_file>     nature of the document'
        ));
        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<local_file_location>|<url>|<WikiPage>|<raw content>     content of the document, according to the type of the document'
        ));
        $this->addParam(array(
            'name'           => 'ordering',
            'description'    => '--ordering=<begin|end>     Place where the new document will be hosted'
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
        $allowed_types= array("file", "link", "wiki", "embedded_file");      
        if (! isset($type) || !in_array($type, $allowed_types)) {
            echo $this->help();
            exit_error("You must specify the type of the document with the --type parameter, taking the value {".implode(",", $allowed_types)."}");
        }
        return true;
    }
    function validate_content(&$content) {
        if (!isset($content) || trim($content) == '') {
            echo $this->help();
            exit_error("You must specify the content of the document with the --content parameter, according to the document type");
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
    function before_soapCall(&$loaded_params) {
        if (isset($this->filename)) {
            if (!file_exists($this->filename)) {
                exit_error("File '". $this->filename ."' doesn't exist");
            } else if (!($fh = fopen($this->filename, "rb"))) {
                exit_error("Could not open '$this->filename' for reading");
            } else {
            	$chunk_offset = $this->current_chunk_offset++;
        		
        		$contents = file_get_contents($this->filename, null, null, $chunk_offset * $this->chunk_size, $this->chunk_size);
                $loaded_params['soap']['content'] = base64_encode($contents);
                $loaded_params['soap']['chunk_offset'] = $chunk_offset;
                $loaded_params['soap']['chunk_size'] = $this->chunk_size;
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
            if ($this->confirmation($loaded_params)) {
            	
            	// This command will create the document (if it's a file, the first chunk will be sent)
				$this->setSoapCommand('createDocmanDocument');

				if ($loaded_params['soap']['type'] == 'file') {
            		$this->filename = $loaded_params['soap']['content'];
                }
                
				$this->before_soapCall($loaded_params);
				
            	if ($loaded_params['soap']['type'] == 'file') {
					$loaded_params['soap']['file_size'] = filesize($this->filename);
					$loaded_params['soap']['file_name'] = $this->filename;
					
					echo "\rSending file (0%)";
                }
				
                try {
                	$soap_result = $this->soapCall($loaded_params['soap'], $this->use_extra_params());
                } catch (SoapFault $fault) {
                    $GLOBALS['LOG']->add($GLOBALS['soap']->__getLastResponse());
                    exit_error($fault, $fault->getCode());
                }
                
                // If it's a file, send the other chunks
                if ($loaded_params['soap']['type'] == 'file') {
               		$item_id = $soap_result;
	                $filesize = filesize($this->filename);
	                
	                $modulo = $filesize % $this->chunk_size;
	                $chunk_count = ($filesize - $modulo) / $this->chunk_size;
	                if ($modulo != 0) {
	                	$chunk_count++;
	                }
	                
					$this->setSoapCommand('appendFileChunk');

            		$loaded_params['soap'] = array(
            			'group_id' => $loaded_params['soap']['group_id'],
            			'item_id' => $item_id,
            		);
	                
	                while($this->current_chunk_offset < $chunk_count) {
						echo "\rSending file (".intval($this->current_chunk_offset / $chunk_count * 100).'%)';
		                $this->before_soapCall($loaded_params);
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
        }
        return $soap_result;
    }
    
    function checkChecksum(&$loaded_params, $item_id) {
    	$this->setSoapCommand('getFileMD5sum');
		$loaded_params['soap'] = array(
			'group_id' => $loaded_params['soap']['group_id'],
			'item_id' => $item_id,
			'version' => 0,
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
