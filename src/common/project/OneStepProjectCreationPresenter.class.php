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
require_once 'ProjectCreationTemplatePresenter.class.php';
require_once 'common/valid/Rule.class.php';


/**
 * Controller view helper class
 */
class OneStepProjectCreationPresenter {

    const DEFAULT_TEMPLATE_ID = 100;

    const FULL_NAME         = 'form_full_name';
    const UNIX_NAME         = 'form_unix_name';
    const IS_PUBLIC         = 'is_public';
    const TEMPLATE_ID       = 'built_from_template';
    const LICENSE_TYPE      = 'form_license';
    const CUSTOM_LICENSE    = 'form_license_other';
    const SHORT_DESCRIPTION = 'form_short_description';
    const TOS_APPROVAL      = 'form_terms_of_services_approval';
    const PROJECT_DESCRIPTION_PREFIX = 'form_';

    public $full_name_label                = self::FULL_NAME;
    public $unix_name_label                = self::UNIX_NAME;
    public $is_public_label                = self::IS_PUBLIC;
    public $template_id_label              = self::TEMPLATE_ID;
    public $license_type_label             = self::LICENSE_TYPE;
    public $custom_license_label           = self::CUSTOM_LICENSE;
    public $short_description_label        = self::SHORT_DESCRIPTION;
    public $term_of_service_approval_label = self::TOS_APPROVAL;

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
    private $term_of_service_approval;

    /**
     *
     * @var bool
     */
    private $is_valid = true;

    /**
     *
     * @var string
     */
    private $form_submission_path;

    /**
     *
     * @var string
     */
    private $license_type;

    /**
     *
     * @var blob
     */
    private $custom_license = null;

    /**
     * @var array
     */
    private $custom_descriptions = array();

    /**
     * @var array
     */
    private $available_licenses;

    /**
     * @var User
     */
    private $creator;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var ProjectDao
     */
    private $project_dao;

    public function __construct(array $request_data, User $creator, array $licenses, ProjectManager $project_manager = null, ProjectDao $project_dao = null) {
        $this->setFullName($request_data)
            ->setUnixName($request_data)
            ->setShortDescription($request_data)
            ->setIsPublic($request_data)
            ->setTemplateId($request_data)
            ->setLicenseType($request_data)
            ->setCustomLicense($request_data)
            ->setTosApproval($request_data)
            ->setCustomDescriptions($request_data);
        $this->available_licenses = $licenses;
        $this->creator            = $creator;
        $this->project_manager    = $project_manager !== null ? $project_manager : ProjectManager::instance();
        $this->project_dao        = $project_dao !== null     ? $project_dao     : new ProjectDao();
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
            ->validateLicense()
            ->validateTosApproval();

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
                self::FULL_NAME         => $this->getFullName(),
                self::IS_PUBLIC         => $this->isPublic(),
                self::UNIX_NAME         => $this->getUnixName(),
                self::TEMPLATE_ID       => $this->getTemplateId(),
                self::LICENSE_TYPE      => $this->getLicenseType(),
                self::CUSTOM_LICENSE    => $custom_license,
                self::SHORT_DESCRIPTION => $this->getShortDescription(),
                'is_test'               => false,
            )
        );
    }

    public function getSysName() {
        return Config::get('sys_name');
    }

    /**
     * @return bool
     */
    public function isProjectApprovalEnabled() {
        return Config::get('sys_project_approval');
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

    /**
     *
     * @return string
     */
    public function getLicenseType() {
        return $this->license_type;
    }

    public function getAvailableLicenses() {
        $licenses = array();
        foreach ($this->available_licenses as $license_type => $license_description) {
            $licenses[] = array(
                'license_type'        => $license_type,
                'license_description' => $license_description,
                'isSelected'          => ($license_type == $this->getLicenseType())
            );
        }
        return $licenses;
    }

    /**
     *
     * @return blob
     */
    public function getCustomLicense() {
        return $this->custom_license;
    }

    /**
     *
     * @return type
     */
    public function getTosApproval() {
        return $this->term_of_service_approval;
    }

    /**
     * @return string
     */
    public function getCustomProjectDescription($id) {
        return $this->custom_descriptions[$id];
    }

    public function getProjectDescriptionFields() {
        $purifier = Codendi_HTMLPurifier::instance();
        $fields = array();
        $res = db_query('SELECT * FROM group_desc WHERE desc_required = 1 ORDER BY desc_rank');
        while ($row = db_fetch_array($res)) {
            $form_name = self::PROJECT_DESCRIPTION_PREFIX.$row['group_desc_id'];
            $fields[] = array(
                'label'               => $row['desc_name'],
                'description'         => $purifier->purify($row['desc_description'], CODENDI_PURIFIER_LIGHT),
                'is_text_field_type'  => $row['desc_type'] == 'line' ? false : true,
                'form_name'           => $form_name,
                'value'               => '',
            );
        }
        return $fields;
    }
    
    public function getTitle() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'title');
    }
    
    public function getPageDescriptionBeg() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'page_description_beg');
    }
    
    public function getPageDescriptionEnd() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'page_description_end');
    }
    
    public function getWarning() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'warning');
    }
    
    public function getWarningMessage() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'warning_message');
    }
    
    public function getDescriptionContainerTitle() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_title');
    }
    
    public function getDescriptionContainerFullName() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_full_name');
    }
    
    public function getDescriptionContainerShortName() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_name');
    }
    
    public function getDescriptionContainerFullNameHelp() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_full_name_help');
    }
    
    public function getDescriptionContainerShortNameHelp() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_name_help');
    }
    
    public function getDescriptionContainerShortNameLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_name_label');
    }
    
    public function getDescriptionContainerShortDescription() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_description');
    }
    
    public function getDescriptionContainerShortDescriptionHelp() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_description_help');
    }
    
    public function getDescriptionContainerProjectDescription() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_description');
    }
    
    public function getDescriptionContainerProjectPrivacy() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_privacy');
    }
    
    public function getDescriptionContainerPublicLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_public_label');
    }
    
    public function getDescriptionContainerPrivateLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_private_label');
    }
    
    public function getDescriptionContainerProjectLicense() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_license');
    }
    
    public function getDescriptionContainerProjectLicenseHelp() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_license_help');

    public function getCreateProjectButtonLabel() {
        return $GLOBALS[Language]->getText('register_project_one_step', 'submit_button');
    }
    /**
     * @return bool
     */
    public function hasMoreThanOneAvailableTemplate() {
        return $this->hasUserTemplates() || $this->hasMoreThanOneDefaultTemplates();
    }

    /**
     *
     * @return ProjectCreationTemplatePresenter[]
     */
    public function getDefaultTemplates() {
        $projects = $this->project_dao
                ->searchSiteTemplates()
                ->instanciateWith(array($this->project_manager, 'getProjectFromDbRow'));
        return $this->generateTemplatesFromParsedDbData($projects);
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneDefaultTemplates() {
        return count($this->getDefaultTemplates()) > 1;
    }

    /**
     *
     * @return ProjectCreationTemplatePresenter[]
     */
    public function getUserTemplates() {
        $projects = $this->project_dao
                ->searchProjectsUserIsAdmin($this->creator->getId())
                ->instanciateWith(array($this->project_manager, 'getProjectFromDbRow'));
        return $this->generateTemplatesFromParsedDbData($projects);
    }

    /**
     * @return bool
     */
    public function hasUserTemplates() {
        return count($this->getUserTemplates()) > 0;
    }

    /**
     *
     * @param resource $db_data
     * @return ProjectCreationTemplatePresenter[]
     */
    private function generateTemplatesFromParsedDbData(DataAccessResult $projects) {
        $templates = array();
        foreach ($projects as $project) {
            /* @var $project Project */
            $templates[] = new ProjectCreationTemplatePresenter($project, $this->getTemplateId());
        }
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
     * @return \OneStepProjectCreationPresenter
     */
    private function setUnixName(array $data) {
        if(isset($data[self::UNIX_NAME])) {
            $this->unix_name = $data[self::UNIX_NAME];
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setFullName(array $data) {
        if(isset($data[self::FULL_NAME])) {
            $this->full_name = $data[self::FULL_NAME];
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setShortDescription(array $data) {
        if(isset($data[self::SHORT_DESCRIPTION])) {
            $this->short_description = trim($data[self::SHORT_DESCRIPTION]);
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setTemplateId(array $data) {
        if(isset($data[self::TEMPLATE_ID])) {
            $this->templateId = $data[self::TEMPLATE_ID];
        } else {
            $this->templateId = self::DEFAULT_TEMPLATE_ID;
        }

        return $this;
    }

    /**
     *
     * @param array $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setIsPublic(array $data) {
        if(isset($data[self::IS_PUBLIC])) {
            $this->is_public = $data[self::IS_PUBLIC];
        }

        return $this;
    }

    /**
     *
     * @param string $path
     * @return \OneStepProjectCreationPresenter
     */
    private function setFormSubmissionPath(string $path) {
        $this->form_submission_path = $path;
        return $this;
    }

    /**
     *
     * @param array $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setLicenseType($data) {
        if(isset($data[self::LICENSE_TYPE])) {
            $this->license_type = $data[self::LICENSE_TYPE];
        }

        return $this;
    }

    /**
     *
     * @param string $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setCustomLicense($data) {
        if(isset($data[self::CUSTOM_LICENSE])) {
            $this->custom_license = trim($data[self::CUSTOM_LICENSE]);
        }

        return $this;
    }

    /**
     *
     * @param type $data
     * @return \OneStepProjectCreationPresenter
     */
    private function setTosApproval($data) {
        $this->term_of_service_approval = false;

        if (isset($data[self::TOS_APPROVAL])) {
            $this->term_of_service_approval = true;
        }

        return $this;
    }

    private function setCustomDescriptions($data) {
        foreach ($data as $key => $value) {
            //if (strp)
        }
    }

    /**
     *
     * @return \OneStepProjectCreationPresenter
     */
    private function validateFullName() {
        if ($this->getFullName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
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
     * @return \OneStepProjectCreationPresenter
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
     * @return \OneStepProjectCreationPresenter
     */
    private function validateUnixName() {
        if ($this->getUnixName() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
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
     * @return \OneStepProjectCreationPresenter
     */
    private function validateTemplateId() {
        if ($this->getTemplateId() == null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
            return $this;
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
     * @return \OneStepProjectCreationPresenter
     */
    private function validateProjectPrivacy() {
        if ($this->isPublic() === null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }

        return $this;
    }

    /**
     *
     * @return \OneStepProjectCreationPresenter
     */
    private function validateLicense() {
        if ($this->getLicenseType() === null) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'info_missed'));
            $this->setIsNotValid();
        }

        return $this;
    }
    /**
     *
     * @return \OneStepProjectCreationPresenter
     */
    private function setIsNotValid() {
        $this->is_valid = false;
        return $this;
    }

    private function validateTosApproval() {
        if (! $this->getTosApproval()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_projectname', 'tos_not_approved'));
            $this->setIsNotValid();
        }

        return $this;
    }
}

?>
