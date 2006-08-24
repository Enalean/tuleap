<?php
  // fault code constants
  define ('login_fault', '3000');
  define ('invalid_session_fault', '3001');
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
  define ('permission_denied_fault', '3016');
  define ('get_groups_fault', '3017');
  
    	   
  require_once ('nusoap/lib/nusoap.php');
  require_once ('pre.php');
  require_once ('timezones.php');
  require_once ('session.php'); 
  require_once ('common/tracker/ArtifactType.class');
    
  // Create the server instance
  $server = new soap_server();
  
  // Initialize WSDL support
  $server->configureWSDL('CodeXAccountwsdl', 'urn:CodeXAccountwsdl');
  
  // Register the data structures used by the services
  $server->wsdl->addComplexType(
	'User',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
		'user_name' => array('name' => 'user_name', 'type' => 'xsd:string'),
		'add_date' => array('name' => 'add_date', 'type' => 'xsd:int'),
		'timezone' => array('name' => 'timezone', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'mail_siteupdates' => array('name' => 'mail_siteupdates', 'type' => 'xsd:int'),
		'mail_va' => array('name' => 'mail_va', 'type' => 'xsd:int'),
		'sticky_login' => array('name' => 'sticky_login', 'type' => 'xsd:int'),
		'fontsize' => array('name' => 'fontsize', 'type' => 'xsd:int'),
		'theme' => array('name' => 'theme', 'type' => 'xsd:string'),
		'unix_status' => array('name' => 'unix_status', 'type' => 'xsd:string'),
		'unix_box' => array('name' => 'unix_box', 'type' => 'xsd:string'),
		'authorized_keys' => array('name' => 'authorized_keys', 'type' => 'xsd:string'),
		'user_pw' => array('name' => 'user_pw', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'people_resume' => array('name' => 'people_resume', 'type' => 'xsd:string'),
		'people_view_skills' => array('name' => 'people_view_skills', 'type' => 'xsd:int'),
		'language_id' => array('name' => 'language_id', 'type' => 'xsd:int'),
		'realname' => array('name' => 'realname', 'type' => 'xsd:string')
	)
  );
  
  $server->wsdl->addComplexType(
	'ArrayOfUser',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:User[]')
	),
	'tns:User'
  );
  
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
  
  $server->wsdl->addComplexType(
	'Session',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id' => array('name' => 'user_id', 'type' => 'xsd:int'),
		'session_hash' => array('name' => 'session_hash', 'type' => 'xsd:string')
	)
  );
  
  $server->wsdl->addComplexType(
        'Group',
        'complexType',
        'struct',
        'all',
        '',
        array(
            'group_id' => array('name'=>'group_id', 'type'=>'xsd:int'), 
            'group_name' => array('name'=>'group_name', 'type'=>'xsd:string'),
            'description' => array('name'=>'description', 'type'=>'xsd:string'),
            'admin_flags' => array('name'=>'admin_flags', 'type'=>'xsd:string'),
            'group_admins' => array('name'=>'group_admins', 'type'=>'tns:ArrayOfUser')
        )
    );
    
  $server->wsdl->addComplexType(
	'ArrayOfGroup',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Group[]')
	),
	'tns:Group'
  );
  
  // Register the methods to expose
  $server->register('getUserById',			       // method name
	  array('sessionKey' => 'xsd:string',		       // input parameters
	  	'user_id' => 'xsd:int'
	  ),		    
	  array('return'   => 'tns:User'),		       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getUserById',		       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'Get User By Id'	                               // documentation
  );

  $server->register('getUserSkillInventory',		       // method name
	  array('sessionKey' => 'xsd:string',		       // input parameters	
	  	'user_id' => 'xsd:int'
	  ),		    
	  array('return'   => 'tns:UserSkillInventory'),       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getUserSkillInventory',	       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'Get User Skill Inventory By Id'	               // documentation
  );	
  
  $server->register('getPeopleSkillBox',		       // method name
	  array('sessionKey' => 'xsd:string'),                 // input parameters
	  array('return'   => 'tns:PeopleSkillBox'),           // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getPeopleSkillBox',	       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'Get People Skill Box'                               // documentation
  );	
  
  $server->register('getPeopleSkillLevelBox',		       // method name
	  array('sessionKey' => 'xsd:string'),                 // input parameters
	  array('return'   => 'tns:PeopleSkillLevelBox'),      // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getPeopleSkillLevelBox',       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'Get People Skill Level Box'                         // documentation
  );	
  
  $server->register('getPeopleSkillYearBox',		       // method name
	  array('sessionKey' => 'xsd:string'),		       // input parameters
	  array('return'   => 'tns:PeopleSkillYearBox'),       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getPeopleSkillYearBox',	       // soapaction
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
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#addToPeopleSkills',            // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'add Skill to People Skills (For CodeX Admin only)'  // documentation
  );

  $server->register('getTimezoneBox',			       // method name
	  array('sessionKey' => 'xsd:string'),		       // input parameters	      
	  array('return'   => 'tns:TimezoneBox'),   	       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getTimezoneBox',               // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'get Timezone Box'     			       // documentation
  );
  
  $server->register('login',				       // method name
	  array('loginname' => 'xsd:string',                
	        'passwd'    => 'xsd:string'
	  ),		                            
	  array('return'   => 'tns:Session'),   	       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#login',             	       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'login to CodeX Server'  			       // documentation
  );
  
  $server->register('logout',				       // method name
	  array('sessionKey' => 'xsd:string'),                 // input parameters	                            
	  array(),				   	       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#logout',             	       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'logout From CodeX Server'  			       // documentation
  );

  $server->register('getListOfGroupsByUser',		       // method name
	  array('sessionKey' => 'xsd:string',		       // input parameters	      
	  	'user_id' => 'xsd:int'
	  ),                                   
	  array('return'   => 'tns:ArrayOfGroup'),	       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#getListOfGroupsByUser',        // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'get listing of groups for that user'  	       // documentation
  );

  $server->register('updateUser',			       // method name
	  array('sessionKey' => 'xsd:string',		       // input parameters	      
	  	'user'       => 'tns:User'
	  ),                                   
	  array(),					       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#updateUser',   		       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'update User'				  	       // documentation
  );
  
  $server->register('updateUserSkillInventory',		       // method name
	  array('sessionKey' => 'xsd:string',		       // input parameters	      
	  	'userSkillInventory' => 'tns:UserSkillInventory'
	  ),                                   
	  array(),					       // output parameters
	  'urn:CodeXAccountwsdl',			       // namespace
	  'urn:CodeXAccountwsdl#updateUser',   		       // soapaction
	  'rpc',					       // style
	  'encoded',					       // use
	  'update User Skill Inventory'		  	       // documentation
  );
  
  // Define the methods as a PHP function
  
  function getUserById($sessionKey, $uid) 
  {
    if (session_continue($sessionKey)){
    	$res_user = db_query("SELECT * FROM user WHERE user_id=" . $uid);
    	$row_user = db_fetch_array($res_user);
    	if (!$res_user || db_numrows($res_user) < 1) 
    	   return new soap_fault ('Server','CodeXAccountwsdl','Could Not Get User By Id',db_error());
        else 
    	   return new soapval('return', 'tns:User', row_user_to_soap($row_user));
     } else
    	return new soap_fault (get_user_fault,'getUserById','Invalide Session ','');
  }
  
  function updateUser ($sessionKey, $user)
  {
     if (session_continue($sessionKey)){
    	$res_user = db_query("SELECT * FROM user WHERE user_id ='" . $user['user_id']."'");
    	$row_user = db_fetch_array($res_user);
    	if (!$res_user || db_numrows($res_user) < 1) 
    	   return new soap_fault (invalid_user_fault,'updateUser','Internal error: Cannot locate user in database.','');
        else 
    	   {
    	      $bool = false;
    	      $sql = "UPDATE user SET";
    	      if ($user['user_name'] != $row_user['user_name'])	{
    	      	  $sql .= " user_name = '".$user['user_name']."'"; 
    	      	  $bool = true;
    	      }
    	      if ($user['add_date'] != $row_user['add_date']) {
    	          $sql .= " add_date = '".$user['add_date']."'";
    	          $bool = true;
    	      }
    	      if ($user['timezone'] != $row_user['timezone']) {
    	          $sql .= " timezone = '".$user['timezone']."'";
    	          $bool = true;
    	      }
    	      if ($user['email'] != $row_user['email']) {
    	          $sql .= " email = '".$user['email']."'";
    	          $bool = true;
    	      }
    	      if ($user['mail_siteupdates'] != $row_user['mail_siteupdates']) { 
    	          $sql .= " mail_siteupdates = '".$user['mail_siteupdates']."'";
    	          $bool = true;
    	      }
    	      if ($user['mail_va'] != $row_user['mail_va']) {
    	          $sql .= " mail_va = ".$user['mail_va'];
    	          $bool = true;
    	      }
    	      if ($user['sticky_login'] != $row_user['sticky_login']) { 
    	          $sql .= " sticky_login = ".$user['sticky_login'];
    	          $bool = true;
    	      }
    	      if ($user['fontsize'] != $row_user['fontsize']) { 
    	          $sql .= " fontsize = ".$user['fontsize'];
    	          $bool = true;
    	      }
    	      if ($user['theme'] != $row_user['theme'])	{ 
    	          $sql .= " theme = '".$user['theme']."'";
    	          $bool = true;
    	      }
    	      if ($user['unix_status'] != $row_user['unix_status']) {
    	          $sql .= " unix_status = '".$user['unix_status']."'";
    	          $bool = true;
    	      }
    	      if ($user['unix_box'] != $row_user['unix_box']) {
    	          $sql .= " unix_box = '".$user['unix_box']."'";
    	          $bool = true;
    	      }
    	      if ($user['authorized_keys'] != $row_user['authorized_keys']) {
    	          $sql .= " user_name = '".$user['user_name']."'";
    	          $bool = true;
    	      }
    	      if ($user['user_pw'] != $row_user['user_pw']) {
    	          updateUserPassword($user['user_id'], $row_user['user_pw'], $user['user_pw']);
    	          exit;
    	      }
	      if ($user['status'] != $row_user['status']) { 
	          $sql .= " status = '".$user['status']."'";
	          $bool = true;
    	      }
	      if ($user['people_resume'] != $row_user['people_resume']) { 
	          $sql .= " people_resume = '".$user['people_resume']."'";
	          $bool = true;
    	      }
	      if ($user['people_view_skills'] != $row_user['people_view_skills']) { 
	          $sql .= " people_view_skills = ".$user['people_view_skills'];
	          $bool = true;
    	      }
	      if ($user['language_id'] != $row_user['language_id']) {
	          $sql .= " language_id = ".$user['language_id'];
	          $bool = true;
    	      }
	      if ($user['realname'] != $row_user['realname']) { 
	          $sql .= " realname = '".$user['realname']."'";
	          $bool = true;
    	      }
	      $sql .= " WHERE user_id =" . $user['user_id'];
	      if ($bool) {
	         $res = db_query($sql);
    	         if (! $res) {
           	     return new soap_fault (update_user_fault,'updateUser','Internal error: Could not update User.', db_error()); 
           	 }    
    	      }
    	   }
     } else
    	return new soap_fault (invalid_session_fault,'updateUser','Invalide Session ','');
  }
   
  function updateUserSkillInventory ($sessionKey, $userSkillInventory)
  {
     
     if (session_continue($sessionKey)){
     	// suppression de la table people_skill_inventory les competences qui ont ete supprime
     	// en mode off-line
     	if (is_array($userSkillInventory)) 
     	    $count = count($userSkillInventory);
        else 
            $count = 0;
            
        $user_id = session_get_userid();
        $sql    = "SELECT * FROM people_skill_inventory WHERE user_id=".$user_id;
        $result = db_query($sql);
        $rows   = db_numrows($result);
        for ($i=0; $i < $rows; $i++) 
        {
            $bool = false;
            for ($j=0; (($j < $count) && (!$bool)); $j++)
            {
               $userSkill = $userSkillInventory[$j];
               if (db_result($result,$i,'skill_id') == $userSkill['skill_id'])
               	   $bool = true;
            }
            if (!$bool)
            {
               $sql="DELETE FROM people_skill_inventory WHERE user_id='".$user_id."' AND 					  skill_inventory_id=".db_result($result,$i,'skill_inventory_id');
     	       $result=db_query($sql);
     	       if (!$result || db_affected_rows($result) < 1) {
	           return new soap_fault (user_skill_delete_fault,'updateUserSkillInventory','User Skill Delete FAILED', db_error());
	       }
            }
        }     
     	// ajout et modification des competences qui ont ete ajoutes ou modifies en mode off-line
       for ($i=0; $i < $count; $i++) 
        {
           $userSkill = $userSkillInventory[$i];	
           $sql    = "SELECT * FROM people_skill_inventory WHERE (user_id='".$user_id."') AND (skill_id='".$userSkill['skill_id']."')";
           $result = db_query($sql);
           $rows   = db_numrows($result);
           if (!$result || db_numrows($result) < 1 ) {
              //skill not already in inventory
	      $sql="INSERT INTO people_skill_inventory (user_id,skill_id,skill_level_id,skill_year_id) ".
	      	   "VALUES (".$userSkill['user_id'].",".$userSkill['skill_id'].",".$userSkill['skill_level_id'].
	      	   ",".$userSkill['skill_year_id'].")";
	      $result=db_query($sql);
	      if (!$result || db_affected_rows($result) < 1) {
	         return new soap_fault (user_skill_insert_fault,'updateUserSkillInventory','ERROR inserting into skill inventory', db_error());
	      }
	   }
	   else
	   {
              $sql = "UPDATE people_skill_inventory SET";
              $bool = false;
              for ($j=0; $j < $rows; $j++) 
              {
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
			 return new soap_fault (user_skill_update_fault,'updateUserSkillInventory', 'User Skill update FAILED', db_error());
		     } 
                  }
              } 
           }  	
        }
     } 
     else
        return new soap_fault (invalid_session_fault,'updateUserSkillInventory','Invalide Session ','');
  }
  
  function getUserSkillInventory($sessionKey, $uid) 
  {
    if (session_continue($sessionKey)){
    	$userSkillInventory = array();
    	$sql="SELECT * FROM people_skill_inventory WHERE user_id='$uid'";
    	$result = db_query($sql);
    	$rows   = db_numrows($result);
    	//if (!$result || $rows < 1) {
    	if (!$result) {
    	    return new soap_fault (get_user_skill_inventory_fault,'getUserSkillInventory','Could Not Get Skill Inventory',db_error());
	} else {
		for ($i=0; $i < $rows; $i++) {
		
		    $userSkillInventory[] = user_skill_to_soap($result, $i);
		}
		return new soapval('return', 'tns:UserSkillInventory', $userSkillInventory);
	}
     } else
    	return new soap_fault (invalid_session_fault,'getUserSkillInventory','Invalide Session ','');
  }
  
  function getPeopleSkillBox($sessionKey)
  {
    if (session_continue($sessionKey)){
        $PEOPLE_SKILL = array();
        $sql    = "SELECT * FROM people_skill ORDER BY skill_id ASC";
        $result = db_query($sql);
        $rows   = db_numrows($result);
        if (!$result || $rows < 1) {
    	   return new soap_fault (get_people_skill_box_fault,'getPeopleSkillBox','Could Not Get People Skill Box',db_error());
	} else {
		for ($i=0; $i < $rows; $i++) {
			
			$PEOPLE_SKILL[] = people_skill_to_soap($result, $i);
		}
		return new soapval('return', 'tns:PeopleSkillBox', $PEOPLE_SKILL);
	}	
     } else
    	return new soap_fault (invalid_session_fault,'getPeopleSkillBox','Invalide Session ','');
  }
  
  function getPeopleSkillLevelBox($sessionKey)
  {
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
     } else
    	   return new soap_fault (invalid_session_fault,'getPeopleSkillLevelBox','Invalide Session ','');
  }
  
  function getPeopleSkillYearBox($sessionKey)
  {
    if (session_continue($sessionKey)){
    	$PEOPLE_SKILL_YEAR = array();
    	$sql    = "SELECT * FROM people_skill_year ORDER BY skill_year_id ASC";
    	$result = db_query($sql);
    	$rows   = db_numrows($result);
    	if (!$result || $rows < 1) {
    		return new soap_fault (get_people_skill_year_box_fault,'getPeopleSkillYearBox','Could Not Get People Skill Year Box',db_error());
	} else {
		for ($i=0; $i < $rows; $i++) {
			
			$PEOPLE_SKILL_YEAR[] = people_skill_year_to_soap($result, $i);
		}
		return new soapval('return', 'tns:PeopleSkillYearBox', $PEOPLE_SKILL_YEAR);
	}	
     } else
    	  return new soap_fault (invalid_session_fault,'getPeopleSkillYearBox','Invalide Session ',''); 	
  }
  
  function updateUserPassword($uid, $old_pwd, $new_pwd) 
  {
     $res = db_query("SELECT user_pw, status FROM user WHERE user_id=" . $uid);
     $row_pw = db_fetch_array();
     if ($row_pw[user_pw] != md5($old_pwd)) {
	return new soap_fault (invalid_old_pwd_fault,'updateUserPassword','Old password is incorrect.', ''); 
     }

     if (($row_pw[status] != 'A')&&($row_pw[status] != 'R')) {
	return new soap_fault (inactive_account_fault,'updateUserPassword','Account must be active to change password.', ''); 
     }

     if (!account_pwvalid($new_pwd)) {
	return new soap_fault (invalid_new_pwd_fault,'updateUserPassword','Password must be at least 6 characters.', ''); 
     }
	
     // if we got this far, it must be good
     if (!account_set_password($uid, $new_pwd) ) {
     	return new soap_fault (update_user_pwd_fault,'updateUserPassword','Internal error: Could not update password.', db_error()); 	
     }
  }
  
  function addToPeopleSkills($sessionKey, $uid, $skill_name)
  {
     if (session_continue($sessionKey)){
     	if (user_ismember(1,'A')) {
           $sql="INSERT INTO people_skill (name) VALUES ('$skill_name')";
           $result=db_query($sql);
           if (!$result) {
	      return new soap_fault (add_people_skill_fault,'addToPeopleSkills','Error inserting value', db_error());
           }
     	} else {
            return new soap_fault (permission_denied_fault,'addToPeopleSkills','Permission Denied', '');
        }
     } else
    	   return new soap_fault (invalid_session_fault,'addToPeopleSkills','Invalide Session ',''); 
  }
  
  function login($loginname, $passwd)
  {
    list($success, $status) = session_login_valid($loginname,$passwd);
    if ($success) {
	$return = array(
			'user_id'  => session_get_userid(),
			'session_hash' => $GLOBALS['session_hash']
        );
	return new soapval('return', 'tns:Session',$return);
    } else {
    	return new soap_fault(login_fault,'login','Unable to log with loginname of '.$loginname.' and password of '.$passwd, '');
    }
  }
  
  function logout($sessionKey) 
  {
     global $session_hash;
     if (session_continue($sessionKey)){
         if (isset($session_hash)) {
    	    db_query("DELETE FROM session WHERE session_hash='$session_hash'");
         }
         session_cookie('session_hash','');
     } else
    	   return new soap_fault (invalid_session_fault, 'CodeXAccountwsdl', 'Invalide Session ','');  
  }
  
  function getTimezoneBox($sessionKey)
  {
     global $TZs;
     if (session_continue($sessionKey)){
         return new soapval('return', 'tns:TimezoneBox', $TZs);
     } else
    	 return new soap_fault (invalid_session_fault,'logout','Invalide Session',''); 
  }
  
  function getListOfGroupsByUser($sessionKey, $user_id)
  {
     if (session_continue($sessionKey)){
     	$LIST_GROUP = array();
     	$res_group = db_query("SELECT groups.group_name, "
     	     . "groups.short_description, "
	     . "groups.unix_group_name, "
	     . "groups.group_id, "
	     . "groups.hide_members, "
	     . "user_group.admin_flags, "
	     . "user_group.bug_flags FROM "
	     . "groups,user_group WHERE user_group.user_id='$user_id' AND "
	     . "groups.group_id=user_group.group_id AND groups.is_public='1' AND groups.status='A' AND 				groups.type='1'");
	if (!$res_group || db_numrows($res_group) < 1) {
		return new soap_fault (get_groups_fault,'getListOfGroupsByUser', 'This developer is not a member of any projects.',db_error());
	} else { 
		while ($row_group = db_fetch_array($res_group)) {
		   $LIST_GROUP[] = row_group_to_soap($sessionKey, $row_group);
            		}
            	return new soapval('return', 'tns:ArrayOfGroup', $LIST_GROUP);	
        }
     } else
    	   return new soap_fault (invalid_session_fault,'getListOfGroupsByUser','Invalide Session ','');  
  }
  
  function row_user_to_soap($row_user)
  {
     $return = array();
     $return = array(
			'user_id' => $row_user['user_id'],
			'user_name' => $row_user['user_name'],
			'add_date' => $row_user['add_date'],
			'timezone' => $row_user['timezone'],
			'email' => $row_user['email'],
			'mail_siteupdates' => $row_user['mail_siteupdates'],
			'mail_va' => $row_user['mail_va'],
			'sticky_login' => $row_user['sticky_login'],
			'fontsize' => $row_user['fontsize'],
			'theme' => $row_user['theme'],
			'unix_status' => $row_user['unix_status'],
			'unix_box' => $row_user['unix_box'],
			'authorized_keys' => $row_user['authorized_keys'],
			'user_pw' => $row_user['user_pw'],
			'status' => $row_user['status'],
			'people_resume' => $row_user['people_resume'],
			'people_view_skills' => $row_user['people_view_skills'],
			'language_id' => $row_user['language_id'],
			'realname' => $row_user['realname']
     );
     return $return;
  }
  
  function row_group_to_soap($sessionKey, $row_group)
  {
     $return = array();
     $group_admins = array();
     $res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name "
	. "FROM user,user_group "
	. "WHERE user_group.user_id=user.user_id AND user_group.group_id=".$row_group['group_id']." AND "
	. "user_group.admin_flags = 'A'");
     $rows=db_numrows($res_admin);
     for ($i=0; $i<$rows; $i++) {
     	$group_admins[] = getUserById($sessionKey, db_result($res_admin,$i,0));
     }
     $return = array(
     			'group_id'    => $row_group['group_id'], 
     			'group_name'  => $row_group['group_name'], 
     			'admin_flags' => $row_group['admin_flags'],
     			'description' => $row_group['short_description'],
     			'group_admins' => $group_admins
     			
     );
     return $return; 	
  }
  
  function user_skill_to_soap($result, $i)
  {
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
  
  function people_skill_to_soap($result, $i)
  {
      $return = array();
      $return = array(
			'skill_id'  => db_result($result,$i,'skill_id'),
			'name'      => db_result($result,$i,'name')
      );
      return $return;
  }
  
  function people_skill_level_to_soap($result, $i)
  {
      $return = array();
      $return = array(
			'skill_level_id'  => db_result($result,$i,'skill_level_id'),
			'name'      => db_result($result,$i,'name')
      );
      return $return;
  }
  
  function people_skill_year_to_soap($result, $i)
  {
      $return = array();
      $return = array(
			'skill_year_id'  => db_result($result,$i,'skill_year_id'),
			'name'           => db_result($result,$i,'name')
      );
      return $return;
  }
  
  
  $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
  $server->service($HTTP_RAW_POST_DATA);

?>  
