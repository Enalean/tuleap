<?php

class PluginSpecificRoleSetting
{
    var $role;
    var $name           = '';
    var $section        = '';
    var $values         = array();
    var $default_values = array();
    var $global         = false;

    public function __construct(&$role, $name, $global = false)
    {
        $this->global = $global;
        $this->role   =& $role;
        $this->name   = $name;
    }

    public function SetAllowedValues($values)
    {
        $this->role->role_values = array_replace_recursive($this->role->role_values, array($this->name => $values));
        if ($this->global) {
            $this->role->global_settings[] = $this->name;
        }
    }

    public function SetDefaultValues($defaults)
    {
        foreach ($defaults as $rname => $v) {
            $this->role->defaults[$rname][$this->name] = $v;
        }
    }

    public function setValueDescriptions($descs)
    {
        global $rbac_permission_names;
        foreach ($descs as $k => $v) {
            $rbac_permission_names[$this->name . $k] = $v;
        }
    }

    public function setDescription($desc)
    {
        global $rbac_edit_section_names;
        $rbac_edit_section_names[$this->name] = $desc;
    }
}
