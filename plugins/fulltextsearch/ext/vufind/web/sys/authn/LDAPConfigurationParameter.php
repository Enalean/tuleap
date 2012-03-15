<?php
require_once 'ConfigurationReader.php';

class LDAPConfigurationParameter {

    private $ldapParameter;

     public function __construct($configurationFilePath = ''){
        $this->configurationFilePath = $configurationFilePath;
    }

    public function getParameter(){
        $this->getFullSectionParameters();
        $this->checkIfMandatoryParametersAreSet();
        $this->convertParameterValuesToLowercase();
        return $this->ldapParameter;
    }

     private function getFullSectionParameters(){
        $configurationReader = new ConfigurationReader($this->configurationFilePath);
        $this->ldapParameter = $configurationReader->readConfiguration("LDAP");
    }

    private function checkIfMandatoryParametersAreSet(){
        if(empty($this->ldapParameter['host']) ||
           empty($this->ldapParameter['port']) ||
           empty($this->ldapParameter['basedn']) ||
           empty($this->ldapParameter['username'])){
            throw new InvalidArgumentException("One or more LDAP parameter are missing. Check your config.ini!");
        }
    }

    private function convertParameterValuesToLowercase(){
        foreach($this->ldapParameter as $index => $value){
            // Don't lowercase the bind credentials -- they may be case sensitive!
            if ($index != 'bind_username' && $index != 'bind_password') {
                $this->ldapParameter[$index] = strtolower($value);
            }
        }
    }


}
?>
