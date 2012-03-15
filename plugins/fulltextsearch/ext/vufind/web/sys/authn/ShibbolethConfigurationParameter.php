<?php
require_once 'ConfigurationReader.php';

class ShibbolethConfigurationParameter {
    
    private $configurationFilePath;
    private $userAttributes;
    
    public function __construct($configurationFilePath = ''){
        $this->configurationFilePath = $configurationFilePath;
    }
    
    public function getUserAttributes(){
        $this->getFullSectionParameters();
        $this->checkIfUsernameExists();
        $this->filterFullSectionParameter();
        $this->sortUserAttributes();
        $this->checkIfAnyAttributeValueIsEmpty();
        $this->checkIfAtLeastOneUserAttributeIsSet();
        return $this->userAttributes;
    }
    
    private function getFullSectionParameters(){
        $configurationReader = new ConfigurationReader($this->configurationFilePath);
        $this->userAttributes = $configurationReader->readConfiguration("Shibboleth");
    }
    
    private function checkIfUsernameExists(){
        if(empty($this->userAttributes['username'])){
            throw new UnexpectedValueException("Username is missing in your configuration file : '" . $this->configurationFilePath . "'");
        }
    }
    
    private function filterFullSectionParameter(){
        $filterPatternAttribute = "/userattribute_[0-9]{1,}/";
        $filterPatternAttributeValue = "/userattribute_value_[0-9]{1,}/";
        foreach($this->userAttributes as $key => $value){
            if(!preg_match($filterPatternAttribute, $key) && 
               !preg_match($filterPatternAttributeValue, $key) && 
               $key != "username"){
                unset($this->userAttributes[$key]);
            }
        }
    }
    
    private function sortUserAttributes(){        
        $filterPatternAttributes = "/userattribute_[0-9]{1,}/";
         $sortedUserAttributes['username'] = $this->userAttributes['username'];
        foreach($this->userAttributes as $key => $value){
            if(preg_match($filterPatternAttributes, $key)){
                $sortedUserAttributes[$value] = $this->getUserAttributeValue(substr($key, 14));
            }
        }
        $this->userAttributes = $sortedUserAttributes;
    }
    
    private function getUserAttributeValue($userAttributeNumber){
        $filterPatternAttributeValues = "/userattribute_value_[". $userAttributeNumber . "]{1,}/";
        foreach($this->userAttributes as $key => $value){
            if(preg_match($filterPatternAttributeValues, $key)){
               return $value;
            }
        }
    }
    
    private function checkIfAnyAttributeValueIsEmpty(){
        foreach($this->userAttributes as $key => $value){
            if(empty($value)){
                throw new UnexpectedValueException("User attribute value of " . $key. " is missing!");    
            }
        }
    }
    
    private function checkIfAtLeastOneUserAttributeIsSet(){
        if(count($this->userAttributes) == 1){
            throw new UnexpectedValueException("You must at least set one user attribute in your configuration file '" . $this->configurationFilePath  . "'.", 3);
        }
    }
}


?>