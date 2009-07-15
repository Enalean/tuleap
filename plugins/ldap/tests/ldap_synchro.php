<?php


/**
 * Allow site to gather more information from the LDAP Directory in order to
 * update user account.
 * Following variables are available:
 * $lr   : an LDAPResult object that correspond to current user LDAP info
 * $user : the User object to be updated in the Database
 * 
 * WARNING: this is a complex implementation because this file is used when:
 * - The user log on Codendi in LDAP_UserManager::synchronizeUser
 * - The LDAP<->Codendi synchro is performed in 
 *   LDAP_DirectorySynchronization::ldapSync
 */

// +++ CODENDI MANDATORY
if (isset($user) && $user instanceof User) {
    $userRef = $user->data_array;
}
if (!$userUpdate) $userUpdate = array();
// --- CODENDI MANDATORY

/*
 * Example of implementation: we want to restrict people based on some 
 * attribute:
 */  
$activeEmployeeType = array('st' => true, 'jv' => true);
$employeeType = strtolower($lr->get('employeetype'));
if($employeeType && isset($activeEmployeeType[$employeeType])) {
    if ($userRef['status'] != 'A') {
        $userUpdate['status'] = 'A';
    }
} elseif ($userRef['status'] != 'R') {
    if (isset($GLOBALS['Response'])) {
        $GLOBALS['Response']->addFeedback('info', "Your account is restricted to projects your are member of");
    }
    $userUpdate['status'] = 'R';
}
 

// +++ CODENDI MANDATORY
if (isset($user) && $user instanceof User) {
    if (isset($userUpdate['status'])) {
        $user->setStatus($userUpdate['status']);
    }
}
// --- CODENDI MANDATORY

?>