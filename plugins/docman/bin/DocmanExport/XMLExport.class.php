<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require 'Docman_XMLExport.class.php';

/**
 * Description of XMLExportclass
 */
class XMLExport
{
    protected $archiveName;
    protected $groupId;
    protected $dataPath;
    protected $packagePath;
    protected $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = new WrapperLogger($logger, 'Export Docman');
    }

    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    public function setArchiveName($name)
    {
        $this->archiveName = $name;
    }

    public function setPackagePath($path)
    {
        $this->packagePath = $path;
    }

    public function createDomDocument()
    {
        $impl = new DOMImplementation();
        $dtd = $impl->createDocumentType('docman', '', HTTPRequest::instance()->getServerUrl() . '/plugins/docman/docman-1.0.dtd');
        $doc = $impl->createDocument('', '', $dtd);
        $doc->encoding     = 'UTF-8';
        $doc->standalone   = 'no';
        $doc->version      = '1.0';
        $doc->formatOutput = true;
        return $doc;
    }

    public function dumpPackage()
    {
        $this->logger->info("Exporting documents of project [" . $this->groupId . "] in [" . $this->packagePath . "]");
        if ($this->createDirectories()) {
            $doc = $this->dump();
            $doc->save($this->packagePath . '/' . $this->archiveName . '.xml');
            $this->logger->info("Documents of project [" . $this->groupId . "] dumped in [" . $this->packagePath . "]");
        }
    }

    public function dump()
    {
        $doc = $this->createDomDocument();
        $this->appendDocman($doc);
        return $doc;
    }

    public function appendDocman($doc)
    {
        $docmanExport = new Docman_XMLExport($this->logger);
        $docmanExport->setGroupId($this->groupId);
        $docmanExport->setDataPath($this->dataPath);
        $doc->appendChild($docmanExport->getXML($doc));
    }

    private function createDirectories()
    {
        return $this->createDirectory($this->packagePath) &&
               $this->createDirectory($this->packagePath . '/' . $this->archiveName);
    }

    private function createDirectory($directoryPath)
    {
        try {
            if (is_dir($directoryPath)) {
                if (!is_writable($directoryPath)) {
                    throw new DocmanExportException("Folder [" . $directoryPath . "] already exist and is not writable");
                } else {
                    return true;
                }
            }

            $parentDirectory = dirname($directoryPath);
            if (!is_dir($parentDirectory)) {
                throw new DocmanExportException("Folder [" . $parentDirectory . "] does not exist");
                return false;
            }

            if (!is_writable($parentDirectory)) {
                throw new DocmanExportException("Folder [" . $parentDirectory . "] is not writable");
                return false;
            }

            $dirCreated = mkdir($directoryPath, 0755, true);
            if ($dirCreated == true) {
                $this->dataPath = $directoryPath;
                $this->logger->info("Folder [" . $directoryPath . "] created for project [" . $this->groupId . "]");
                return true;
            } else {
                throw new DocmanExportException("Unable to create folder [" . $directoryPath . "]");
                return false;
            }
        } catch (Exception $exception) {
            $this->logger->error("Unable to create folder [" . $directoryPath . "] for project [" . $this->groupId . "] Error message: " . $exception->getMessage());
            throw new DocmanExportException("Unable to create folder [" . $directoryPath . "] for project [" . $this->groupId . "] Error message: " . $exception->getMessage());
            return false;
        }
    }
}
