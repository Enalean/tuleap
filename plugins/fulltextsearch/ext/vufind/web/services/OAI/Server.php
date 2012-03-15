<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

class Server extends Action
{
    function launch()
    {
        header('Content-type: text/xml');
        
        // Allow for POST in addition to GET
        $_GET = array_merge($_GET, $_POST);

        // Call Verb
        $server = new OAIServer();
        if (isset($_GET['verb'])) {
            $action = $_GET['verb'];
            if (is_callable(array($server, $action))) {
                $server->$action();
            } else {
                $server->Error('<error code="badVerb">Illegal OAI Verb</error>');
            }
        } else {
            $server->Error('<error code="badArgument">Missing Verb Argument</error>');
        }
    }
}

class OAIServer
{
    var $label;
    var $earliest = '2006-06-01T00:00:00Z';
    var $verb;
    var $from;
    var $until;
    var $identifier;
    var $set;
    var $resumptionToken;
    var $metadataPrefix;
    
    private $db;
    
    function Server()
    {
        global $configArray;
        
        $this->label = $configArray['OAI']['identifier'];
    
        // Check Parameters
        foreach ($_GET as $param => $val) {
            if ((!property_exists($this, $param)) && (($param != 'module') && ($param != 'action'))) {
                $this->Error('<error code="badVerb">Invalid Verb</error>');
            }
        }

        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $this->db = new $class($configArray['Index']['url']);
        if ($configArray['System']['debug']) {
            $this->db->debug = true;
        }

        // Define Parameters
        $this->from = (isset($_GET['from'])) ? $_GET['from'] : null;
        $this->until = (isset($_GET['until'])) ? $_GET['until'] : null;
        $this->set = (isset($_GET['set'])) ? str_replace(':', '/', $_GET['set']) : null;
        $this->resumptionToken = (isset($_GET['resumptionToken'])) ? $_GET['resumptionToken'] : null;
        $this->metadataPrefix = (isset($_GET['metadataPrefix'])) ? $_GET['metadataPrefix'] : null;
        if (isset($_GET['identifier'])) {
            if (stristr($_GET['identifier'], $this->label)) {
                $id = substr($_GET['identifier'], strlen($this->label));
                if (!is_file($configArray['Data']['src'] . "/$id")) {
                    $this->Error('<error code="idDoesNotExist">Unknown Record</error>');
                } else {
                    $this->identifier = $id;
                }
            } else {
                $this->Error('<error code="idDoesNotExist">Unknown Record: Malformed Name</error>');
            }
        }
        
        // Evaluate Parameters
        if ($this->from) {
            if (!$this->until)
                $this->Error('<error code="badArgument">Missing Until</error>');
            if (!strtotime($this->from))
                $this->Error('<error code="badArgument">Bad Date Format</error>');
            if (strtotime($this->from) > strtotime($this->until))
                $this->Error('<error code="badArgument">End date must be after start date</error>');
            if (strtotime($this->from) < strtotime($this->earliest))
                $this->Error('<error code="badArgument">Start date must be after earliest date</error>');
            if (!substr($this->from, 11) == 'T') 
                $this->Error('<error code="badArgument">From Date must be YYYY-MM-DDThh:mm:ssZ format</error>');
        }
        if ($this->until) {
            if (!strtotime($this->until))
                $this->Error('<error code="badArgument">Bad Until Date Format</error>');
            if (!substr($this->until, 11) == 'T') 
                $this->Error('<error code="badArgument">Until Date must be YYYY-MM-DDThh:mm:ssZ format</error>');
            //if (strtotime($this->until) < strtotime($this->earliest))
            //    $this->Error('<error code="badArgument">Until Date must be after earliest date</error>');
        }
        

        if ($this->metadataPrefix && !stristr($this->metadataPrefix, 'oai_dc')) {
            $this->Error('<error code="cannotDisseminateFormat">Unknown Format</error>');
        }
        if ($this->resumptionToken) {
            $this->Error('<error code="badResumptionToken">Resumption Tokens are Not Supported</error>');
        }
    }
    
    function Error($error = '<error/>')
    {
        $xsl = new XSLTProcessor();

        $style = new DOMDocument;
        $style->load('services/OAI/xsl/oai-error.xsl');
        $xsl->importStyleSheet($style);
        $xsl->registerPHPFunctions();

        $xml = new DOMDocument;
        $xml->loadXML($error);
        
        echo $xsl->transformToXML($xml);
        exit();
    }
    
    function Identify()
    {
        global $configArray;
    
        $xsl = new XSLTProcessor();

        $style = new DOMDocument;
        $style->load('services/OAI/xsl/oai-identify.xsl');
        $xsl->importStyleSheet($style);
        $xsl->registerPHPFunctions();
        $xsl->setParameter('', 'identifier', $this->label);
        $xsl->setParameter('', 'repoName', $configArray['Site']['title']);
        $xsl->setParameter('', 'baseUrl', $configArray['Site']['url'] . '/OAI/Server');
        $xsl->setParameter('', 'email', $configArray['Site']['email']);

        $xml = new DOMDocument;
        $xml->loadXML('<empty/>');
        
        echo $xsl->transformToXML($xml);
    }
    
    function GetRecord()
    {
        global $configArray;
    
        // Processs Parameters
        if (!$this->metadataPrefix) {
            if (!$this->resumptionToken) {
                $this->Error('<error code="badArgument">Missing Metadata Prefix</error>');
            }
        }
        if (!$this->identifier) {
            $this->Error('<error code="badArgument">Missing Identifier</error>');
        }

        // Develop SET Path
        $path = explode('/', $this->identifier);
        array_pop($path);
        $path = implode(':', $path);
        
        $style = new DOMDocument;
        $style->load('services/OAI/xsl/oai-getrecord.xsl');
        
        $xsl = new XSLTProcessor();
        $xsl->importStyleSheet($style);
        $xsl->setParameter('', 'identifier', $this->label);
        $xsl->setParameter('', 'set', 'collection:' . $path);
        $xsl->registerPHPFunctions();

        $record = file_get_contents($configArray['Data']['src'] . '/' . $this->identifier);
        
        $xml = new DOMDocument;
        $xml->loadXML($record);
        
        echo $xsl->transformToXML($xml);        
    }
    
    function ListIdentifiers()
    {
        global $configArray;

        // Processs Parameters
        if (!$this->metadataPrefix) {
            if (!$this->resumptionToken) {
                $this->Error('<error code="badArgument">Missing Metadata Prefix</error>');
            }
        }
        if ($this->resumptionToken) {
            $this->Error('<error code="badArgument">This repository does not support Resumption Tokens</error>');
        }

        // Begin XML Creation
        $style = new DOMDocument;
        $style->load('xsl/oai-listrecords.xsl');

        $xsl = new XSLTProcessor();
        $xsl->importStyleSheet($style);
        $xsl->setParameter('', 'label', $this->label);
        $xsl->setParameter('', 'set', 'collection:' . $path);
        $xsl->registerPHPFunctions();

        // Query Records
        if ($this->set) {
            $result = $this->db->query('*:*', "format:$this->set", null, 0, null, null, 'id, format');
        } else {
            $result = $this->db->query('*:*', null, null, 0, null, null, 'id, format');
        }

        $xml = new DOMDocument;
        $xml->loadXML($result);

        $result = $xsl->transformToXML($xml);
        $unxml = new XML_Unserializer();
        $unxml->unserialize($result);
        $data = $unxml->getUnserializedData();
        if (isset($data['ListRecords']) && !$data['ListRecords']) {
            $this->Error('<error code="noRecordsMatch">No Records Found</error>');
        } else {
            echo $result;
        }
    }
    
    function ListMetadataFormats()
    {
        $xsl = new XSLTProcessor();

        $style = new DOMDocument;
        $style->load('services/OAI/xsl/oai-listmetadataformats.xsl');
        $xsl->importStyleSheet($style);
        $xsl->registerPHPFunctions();

        $xml = new DOMDocument;
        $xml->loadXML('<empty/>');
        
        echo $xsl->transformToXML($xml);
    }
    
    function ListRecords()
    {
        global $configArray;
        
        // Processs Parameters
        if (!$this->metadataPrefix) {
            if (!$this->resumptionToken) {
                $this->Error('<error code="badArgument">Missing Metadata Prefix</error>');
            }
        }
        if ($this->resumptionToken) {
            $this->Error('<error code="badArgument">This repository does not support Resumption Tokens</error>');
        }
        
        // Begin XML Creation
        $style = new DOMDocument;
        $style->load('xsl/oai-listrecords.xsl');
        
        $xsl = new XSLTProcessor();
        $xsl->importStyleSheet($style);
        $xsl->setParameter('', 'label', $this->label);
        $xsl->setParameter('', 'set', 'collection:' . $path);
        $xsl->registerPHPFunctions();

        // Query Records
        if ($this->set) {
            $result = $this->db->query('*:*', "format:$this->set");
        } else {
            $result = $this->db->query('*:*');
        }

        $xml = new DOMDocument;
        $xml->loadXML($result);

        $result = $xsl->transformToXML($xml);
        $unxml = new XML_Unserializer();
        $unxml->unserialize($result);
        $data = $unxml->getUnserializedData();
        if (isset($data['ListRecords']) && !$data['ListRecords']) {
            $this->Error('<error code="noRecordsMatch">No Records Found</error>');
        } else {
            echo $result;
        }
    }
    
    function ListSets()
    {
        global $configArray;

        // Processs Parameters
        if (!$this->metadataPrefix) {
            if (!$this->resumptionToken) {
                $this->Error('<error code="badArgument">Missing Metadata Prefix</error>');
            }
        }
        if ($this->resumptionToken) {
            $this->Error('<error code="badArgument">This repository does not support Resumption Tokens</error>');
        }

        // Begin XML Processing
        $xsl = new XSLTProcessor();

        $style = new DOMDocument;
        $style->load('services/OAI/xsl/oai-listidentifiers.xsl');
        $xsl->importStyleSheet($style);
        $xsl->registerPHPFunctions();

        if ($this->set) {
            $result = $this->db->query('*:*', null, null, 0, null, array('limit' => 10, 'field' => array('format')), 'score');
        } else {
            $result = $this->db->query('*:*', null, null, 0, null, array('limit' => 10, 'field' => array('format')), 'score');
        }

        $xml = new DOMDocument;
        $xml->loadXML($result);

        echo $xsl->transformToXML($xml);
    }
}

function getISODate($str)
{
    $stamp = strtotime($str);
    return date('Y-m-d\TH:i:s\Z', $stamp);
}
?>