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




    public function __construct(array $request_data) {
        $this->setFullName($request_data)
            ->setUnixName($request_data)
            ->setShortDescription($request_data)
            ->setIsPublic($request_data)
            ->setTemplateId($request_data);
    }
    
    /**
     * Displays the view for this step. The view is a file called one_step_register.phtml
     */
    public function display() {
        include($GLOBALS['Language']->getContent('project/one_step_register', null, null, '.phtml')); 
    }
    
    /**
     * 
     * @return boolean
     */
    public function validateAndDisplayErrors() {
        $this->is_valid = true;
        
        $this->validateTemplateId()
            ->validateUnixName()
            ->validateProjectPrivacy()
            ->validateFullName()
            ->validateShortDescription();
        
        return $this->is_valid;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    public function setUnixName(array $data) {
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
    public function setFullName(array $data) {
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
    public function setShortDescription(array $data) {
        if(isset($data['form_short_description'])) {
            $this->short_description = $data['form_short_description'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @param array $data
     * @return \RegisterProjectOneStep
     */
    public function setTemplateId(array $data) {
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
    public function setIsPublic(array $data) {
        if(isset($data['is_public'])) {
            $this->short_description = $data['is_public'];
        }
        
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    private function getUnixName() {
        return $this->unix_name;
    }
    
    /**
     * 
     * @return string
     */
    private function getFullName() {
        return $this->full_name;
    }
    
    /**
     * 
     * @return string
     */
    private function getShortDescription() {
        return $this->short_description;
    }
    
    /**
     * 
     * @return int
     */
    private function getTemplateId() {
        return $this->templateId;
    }
    
    /**
     * 
     * @return bool
     */
    private function isPublic() {
        return $this->is_public;
    }
        
    /**
     * 
     * @return Template[]
     */
    private function getDefaultTemplates() {
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
    private function getUserTemplates() {
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
     * @return string
     */
    private function getDateFormat() {
        return $GLOBALS['Language']->getText('system', 'datefmt_short');
    }

    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateFullName() {
        if ($this->getFullName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }
        
        $rule = new Rule_ProjectFullName();
        if (!$rule->isValid($this->getFullName())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_full_name'));
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
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
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
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }
        
        //check for valid group name
        $rule = new Rule_ProjectName();
        if (!$rule->isValid($this->getUnixName())) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_license','invalid_short_name'));
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
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }

        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($this->getTemplateId());
        
        if (! $project->isTemplate() && ! user_ismember($this->getTemplateId(), 'A')) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
            $this->setIsNotValid();
        }
        
        return $this;
    }
    
    /**
     * 
     * @return \RegisterProjectOneStep
     */
    private function validateProjectPrivacy() {
        if ($this->isPublic() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
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
