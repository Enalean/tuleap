<?php

require_once 'FakePluginDescriptor.php';

class ReleaseVersionComparator {
    const COLOR_RED     = "\033[31m";
    const COLOR_GREEN   = "\033[32m";
    const COLOR_NOCOLOR = "\033[0m";

    protected $tmpNames = array();

    public function __construct($prevUri, $curUri) {
        $this->prevUri = $prevUri;
        $this->curUri  = $curUri;
    }

    public function __destruct() {
        foreach ($this->tmpNames as $file) {
            @unlink($file);
        }
    }

    public function iterateOverPaths($paths, $rpms, $verbose=false) {
        $iRpms = array_flip(array_map('strtolower', $rpms));
        foreach ($paths as $path) {
            $versionPath = $path.'/VERSION';            
            $curVersion  = $this->getCurrentVersion($versionPath);
            try {
                $prevVersion = $this->getPreviousVersion($versionPath);
                $this->displayOneLine($path, $curVersion, $prevVersion, $iRpms, $verbose);
            } catch(Exception $e) {
                echo "Impossible to get previous $versionPath. It's normal if it's new in this release. Otherwise, please check\n";
            }
        }
    }

    public function displayOneLine($path, $curVersion, $prevVersion, $iRpms, $verbose) {
        $versionOk = version_compare($curVersion, $prevVersion, '>');
        if ($verbose || !$versionOk) {
            $flag = '';
            $name = basename($path);
            if (isset($iRpms[strtolower($name)])) {
                if ($versionOk) {
                    $flag .= self::COLOR_GREEN;
                } else {
                    $flag .= self::COLOR_RED;
                }
                $flag .= '[RPM] ';
            }
            echo "\t$flag".$path.": ".$curVersion.' (Previous release was: '.$prevVersion.')'.self::COLOR_NOCOLOR.PHP_EOL;        
        }
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
        $filePath = $this->getRemoteFile($url);
        return $this->getVersionContent($filePath);
    }

    protected function getRemoteFile($url) {
        $name   = tempnam('/tmp', 'codendi_release_');
        $this->tmpNames[] = $name;
        $output = array();
        $retVal = false;
        exec('svn cat '.$url.' 2>/dev/null > '.$name, $output, $retVal);
        if ($retVal === 0) {
            return $name;
        }
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
            return $oldDesc->getVersion();
        }
        throw new Exception('No way to get the previous version number');
    }

}

?>