<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2008. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

abstract class CLI_Action_Docman_CreateItem extends CLI_Action {
	function CLI_Action_Docman_CreateItem($name, $description) {
		$this->CLI_Action($name, $description);

        $this->addParam(array(
            'name'           => 'parent_id',
            'description'    => '--parent_id=<item_id>     ID of the parent the folder will be created in'
        ));
        $this->addParam(array(
            'name'           => 'title',
            'description'    => '--title=<title>     Title of the new folder'
        ));
        $this->addParam(array(
            'name'           => 'description',
            'description'    => '--description=<description>     Description of the new folder'
        ));
        $this->addParam(array(
            'name'           => 'ordering',
            'description'    => '--ordering=<begin|end>     Place where the new folder will be hosted'
        ));
        $this->addParam(array(
            'name'           => 'perm-read',
            'description'    => '--perm-read=<comma separated list of ugroups IDs>     Groups that will have the permission READ',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'perm-write',
            'description'    => '--perm-write=<comma separated list of ugroups IDs>     Groups that will have the permission WRITE',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'perm-manage',
            'description'    => '--perm-manage=<comma separated list of ugroups IDs>     Groups that will have the permission MANAGE',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'perm-none',
            'description'    => '--perm-none=<comma separated list of ugroups IDs>     Groups that will have no permission',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'properties',
            'description'    => '--properties=<property file>     File that contains the properties to apply to the item',
            'soap'     => false,
        ));
	}
	
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
				// For each group that is present in the definition of the current permission, store a permission
				foreach ($ids as $id) {
					$loaded_params['soap']['permissions'][] = array('ugroup_id' => $id, 'type' => $permission['type']);
				}
			}
    	}
    }
    
    /**
     * Read metadata from the specified "properties" file. This file must be formatted as the following example:
     * ;======example.properties======
     * field_2=This is a string
     * 
     * ;List of values must follow this format: <N1,N2,N3,...> where Nx is an existing value ID
     * field_9=<103,104>
     * 
     * ;Values enclosed in double quotes can contain new lines
     * field_10="Ceci est un texte
     * sur plusieurs lignes
     * en français."
     * 
     * ;Date format must be "yyyy-mm-dd"
     * field_3=2008-12-25
     * ;==============================
     */
    function loadMetadata(&$loaded_params) {
    	$loaded_params['soap']['metadata'] = array();

    	// Do nothing if no file is specified
    	if (isset ($loaded_params['others']['properties'])) {
	    	$properties = parse_ini_file($loaded_params['others']['properties']);
	    	foreach ($properties as $name => $val) {
	    		if ($val != '') {
	    			// Check if the value corresponds to the pattern used for lists. Ex: "<1,2,3>"
		    		if (preg_match('/^<([0-9]+(,[0-9]+)*)>$/', $val, $matches)) {
						$lov = explode(',', $matches[1]);
						// Put each list value into the SOAP parameters array "metadata"
						foreach ($lov as $lv) {
							array_push($loaded_params['soap']['metadata'], array('label' => $name, 'value' => $lv));
						}
					} else {
						array_push($loaded_params['soap']['metadata'], array('label' => $name, 'value' => $val));
					}
	    		}
	    	}
    	}
    }
    
    function after_loadParams(&$loaded_params) {
    	$this->loadPermissionParams($loaded_params);
    	$this->loadMetadata($loaded_params);
    }
}
?>
