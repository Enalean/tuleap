<?php

require_once 'FakePluginDescriptor.php';

class ReleaseVersionComparator {

    public function __construct($prevUri, $curUri) {
        $this->prevUri = $prevUri;
        $this->curUri  = $curUri;
    }

    public function getCurrentVersion($relPath) {
        if (is_dir($this->curUri)) {
            return $this->getVersionContent($this->curUri.$relPath);
        } else {
            return $this->getRemoteVersion($this->curUri.$relPath);
        }
    }

    public function getPreviousVersion($relPath) {
        return $this->getRemoteVersion($this->prevUri.$relPath);
    }

    protected function getRemoteVersion($url) {
        //try {
            $filePath = $this->getRemoteFile($url);
            $v = $this->getVersionContent($filePath);
            unlink($filePath);
            return $v;
            //} catch (Excepti)
    }

    protected function getRemoteFile($url) {
        $name   = tempnam('/tmp', 'codendi_release_');
        $output = array();
        $retVal = false;
        exec('svn cat '.$url.' 2>/dev/null > '.$name, $output, $retVal);
        if ($retVal === 0) {
            return $name;
        }
        unlink($name);
        throw new Exception('Impossible to get remote file: '.$url);
    }
    
    protected function getVersionContent($filePath) {
        return trim(file_get_contents($filePath));
    }

}

class PluginReleaseVersionComparator extends ReleaseVersionComparator {

    public function __construct($prevUri, $curUri, $fpd) {
        parent::__construct($prevUri, $curUri);
        $this->fpd = $fpd;
    }

    public function getPreviousVersion($relPath) {
        try {
            return $this->getRemoteVersion($this->prevUri.$relPath);
        } catch (Exception $e) {
            $pluginRoot = dirname($relPath);
            $pluginName = basename($pluginRoot);
            $path       = $this->fpd->findDescriptor($this->curUri.$pluginRoot);
            $relPath    = substr($path, -(strlen($path)-strlen($this->curUri)));

            // Get descriptor
            $oldDescPath = $this->getRemoteFile($this->prevUri.$relPath);
            $oldDesc     = $this->fpd->getDescriptorFromFile($pluginName, $oldDescPath);
            unlink($oldDescPath);

            return $oldDesc->getVersion();
        }
        throw new Exception('No way to get the previous version number');
    }

}

?>