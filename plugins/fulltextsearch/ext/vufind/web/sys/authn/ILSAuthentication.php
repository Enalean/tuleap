<?php
require_once 'Authentication.php';
require_once 'CatalogConnection.php';

class ILSAuthentication implements Authentication {

    public function authenticate(){
        global $configArray;
        
        $this->username = $_POST['username'];
        $this->password = $_POST['password'];

        if($this->username == '' || $this->password == ''){
            $user = new PEAR_Error('authentication_error_blank');
        } else {
            // Connect to Database
            $catalog = new CatalogConnection($configArray['Catalog']['driver']);

            if ($catalog->status) {
                $patron = $catalog->patronLogin($this->username, $this->password);
                if ($patron && !PEAR::isError($patron)) {
                    $user = $this->processILSUser($patron);
                } else {
                    $user = new PEAR_Error('authentication_error_invalid');
                }
            } else {
                $user = new PEAR_Error('authentication_error_technical');
            }
        } 
        return $user;
    }

    private function processILSUser($info){
        require_once "services/MyResearch/lib/User.php";

        $user = new User();
        $user->username = $info['cat_username'];
        $user->password = $info['cat_password'];
        if ($user->find(true)) {
            $insert = false;
        } else {
            $insert = true;
        }

        $user->firstname    = $info['firstname']    == null ? " " : $info['firstname'];
        $user->lastname     = $info['lastname']     == null ? " " : $info['lastname'];
        $user->cat_username = $info['cat_username'] == null ? " " : $info['cat_username'];
        $user->cat_password = $info['cat_password'] == null ? " " : $info['cat_password'];
        $user->email        = $info['email']        == null ? " " : $info['email'];
        $user->major        = $info['major']        == null ? " " : $info['major'];
        $user->college      = $info['college']      == null ? " " : $info['college'];

        if ($insert) {
            $user->created = date('Y-m-d');
            $user->insert();
        } else {
            $user->update();
        }

        return $user;
    }
}
?>
