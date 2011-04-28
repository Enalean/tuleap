<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require 'Docman_XMLExport.class.php';

/**
 * Description of XMLExportclass
 *
 * @author vm16
 */
class XMLExport {
    protected $archiveName;
    protected $groupId;
    protected $dataPath;
    protected $packagePath;

    public function __construct() {
    }

    public function setGroupId($groupId) {
        $this->groupId = $groupId;
    }

    public function setArchiveName($name) {
        $this->archiveName = $name;
    }

    public function createDomDocument() {
        $impl = new DOMImplementation();
        $dtd = $impl->createDocumentType('docman', '', get_server_url().'/plugins/docman/docman-1.0.dtd');
        $doc = $impl->createDocument('', '', $dtd);
        $doc->encoding     = 'UTF-8';
        $doc->standalone   = 'no';
        $doc->version      = '1.0';
        $doc->formatOutput = true;
        return $doc;
    }

    public function dumpPackage() {
        $this->createDirectories();
        $doc = $this->dump();
        $doc->save($this->packagePath.'/'.$this->archiveName.'.xml');
    }

    public function dump() {
        $doc = $this->createDomDocument();
        $this->appendDocman($doc);
        return $doc;
    }

    public function appendDocman($doc) {
        $docmanExport = new Docman_XMLExport();
        $docmanExport->setGroupId($this->groupId);
        $docmanExport->setDataPath($this->dataPath);
        $doc->appendChild($docmanExport->getXML($doc));
    }

    public function createDirectories() {
        $dirCreated = mkdir($this->archiveName.'/'.$this->archiveName, 0755, true);
        if($dirCreated) {
            $this->packagePath = $this->archiveName;
            $this->dataPath = $this->archiveName.'/'.$this->archiveName;

            //$this->asXML();
            //$this->doc->validate();
            //$this->doc->save($this->archiveName.'/'.$this->archiveName.'.xml');
            //$this->displayStatistics();
        }
    }
}
?>
