<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
    private $is_template;
    private $short_description;
    private $built_from_template;
    private $trove_data;
    private $is_unrestricted = false;
    private $inherit_from_template = true;

    /**
     * Returns true if the data should be inherited from template (in DB)
     *
     * This is mostly useful for XML import where "the true" come from XML
     * and not from the predefined template.
     *
     * @return boolean
     */
    public function projectShouldInheritFromTemplate() {
        return $this->inherit_from_template;
    }

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
        if ($this->is_unrestricted) {
            return Project::ACCESS_PUBLIC_UNRESTRICTED;
        }
        if ($this->is_public === null || ForgeConfig::get('sys_user_can_choose_project_privacy') === '0') {
            return ForgeConfig::get('sys_is_project_public') ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;
        } else {
            return $this->is_public ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;
        }
    }

    public function isTest() {
        return $this->is_test;
    }

    public function isTemplate()
    {
        return $this->is_template;
    }

    public function setIsTemplate()
    {
        $this->is_template = true;
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
     * $data['project']['form_short_description']
     * $data['project']['built_from_template']
     * $data['project']['is_test']
     * $data['project']['is_public']
     * $data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]
     * foreach($data['project']['trove'] as $root => $values);
     * $data['project']['services'][$arr['service_id']]['is_used'];
     * $data['project']['services'][$arr['service_id']]['server_id'];
     */
    public static function buildFromFormArray(array $data) {
        $instance = new ProjectCreationData();
        $instance->fromForm($data);
        return $instance;
    }

    private function fromForm(array $data) {
        $project = isset($data['project']) ? $data['project'] : array();

        $this->unix_name           = isset($project['form_unix_name'])         ? $project['form_unix_name']         : null;
        $this->full_name           = isset($project['form_full_name'])         ? $project['form_full_name']         : null;
        $this->short_description   = isset($project['form_short_description']) ? $project['form_short_description'] : null;
        $this->built_from_template = isset($project['built_from_template'])    ? $project['built_from_template']    : null;
        $this->is_test             = isset($project['is_test'])                ? $project['is_test']                : null;
        $this->is_public           = isset($project['is_public'])              ? $project['is_public']              : null;
        $this->trove_data          = isset($project['trove'])                  ? $project['trove']                  : array();
        $this->data_services       = isset($project['services'])               ? $project['services']               : array();
        $this->data_fields         = $project;
    }

    public static function buildFromXML(
        SimpleXMLElement $xml,
        $template_id = 100,
        XML_RNGValidator $xml_validator = null,
        ServiceManager $service_manager = null,
        ProjectManager $project_manager = null)
    {
        $instance = new ProjectCreationData();
        $instance->fromXML($xml, $template_id, $xml_validator, $service_manager, $project_manager);
        return $instance;
    }

    private function fromXML(
        SimpleXMLElement $xml,
        $template_id,
        XML_RNGValidator $xml_validator = null,
        ServiceManager $service_manager = null,
        ProjectManager $project_manager = null)
    {
        if(empty($xml_validator)) {
            $xml_validator = new XML_RNGValidator();
        }
        if(empty($service_manager)){
            $service_manager = ServiceManager::instance();
        }
        if(empty($project_manager)){
            $project_manager = ProjectManager::instance();
        }

        $rng_path = realpath(dirname(__FILE__).'/../xml/resources/project/project.rng');
        $xml_validator->validate($xml, $rng_path);

        $long_description_tagname = 'long-description';

        $attrs = $xml->attributes();
        $this->unix_name     = (string) $attrs['unix-name'];
        $this->full_name     = (string) $attrs['full-name'];
        $this->short_description   = (string) $attrs['description'];
        $this->built_from_template = (int) $template_id;
        $this->is_test       = (bool) false;
        $this->is_public     = null;
        $this->trove_data    = array();
        $this->data_services = array();
        $this->data_fields   = array(
            'form_101' => $xml->$long_description_tagname
        );

        switch ($attrs['access']) {
            case 'unrestricted':
                $this->is_unrestricted = true;
                break;
            case 'public':
                $this->is_public = true;
                break;
            case 'private':
                $this->is_public = false;
                break;
        }

        $this->markUsedServicesFromXML($xml, $template_id, $service_manager, $project_manager);

        $this->inherit_from_template = false;
    }

    /**
     * Read the template and XML and mark services as being in use if they are
     * allowed in the template and enabled in the XML.
     */
    private function markUsedServicesFromXML(
        SimpleXMLElement $xml,
        $template_id,
        ServiceManager $service_manager = null,
        ProjectManager $project_manager = null)
    {
        $template = $project_manager->getProject($template_id);
        $services_by_name = array();
        foreach($service_manager->getListOfAllowedServicesForProject($template) as $service) {
            $services_by_name[$service->getShortName()] = $service;
        }

        foreach($xml->services->children() as $service) {
            if(!($service instanceof SimpleXMLElement)) continue;
            if($service->getName() !== "service") continue;
            $attrs   = $service->attributes();
            $name    = (string) $attrs['shortname'];
            $enabled = \Tuleap\XML\PHPCast::toBoolean($attrs['enabled']);
            if(isset($services_by_name[$name])) {
                $service_id = $services_by_name[$name]->getId();
                $this->data_services[$service_id] = array(
                    'is_used' => $enabled
                );
            }
        }
    }

    public function unsetProjectServiceUsage($service_id)
    {
        if (isset($this->data_services[$service_id])) {
            $this->data_services[$service_id]['is_used'] = '0';
        }
    }

    public function forceServiceUsage($service_id)
    {
        if (isset($this->data_services[$service_id])) {
            $this->data_services[$service_id]['is_used'] = '1';
        }
    }
}

