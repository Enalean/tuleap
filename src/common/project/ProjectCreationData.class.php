<?php
/**
 * Copyright (c) Sogilis, 2015. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class ProjectCreationData {

    private $data_services;
    private $data_fields;
    private $full_name;
    private $unix_name;
    private $is_public;
    private $is_test;
    private $short_description;
    private $built_from_template;
    private $trove_data;
    private $license;
    private $license_other;

    public function getFullName() {
        return $this->full_name;
    }

    public function setFullName($name) {
        $this->full_name = $name;
    }

    public function getUnixName() {
        return $this->unix_name;
    }

    public function setUnixName($name) {
        $this->unix_name = $name;
    }

    public function getAccess() {
        if(is_null($this->is_public)) {
            return ForgeConfig::get('sys_is_project_public') ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;
        } else {
            return ($this->is_public) ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;
        }
    }

    public function isTest() {
        return $this->is_test;
    }

    public function getLicense() {
        return $this->license;
    }

    public function getLicenseOther() {
        return $this->license_other;
    }

    public function getShortDescription() {
        return $this->short_description;
    }

    public function getTemplateId() {
        return $this->built_from_template;
    }

    public function getTroveData() {
        return $this->trove_data;
    }

    /**
     * @param $group_desc_id int id of the description field to return
     * @return the value of the field requested, null if the field isnt set
     */
    public function getField($group_desc_id) {
        if(!isset($this->data_fields['form_' . $group_desc_id])) {
            return null;
        }
        return $this->data_fields['form_' . $group_desc_id];
    }

    /**
     * @return array with:
     *     is_used => boolean telling if the service is used
     *     server_id => service server id (whatver that is)
     */
    public function getServiceInfo($service_id) {
        return isset($this->data_services[$service_id]) ?
            $this->data_services[$service_id] :
            null;
    }

    /**
     * $data['project']['form_unix_name']
     * $data['project']['form_full_name']
     * $data['project']['form_license']
     * $data['project']['form_license_other']
     * $data['project']['form_short_description']
     * $data['project']['built_from_template']
     * $data['project']['is_test']
     * $data['project']['is_public']
     * $data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]
     * foreach($data['project']['trove'] as $root => $values);
     * $data['project']['services'][$arr['service_id']]['is_used'];
     * $data['project']['services'][$arr['service_id']]['server_id'];
     */
    public function buildFromFormArray(array $data) {
        $instance = new ProjectCreationData();
        $instance->fromForm($data);
        return $instance;
    }

    private function fromForm(array $data) {
        $project = isset($data['project']) ? $data['project'] : array();

        $this->unix_name           = isset($project['form_unix_name'])         ? $project['form_unix_name']         : null;
        $this->full_name           = isset($project['form_full_name'])         ? $project['form_full_name']         : null;
        $this->license             = isset($project['form_license'])           ? $project['form_license']           : null;
        $this->license_other       = isset($project['form_license_other'])     ? $project['form_license_other']     : null;
        $this->short_description   = isset($project['form_short_description']) ? $project['form_short_description'] : null;
        $this->built_from_template = isset($project['built_from_template'])    ? $project['built_from_template']    : null;
        $this->is_test             = isset($project['is_test'])                ? $project['is_test']                : null;
        $this->is_public           = isset($project['is_public'])              ? $project['is_public']              : null;
        $this->trove_data          = isset($project['trove'])                  ? $project['trove']                  : array();
        $this->data_services       = isset($project['services'])               ? $project['services']               : array();
        $this->data_fields         = $project;
    }
}

