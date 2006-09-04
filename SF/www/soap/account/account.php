<?php

// fault code constants
define ('login_fault', '3000');

define ('get_user_fault', '3002');
define ('update_user_fault', '3003');
define ('invalid_user_fault', '3004');
define ('user_skill_delete_fault', '3005');  
define ('user_skill_update_fault', '3006');  
define ('user_skill_insert_fault', '3007');  
define ('get_user_skill_inventory_fault', '3008');
define ('get_people_skill_box_fault', '3009');
define ('get_people_skill_level_box_fault', '3010');
define ('get_people_skill_year_box_fault', '3011');
define ('old_pwd_fault', '3012');
define ('inactive_account_fault', '3013');
define ('update_user_pwd_fault', '3014');
define ('add_people_skill_fault', '3015');
define ('get_groups_fault', '3017');

require_once('nusoap/lib/nusoap.php');
require_once('pre.php');
require_once('timezones.php');
require_once('session.php'); 
require_once('common/tracker/ArtifactType.class');
require_once('Group.class');


//
// Type definition
//
$server->wsdl->addComplexType(
    'UserSkill',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'skill_inventory_id' => array('name' => 'skill_inventory_id', 'type' => 'xsd:int'),
        'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
        'skill_id' => array('name' => 'skill_id', 'type' => 'xsd:int'),
        'skill_level_id' => array('name' => 'skill_level_id', 'type' => 'xsd:int'),
        'skill_year_id' => array('name' => 'skill_year_id', 'type' => 'xsd:int')
    )
);

$server->wsdl->addComplexType(
    'UserSkillInventory',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:UserSkill[]')
    ),
    'tns:UserSkill'
);

$server->wsdl->addComplexType(
    'PeopleSkill',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'skill_id' => array('name' => 'skill_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'PeopleSkillBox',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:PeopleSkill[]')
    ),
    'tns:PeopleSkill'
);

$server->wsdl->addComplexType(
    'TimezoneBox',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'xsd:string[]')
    ),
    'xsd:string'
);

$server->wsdl->addComplexType(
    'PeopleSkillLevel',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'skill_level_id' => array('name' => 'skill_level_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'PeopleSkillLevelBox',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:PeopleSkillLevel[]')
    ),
    'tns:PeopleSkillLevel'
);

$server->wsdl->addComplexType(
    'PeopleSkillYear',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'skill_year_id' => array('name' => 'skill_year_id', 'type' => 'xsd:int'),
        'name' => array('name' => 'name', 'type' => 'xsd:string')
    )
);

$server->wsdl->addComplexType(
    'PeopleSkillYearBox',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:PeopleSkillYear[]')
    ),
    'tns:PeopleSkillYear'
);

//
// Function definition
//
$server->register('getUserSkillInventory',		       // method name
    array('sessionKey' => 'xsd:string',		       // input parameters	
        'user_id' => 'xsd:int'
    ),		    
    array('return'   => 'tns:UserSkillInventory'),       // output parameters
    $uri,			       // namespace
    $uri.'#getUserSkillInventory',	       // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'Get User Skill Inventory By Id'	               // documentation
);

$server->register('getPeopleSkillBox',		       // method name
    array('sessionKey' => 'xsd:string'),                 // input parameters
    array('return'   => 'tns:PeopleSkillBox'),           // output parameters
    $uri,			       // namespace
    $uri.'#getPeopleSkillBox',	       // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'Get People Skill Box'                               // documentation
);

$server->register('getPeopleSkillLevelBox',		       // method name
    array('sessionKey' => 'xsd:string'),                 // input parameters
    array('return'   => 'tns:PeopleSkillLevelBox'),      // output parameters
    $uri,			       // namespace
    $uri.'#getPeopleSkillLevelBox',       // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'Get People Skill Level Box'                         // documentation
);

$server->register('getPeopleSkillYearBox',		       // method name
    array('sessionKey' => 'xsd:string'),		       // input parameters
    array('return'   => 'tns:PeopleSkillYearBox'),       // output parameters
    $uri,			       // namespace
    $uri.'#getPeopleSkillYearBox',	       // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'Get People Skill Year Box'                          // documentation
);

$server->register('addToPeopleSkills',		       // method name
    array('sessionKey' => 'xsd:string',		       // input parameters 
        'user_id' => 'xsd:int',                     
        'skill_name' => 'xsd:string'
    ),		                            
    array(),                                             // output parameters
    $uri,			       // namespace
    $uri.'#addToPeopleSkills',            // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'add Skill to People Skills (For CodeX Admin only)'  // documentation
);

$server->register('getTimezoneBox',			       // method name
    array('sessionKey' => 'xsd:string'),		       // input parameters	      
    array('return'   => 'tns:TimezoneBox'),   	       // output parameters
    $uri,			       // namespace
    $uri.'#getTimezoneBox',               // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'get Timezone Box'     			       // documentation
);

$server->register('updateUserSkillInventory',		       // method name
    array('sessionKey' => 'xsd:string',		       // input parameters	      
        'userSkillInventory' => 'tns:UserSkillInventory'
    ),                                   
    array(),					       // output parameters
    $uri,			       // namespace
    $uri.'#updateUser',   		       // soapaction
    'rpc',					       // style
    'encoded',					       // use
    'update User Skill Inventory'		  	       // documentation
);

//
// Function implementation
//

function updateUserSkillInventory($sessionKey, $userSkillInventory) {
    if (session_continue($sessionKey)){
        // suppression de la table people_skill_inventory les competences qui ont ete supprime
        // en mode off-line
        if (is_array($userSkillInventory)) {
            $count = count($userSkillInventory);
        } else {
            $count = 0;
        }

        $user_id = session_get_userid();
        $sql = "SELECT * FROM people_skill_inventory WHERE user_id=".$user_id;
        $result = db_query($sql);
        $rows = db_numrows($result);
        for ($i=0; $i < $rows; $i++) {
            $bool = false;
            for ($j=0; (($j < $count) && (!$bool)); $j++) {
                $userSkill = $userSkillInventory[$j];
                if (db_result($result,$i,'skill_id') == $userSkill['skill_id']) {
                    $bool = true;
                }
            }
            if (!$bool) {
                $sql="DELETE FROM people_skill_inventory WHERE user_id='".$user_id."' AND skill_inventory_id=".db_result($result,$i,'skill_inventory_id');
                $result=db_query($sql);
                if (!$result || db_affected_rows($result) < 1) {
                    return new soap_fault(user_skill_delete_fault,'updateUserSkillInventory','User Skill Delete FAILED', db_error());
                }
            }
        }
        // ajout et modification des competences qui ont ete ajoutes ou modifies en mode off-line
        for ($i=0; $i < $count; $i++) {
            $userSkill = $userSkillInventory[$i];
            $sql = "SELECT * FROM people_skill_inventory WHERE (user_id='".$user_id."') AND (skill_id='".$userSkill['skill_id']."')";
            $result = db_query($sql);
            $rows = db_numrows($result);
            if (!$result || db_numrows($result) < 1 ) {
                //skill not already in inventory
                $sql="INSERT INTO people_skill_inventory (user_id,skill_id,skill_level_id,skill_year_id) ".
                    "VALUES (".$userSkill['user_id'].",".$userSkill['skill_id'].",".$userSkill['skill_level_id'].",".$userSkill['skill_year_id'].")";
                $result=db_query($sql);
                if (!$result || db_affected_rows($result) < 1) {
                    return new soap_fault(user_skill_insert_fault,'updateUserSkillInventory','ERROR inserting into skill inventory', db_error());
                }
            } else {
                $sql = "UPDATE people_skill_inventory SET";
                $bool = false;
                for ($j=0; $j < $rows; $j++) {
                    if ($userSkill['skill_level_id'] != db_result($result,$j,'skill_level_id')) {
                        $bool = true;
                        $sql .= " skill_level_id =".$userSkill['skill_level_id'];  
                    }
                    if ($userSkill['skill_year_id'] != db_result($result,$j,'skill_year_id')) {
                        $bool = true;
                        $sql .= " skill_year_id =".$userSkill['skill_year_id'];  
                    }
                    $sql .= " WHERE (skill_inventory_id=".$userSkill['skill_inventory_id'].") AND (user_id=".$userSkill['user_id'].")";
                    if ($bool) {   
                        $result2= db_query($sql); 
                        if (!$result || db_affected_rows($result) < 1) {
                            return new soap_fault(user_skill_update_fault,'updateUserSkillInventory', 'User Skill update FAILED', db_error());
                        }
                    }
                }
            }
        }
    } else {
        return new soap_fault(invalid_session_fault,'updateUserSkillInventory','Invalid Session ','');
    }
}

function getUserSkillInventory($sessionKey, $uid) {
    if (session_continue($sessionKey)){
        $userSkillInventory = array();
        $sql = "SELECT * FROM people_skill_inventory WHERE user_id='$uid'";
        $result = db_query($sql);
        $rows = db_numrows($result);
        //if (!$result || $rows < 1) {
        if (!$result) {
            return new soap_fault (get_user_skill_inventory_fault,'getUserSkillInventory','Could Not Get Skill Inventory',db_error());
        } else {
            for ($i=0; $i < $rows; $i++) {
                $userSkillInventory[] = user_skill_to_soap($result, $i);
            }
            return new soapval('return', 'tns:UserSkillInventory', $userSkillInventory);
        }
    } else {
        return new soap_fault(invalid_session_fault,'getUserSkillInventory','Invalid Session ','');
    }
}

function getPeopleSkillBox($sessionKey) {
    if (session_continue($sessionKey)) {
        $PEOPLE_SKILL = array();
        $sql = "SELECT * FROM people_skill ORDER BY skill_id ASC";
        $result = db_query($sql);
        $rows = db_numrows($result);
        if (!$result || $rows < 1) {
           return new soap_fault (get_people_skill_box_fault,'getPeopleSkillBox','Could Not Get People Skill Box',db_error());
        } else {
            for ($i=0; $i < $rows; $i++) {
                $PEOPLE_SKILL[] = people_skill_to_soap($result, $i);
            }
            return new soapval('return', 'tns:PeopleSkillBox', $PEOPLE_SKILL);
        }
    } else {
        return new soap_fault(invalid_session_fault,'getPeopleSkillBox','Invalid Session ','');
    }
}

function getPeopleSkillLevelBox($sessionKey) {
    if (session_continue($sessionKey)){
        $PEOPLE_SKILL_LEVEL = array();
        $sql    = "SELECT * FROM people_skill_level ORDER BY skill_level_id ASC";
        $result = db_query($sql);
        $rows   = db_numrows($result);
        if (!$result || $rows < 1) {
            return new soap_fault (get_people_skill_level_box_fault,'getPeopleSkillLevelBox','Could Not Get People Skill Level Box',db_error());
        } else {
            for ($i=0; $i < $rows; $i++) {
                $PEOPLE_SKILL_LEVEL[] = people_skill_level_to_soap($result, $i);
            }
            return new soapval('return', 'tns:PeopleSkillLevelBox', $PEOPLE_SKILL_LEVEL);
        }	
    } else {
       return new soap_fault(invalid_session_fault,'getPeopleSkillLevelBox','Invalid Session ','');
    }
}

function getPeopleSkillYearBox($sessionKey) {
    if (session_continue($sessionKey)){
        $PEOPLE_SKILL_YEAR = array();
        $sql = "SELECT * FROM people_skill_year ORDER BY skill_year_id ASC";
        $result = db_query($sql);
        $rows = db_numrows($result);
        if (!$result || $rows < 1) {
            return new soap_fault (get_people_skill_year_box_fault,'getPeopleSkillYearBox','Could Not Get People Skill Year Box',db_error());
        } else {
            for ($i=0; $i < $rows; $i++) {
                $PEOPLE_SKILL_YEAR[] = people_skill_year_to_soap($result, $i);
            }
            return new soapval('return', 'tns:PeopleSkillYearBox', $PEOPLE_SKILL_YEAR);
        }
    } else {
        return new soap_fault(invalid_session_fault,'getPeopleSkillYearBox','Invalid Session ','');
    }
}

function updateUserPassword($uid, $old_pwd, $new_pwd) {
    $res = db_query("SELECT user_pw, status FROM user WHERE user_id=" . $uid);
    $row_pw = db_fetch_array();
    if ($row_pw[user_pw] != md5($old_pwd)) {
        return new soap_fault(invalid_old_pwd_fault,'updateUserPassword','Old password is incorrect.', ''); 
    }

    if (($row_pw[status] != 'A')&&($row_pw[status] != 'R')) {
        return new soap_fault(inactive_account_fault,'updateUserPassword','Account must be active to change password.', ''); 
    }
    
    if (!account_pwvalid($new_pwd)) {
        return new soap_fault(invalid_new_pwd_fault,'updateUserPassword','Password must be at least 6 characters.', ''); 
    }
    
    // if we got this far, it must be good
    if (!account_set_password($uid, $new_pwd) ) {
        return new soap_fault(update_user_pwd_fault,'updateUserPassword','Internal error: Could not update password.', db_error()); 	
    }
}

function addToPeopleSkills($sessionKey, $uid, $skill_name) {
    if (session_continue($sessionKey)){
        if (user_ismember(1,'A')) {
            $sql="INSERT INTO people_skill (name) VALUES ('$skill_name')";
            $result=db_query($sql);
            if (!$result) {
                return new soap_fault(add_people_skill_fault,'addToPeopleSkills','Error inserting value', db_error());
            }
        } else {
            return new soap_fault(permission_denied_fault,'addToPeopleSkills','Permission Denied', '');
        }
    } else {
        return new soap_fault(invalid_session_fault,'addToPeopleSkills','Invalid Session ','');
    }
}

function getTimezoneBox($sessionKey) {
    global $TZs;
    if (session_continue($sessionKey)){
        return new soapval('return', 'tns:TimezoneBox', $TZs);
    } else {
        return new soap_fault(invalid_session_fault,'logout','Invalid Session','');
    }
}

function user_skill_to_soap($result, $i) {
    $return = array();
    $return = array(
        'skill_inventory_id' => db_result($result,$i,'skill_inventory_id'),
        'user_id' => db_result($result,$i,'user_id'),
        'skill_id' => db_result($result,$i,'skill_id'),
        'skill_level_id' => db_result($result,$i,'skill_level_id'),
        'skill_year_id' => db_result($result,$i,'skill_year_id')
    );
    return $return;
}

function people_skill_to_soap($result, $i) {
    $return = array();
    $return = array(
        'skill_id'  => db_result($result,$i,'skill_id'),
        'name'      => db_result($result,$i,'name')
    );
    return $return;
}

function people_skill_level_to_soap($result, $i) {
    $return = array();
    $return = array(
        'skill_level_id'  => db_result($result,$i,'skill_level_id'),
        'name'      => db_result($result,$i,'name')
    );
    return $return;
}

function people_skill_year_to_soap($result, $i) {
    $return = array();
    $return = array(
        'skill_year_id'  => db_result($result,$i,'skill_year_id'),
        'name'           => db_result($result,$i,'name')
    );
    return $return;
}

?>
