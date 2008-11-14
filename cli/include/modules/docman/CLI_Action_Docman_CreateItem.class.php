<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2008. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_CreateItem extends CLI_Action {
    /**
     * Retrieve the groups given by the CLI parameters (read-groups, write-groups, manage-groups) and put them in the SOAP parameter "permissions" with their associated permission
     */
    function loadPermissionParams(&$loaded_params) {
    	$loaded_params['soap']['permissions'] = array();

        $permissions = array(
    					array('type' => 'PLUGIN_DOCMAN_READ', 'param_name' => 'perm-read'),
    					array('type' => 'PLUGIN_DOCMAN_WRITE', 'param_name' => 'perm-write'),
    					array('type' => 'PLUGIN_DOCMAN_MANAGE', 'param_name' => 'perm-manage'),
    					array('type' => '', 'param_name' => 'perm-none'));
    	
    	foreach ($permissions as $permission) {
	    	if (isset($loaded_params['others'][$permission['param_name']])) {
				$ids = explode(',', $loaded_params['others'][$permission['param_name']]);
				foreach ($ids as $id) {
					$loaded_params['soap']['permissions'][] = array('ugroup_id' => $id, 'type' => $permission['type']);
				}
			}
    	}
    }
    
    function after_loadParams(&$loaded_params) {
    	$this->loadPermissionParams($loaded_params);
    }
}
?>