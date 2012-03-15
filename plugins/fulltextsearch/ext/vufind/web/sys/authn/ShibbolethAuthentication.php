<?php
require_once 'PEAR.php';
require_once 'Authentication.php';
require_once 'ShibbolethConfigurationParameter.php';
require_once 'services/MyResearch/lib/User.php';

class ShibbolethAuthentication implements Authentication {

    private $userAttributes;
    private $username;
    
    public function __construct(){
        $shibbolethConfigurationParameter = new ShibbolethConfigurationParameter();
        $this->userAttributes = $shibbolethConfigurationParameter->getUserAttributes();
    }

    public function authenticate(){
        if(!$this->isUsernamePartOfAssertions()){        
            return new PEAR_ERROR('authentication_error_admin');
        }
        foreach($this->userAttributes as $key => $value){
            if($key != 'username'){
                if(!preg_match('/'. $value .'/', $_SERVER[$key])){
                    return new PEAR_ERROR('authentication_error_denied');
                }
            }
        }

        $user = new User();
        $user->username = $_SERVER[$this->userAttributes['username']];       
        $userIsInVufindDatabase = $this->isUserInVufindDatabase($user); 
        $this->synchronizeVufindDatabase($userIsInVufindDatabase, $user);

        return $user;
    }

    private function isUsernamePartOfAssertions(){
        if(isset($_SERVER[$this->userAttributes['username']])){
            return true;
        }
        return false;
    }

    private function isUserInVufindDatabase($user){
        return $user->find(true);
    }

    private function synchronizeVufindDatabase($userIsInVufindDatabase, $user){
        if($userIsInVufindDatabase){
            $user->update();
        } else {
            $user->created = date('Y-m-d');
            $user->insert();
        }
    }
}
?>
