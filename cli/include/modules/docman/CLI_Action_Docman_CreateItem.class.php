<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

abstract class CLI_Action_Docman_CreateItem extends CLI_Action {
    function __construct($name, $description) {
        parent::__construct($name, $description);

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
            'name'           => 'status',
            'description'    => '--status=<none|draft|approved|rejected>     Status to apply to the item',
        ));
        $this->addParam(array(
            'name'           => 'perm_read',
            'description'    => '--perm_read=<comma separated list of ugroup IDs>     Groups that will have the permission READ',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'perm_write',
            'description'    => '--perm_write=<comma separated list of ugroup IDs>     Groups that will have the permission WRITE',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'perm_manage',
            'description'    => '--perm_manage=<comma separated list of ugroup IDs>     Groups that will have the permission MANAGE',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'perm_none',
            'description'    => '--perm_none=<comma separated list of ugroup IDs>     Groups that will have no permission (ie. those groups will not inherit the permissions of the parent folder)',
            'soap'     => false,
        ));
        $this->addParam(array(
            'name'           => 'properties',
            'description'    => '--properties=<property file>     File that contains the properties to apply to the item',
            'soap'     => false,
        ));
    }

    function validate_parent_id(&$parent_id) {
        if (!isset($parent_id)) {
            echo $this->help();
            exit_error("You must specify the parent ID of the item with the --parent_id parameter");
        }
        return true;
    }
    function validate_title(&$title) {
        if (!isset($title) || trim($title) == '') {
            echo $this->help();
            exit_error("You must specify the title of the item with the --title parameter");
        }
        return true;
    }
    function validate_ordering(&$ordering) {
        $allowed_ordering = array("begin", "end");
        if (isset($ordering)) {
            // check that the value is allowed
            if (!in_array($ordering, $allowed_ordering)) {
                echo $this->help();
                exit_error("You must specify the ordering of the item with the --ordering parameter, taking the value {".implode(",", $allowed_ordering)."}");
            }
        } else {
            // $ordering is not set
            $ordering = "begin";
        }
        return true;
    }
    function validate_status(&$status) {
        $allowed_values= array("none", "draft", "approved", "rejected");
        if (isset($status) && !in_array($status, $allowed_values)) {
            echo $this->help();
            exit_error("The --status parameter must take one of the following values: ".implode(", ", $allowed_values));
        }
        return true;
    }
    function validate_perm(&$perm) {
        if (isset($perm) && !preg_match("/^[0-9]+(,[0-9]+)*$/", $perm)) {
            echo $this->help();
            return false;
        }
        return true;
    }
    function validate_perm_read(&$perm) {
        if (!$this->validate_perm($perm)) {
            exit_error("The permissions must be a comma separated list of ugroup IDs. Ex: --perm_read=101,102");
        }
        return true;
    }
    function validate_perm_write(&$perm) {
        if (!$this->validate_perm($perm)) {
            exit_error("The permissions must be a comma separated list of ugroup IDs. Ex: --perm_write=101,102");
        }
        return true;
    }
    function validate_perm_manage(&$perm) {
        if (!$this->validate_perm($perm)) {
            exit_error("The permissions must be a comma separated list of ugroup IDs. Ex: --perm_manage=101,102");
        }
        return true;
    }
    function validate_perm_none(&$perm) {
        if (!$this->validate_perm($perm)) {
            exit_error("The permissions must be a comma separated list of ugroup IDs. Ex: --perm_none=101,102");
        }
        return true;
    }

    /**
     * Retrieve the groups given by the CLI parameters (read_groups, write_groups, manage_groups) and put them in the SOAP parameter "permissions" with their associated permission
     */
    function loadPermissionParams(&$loaded_params) {
        $loaded_params['soap']['permissions'] = array();

        $permissions = array(
                        array('type' => 'PLUGIN_DOCMAN_READ', 'param_name' => 'perm_read'),
                        array('type' => 'PLUGIN_DOCMAN_WRITE', 'param_name' => 'perm_write'),
                        array('type' => 'PLUGIN_DOCMAN_MANAGE', 'param_name' => 'perm_manage'),
                        array('type' => '', 'param_name' => 'perm_none'));

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

        // We do nothing if no file is specified
        if (isset ($loaded_params['others']['properties'])) {
            $properties = parse_ini_file($loaded_params['others']['properties']);
            if ($properties) {
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
    }

    function after_loadParams(&$loaded_params) {
        $this->loadPermissionParams($loaded_params);
        $this->loadMetadata($loaded_params);
    }
}
