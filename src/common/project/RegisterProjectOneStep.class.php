<?php
/**
  * Copyright (c) Enalean, 2013. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

require_once 'RegisterProjectStep.class.php';
require_once 'common/include/TemplateSingleton.class.php';
require_once 'Template.class.php';
require_once 'common/valid/Rule.class.php';


/**
 * Controller view helper class 
 */
class RegisterProjectOneStep {

    /**
     *
     * @var string 
     */
    private $full_name;
    
    /**
     *
     * @var string 
     */
    private $short_description;
    
    /**
     *
     * @var string 
     */
    private $unix_name;
    
    /**
     *
     * @var bool 
     */
    private $is_public;
    
    /**
     *
     * @var int 
     */
    private $templateId;
    
    /**
     *
     * @var bool 
     */
    private $is_valid = true;

    private $form_submission_path;
    
    private $license_type;
    
    private $custom_license = null;
    
    private $available_licenses;


    public function __construct(array $request_data, array $available_licenses) {
        $this->setFullName($request_data)
            ->setUnixName($request_data)
            ->setShortDescription($request_data)
            ->setIsPublic($request_data)
            ->setTemplateId($request_data)
            ->setLicenseType($request_data)
            ->setCustomLicense($request_data);
        
        $this->setAvailableLicenses($available_licenses);
    }
    
    /**
     * 
     * @return boolean
     */
    public function validateAndGenerateErrors() {
        $this->is_valid = true;
        
        $this->validateTemplateId()
            ->validateUnixName()
            ->validateProjectPrivacy()
            ->validateFullName()
            ->validateShortDescription()
            ->validateLicense();
        
        return $this->is_valid;
    }
        
    /**
     * 
     * @return array
     */
    public function getProjectValues() {
        $custom_license = ($this->getLicenseType() == 'other') ? $this->getCustomLicense() : null;
        
        return array(
            'project' => array(
                'form_full_name'            => $this->getFullName(),
                'is_public'                 => $this->isPublic(),
                'form_unix_name'            => $this->getUnixName(),
                'built_from_template'       => $this->getTemplateId(),
                'form_license'              => $this->getLicenseType(),
                'form_license_other'        => $custom_license,
                'form_short_description'    => $this->getShortDescription(),
                'is_test'                   => false,
            )
        );
    }
    
    /**
     * 
     * @return array
     */
    public function getAvailableLicenses() {
        return $this->available_licenses;
    }

    /**
     * 
     * @return string
     */
    public function getFormSubmissionPath() {
        return $this->form_submission_path;
    }
    
    /**
     * 
     * @return string
     */
    public function getUnixName() {
        return $this->unix_name;
    }
    
    /**
     * 
     * @return string
     */
    public function getFullName() {
        return $this->full_name;
    }
    
    /**
     * 
     * @return string
     */
    public function getShortDescription() {
        return $this->short_description;
    }
    
    /**
     * 
     * @return int
     */
    public function getTemplateId() {
        return $this->templateId;
    }
    
    /**
     * 
     * @return bool
     */
    public function isPublic() {
        return $this->is_public;
    }
    
    public function getLicenseType() {
        return $this->license_type;
    }
    
    public function getCustomLicense() {
        return $this->custom_license;
    }
        
    /**
     * 
     * @return Template[]
     */
    public function getDefaultTemplates() {
        $db_templates = db_query("
            SELECT group_id, 
                group_name, 
                unix_group_name,
                short_description,
                register_time 
            FROM groups 
            WHERE type='2' 
            AND status IN ('A','s')"
        );

        $templates = $this->generateTemplatesFromDbData($db_templates);
        return $templates;     
    }
    
    /**
     * 
     * @return Template[]
     */
    public function getUserTemplates() {
        $userId = (int) user_getid();
        
        $db_data = db_query("
            SELECT groups.group_name AS group_name, 
                groups.group_id AS group_id, 
                groups.unix_group_name AS unix_group_name,
                groups.register_time AS register_time, 
                groups.short_description AS short_description
            FROM groups, user_group 
            WHERE groups.group_id = user_group.group_id 
            AND user_group.user_id = '". $userId ."' 
            AND user_group.admin_flags = 'A' 
            AND groups.status='A' 
            ORDER BY group_name"
        );
        
        $templates = $this->generateTemplatesFromDbData($db_data);
        return $templates; 
    }
    
    /**
     * 
     * @return string
     */
    public function getDateFormat() {
        return $GLOBALS['Language']->getText('system', 'datefmt_short');
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    private function setUnixName(array $data) {
        if(isset($data['form_unix_name'])) {
            $this->unix_name = $data['form_unix_name'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    private function setFullName(array $data) {
        if(isset($data['form_full_name'])) {
            $this->full_name = $data['form_full_name'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    private function setShortDescription(array $data) {
        if(isset($data['form_short_description'])) {
            $this->short_description = trim($data['form_short_description']);
        }
        
        return $this;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    private function setTemplateId(array $data) {
        if(isset($data['built_from_template'])) {
            $this->templateId = $data['built_from_template'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    private function setIsPublic(array $data) {
        if(isset($data['is_public'])) {
            $this->is_public = $data['is_public'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @param string $path
     * @return \RegisterProjectOneStep
     */
    private function setFormSubmissionPath(string $path) {
        $this->form_submission_path = $path;
        return $this;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    private function setLicenseType($data) {
        if(isset($data['form_license'])) {
            $this->license_type = $data['form_license'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @param string $data
     * @return \RegisterProjectOneStep
     */
    private function setCustomLicense($data) {
        if(isset($data['form_license_other'])) {
            $this->custom_license = trim($data['form_license_other']);
        }
        
        return $this;
    }
        
    /**
     * 
     * @param array $licenses
     * @return \RegisterProjectOneStep
     */
    private function setAvailableLicenses(array $licenses) {
        $this->available_licenses = $licenses;
        return $this;
    }
    
    /**
     * 
     * @param resource $db_data
     * @return Template[]
     */
    private function generateTemplatesFromDbData($db_data) {
        $templates = array();
        $row_count = db_numrows($db_data);
        
        for ($i=0; $i < $row_count; $i++) {
            $template = new Template();
            $template->setUserGroupId(db_result($db_data, $i, 'group_id'))
                    ->setUserGroupName(db_result($db_data, $i, 'group_name'))
                    ->setDateRegistered(db_result($db_data, $i, 'register_time'))
                    ->setUnixGroupName(db_result($db_data, $i, 'unix_group_name'))
                    ->setShortDescription(db_result($db_data, $i, 'short_description'));
            $templates[] = $template;
        }
        
        return $templates;
    }

    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateFullName() {
        if ($this->getFullName() == null) {
            $GLOBALS['Response']->addFeedback('error', 'full_name' . $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }
        
        $rule = new Rule_ProjectFullName();
        if (!$rule->isValid($this->getFullName())) {
            $GLOBALS['Response']->addFeedback('error', 'full_name' . $GLOBALS['Language']->getText('register_license','invalid_full_name'));
            $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            $this->setIsNotValid();
        }
        
        return $this;
    }
    
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateShortDescription() {
        if ($this->getShortDescription() == null) {
            $GLOBALS['Response']->addFeedback('error', 'short' . $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }
        
        return $this;
    }
    
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateUnixName() {
        if ($this->getUnixName() == null) {
            $GLOBALS['Response']->addFeedback('error', 'unix' . $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }
        
        //check for valid group name
        $rule = new Rule_ProjectName();
        if (!$rule->isValid($this->getUnixName())) {
            $GLOBALS['Response']->addFeedback('error', 'unix' . $GLOBALS['Language']->getText('register_license','invalid_short_name'));
            $GLOBALS['Response']->addFeedback('error', $rule->getErrorMessage());
            $this->setIsNotValid();
        }
        
        return $this;
    }
    
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateTemplateId() {
        if ($this->getTemplateId() == null) {
            $GLOBALS['Response']->addFeedback('error', 'template' .$GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
        }

        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($this->getTemplateId());
        
        if (! $project->isTemplate() && ! user_ismember($this->getTemplateId(), 'A')) {
            $GLOBALS['Response']->addFeedback('error', 'template' .$GLOBALS['Language']->getText('global', 'perm_denied'));
            $this->setIsNotValid();
        }
        
        return $this;
    }
    
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateProjectPrivacy() {
        if ($this->isPublic() === null) {
            $GLOBALS['Response']->addFeedback('error', 'priv' . $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }
        
        return $this;
    }
    
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateLicense() {
        if ($this->getLicenseType() === null) {
            $GLOBALS['Response']->addFeedback('error', 'priv' . $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }
        
        return $this;
    }
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function setIsNotValid() {
        $this->is_valid = false;
        return $this;
    }
}

?>
