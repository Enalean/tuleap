<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_CreateDocument extends CLI_Action {
    
    function CLI_Action_Docman_CreateDocument() {
        $this->CLI_Action('createDocument', 'Create a document');
        $this->setSoapCommand('createDocmanDocument');
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
            'name'           => 'ordering',
            'description'    => '--ordering=<begin|end>     Place where the new document will be hosted'
        ));
        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<local_file_location>|<url>|<WikiPage>|<raw content>     content of the document, according to the type of the document'
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
        if ($loaded_params['soap']['type'] == 'file') {
            if (!file_exists($loaded_params['soap']['content'])) {
                exit_error("File '". $loaded_params['soap']['content'] ."' doesn't exist");
            } else if (!($fh = fopen($loaded_params['soap']['content'], "rb"))) {
                exit_error("Could not open '". $loaded_params['soap']['content'] ."' for reading");
            } else {
                $contents = file_get_contents($loaded_params['soap']['content']);
                $loaded_params['soap']['content'] = base64_encode($contents);
            }
        }
    }
}

?>
