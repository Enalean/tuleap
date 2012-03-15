<?php
require_once 'IOException.php';
require_once 'FileParseException.php';

class ConfigurationReader {
    
    private $pathToConfigurationFile;
    private $configurationFileContent;
    private $sectionName;
    
    public function __construct($pathToConfigurationFile = ''){
        $this->setPathOfConfigurationFileIfParameterIsEmpty($pathToConfigurationFile);
        $this->checkIfConfigurationFileExists();
    }
    
    private function setPathOfConfigurationFileIfParameterIsEmpty($pathToConfigurationFile){
        if(empty($pathToConfigurationFile) || $pathToConfigurationFile == ''){
            $actualPath = dirname(__FILE__);
            // Handle forward and back slashes for Windows/Linux compatibility:
            $this->pathToConfigurationFile = str_replace(array("/sys/authn", "\sys\authn"), 
                array("/conf/config.ini", "\conf\config.ini"), $actualPath);
                
        } else {
            $this->pathToConfigurationFile = $pathToConfigurationFile;
        }
    }
    
    private function checkIfConfigurationFileExists(){
        clearstatcache();
        if(!file_exists($this->pathToConfigurationFile)){
            throw new IOException('Missing configuration file ' . $this->pathToConfigurationFile . '.', 1);
        }
    }
    
    public function readConfiguration($sectionName){
        $this->sectionName = $sectionName;
        try {
            $this->configurationFileContent = parse_ini_file($this->pathToConfigurationFile, true);    
        } catch (Exception $exception){
            throw new FileParseException("Error during parsing file '" . $this->pathToConfigurationFile . "'", 2);
        }
        
        $this->checkIfParsingWasSuccesfull();
        $this->checkIfSectionExists();
        return $this->configurationFileContent[$this->sectionName];
    }
    
    private function checkIfParsingWasSuccesfull(){
        if(!is_array($this->configurationFileContent)){
            throw new FileParseException ('Could not parse configuration file ' . $this->pathToConfigurationFile . '.', 3);
        }       
    }
    
    private function checkIfSectionExists(){
        if(empty($this->configurationFileContent[$this->sectionName])){
            throw new UnexpectedValueException ('Section ' . $this->sectionName . ' do not exists! Could not procede.');
        }
    }    
}

?>