<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFUser
 */

require_once('common/plugin/PluginManager.class.php');
require_once('PluginSalomeUserDao.class.php');

class SalomeTMFUser {

    /**
     * @var int $id the ID of the salome user
     */
    var $id;
    
    /**
     * @var string $login the login of the salome user
     */
    var $login;
    
    /**
     * @var string $lastname the lastname of the salome user
     */
    var $lastname;
    
    /**
     * @var string $firstname the firstname of the salome user
     */
    var $firstname;
    
    /**
     * @var string $description the description of the salome user
     */
    var $description;
    
    /**
     * @var string $email the email of the salome user
     */
    var $email;
    
    /**
     * @var string $phonenumber the phonenumber of the salome user
     */
    var $phonenumber;
    
    /**
     * Construct a user from her ID or from her row
     */
    function SalomeTMFUser($salome_user_id, $row = false) {
        if (! $row) {
            $sum =& SalomeTMFUserManager::instance();
            $u = $sum->getSalomeUserFromSalomeUserID($salome_user_id);
            $this->id = $u->getID();
            $this->login = $u->getLogin();
            $this->lastname = $u->getLastName();
            $this->firstname = $u->getFirstName();
            $this->description = $u->getDescription();
            $this->email = $u->getEmail();
            $this->phonenumber = $u->getPhoneNumber();
        } else {
            $this->id = $row['id_personne'];
            $this->login = $row['login_personne'];
            $this->lastname = $row['nom_personne'];
            $this->firstname = $row['prenom_personne'];
            $this->description = $row['desc_personne'];
            $this->email = $row['email_personne'];
            $this->phonenumber = $row['tel_personne'];
        }
    }
    
    function getID() {
        return $this->id;
    }
    
    function getLogin() {
        return $this->login;
    }
    
    function getLastName() {
        return $this->lastname;
    }
    
    function getFirstName() {
        return $this->firstname;
    }
    
    function getDescription() {
        return $this->description;
    }
    
    function getEmail() {
        return $this->email;
    }
    
    function getPhoneNumber() {
        return $this->phonenumber;
    }
    
}

?>
