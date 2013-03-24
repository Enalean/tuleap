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

require_once 'common/include/TemplateSingleton.class.php';
require_once 'common/project/ProjectCreationTemplatePresenter.class.php';
require_once 'common/valid/Rule.class.php';
require_once 'common/project/CustomDescription/CustomDescriptionPresenter.class.php';
require_once 'OneStepCreationRequest.class.php';

/**
 * Presenter for one step creation project
 */
class Project_OneStepCreation_OneStepCreationPresenter {

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
     * @var Project_CustomDescription_CustomDescriptionPresenter[]
     */
    private $required_custom_description_presenters;

    /**
     * @var array
     */
    private $available_licenses;

    /**
     * @var PFUser
     */
    private $creator;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var Project_OneStepCreation_OneStepCreationRequest
     */
    private $creation_request;

    public function __construct(
        Project_OneStepCreation_OneStepCreationRequest $creation_request,
        array $available_licenses,
        array $required_custom_descriptions,
        ProjectManager $project_manager
    ) {
        $this->creation_request                       = $creation_request;
        $this->available_licenses                     = $available_licenses;
        $this->project_manager                        = $project_manager;
        $this->required_custom_description_presenters = $this->getCustomDescriptionPresenters($required_custom_descriptions);
    }

    /**
     * @return Project_CustomDescription_CustomDescriptionPresenter[]
     */
    private function getCustomDescriptionPresenters(array $required_custom_descriptions) {
        $presenters = array();
        foreach ($required_custom_descriptions as $custom_description) {
            $presenters[] = new Project_CustomDescription_CustomDescriptionPresenter(
                $custom_description,
                $this->creation_request->getCustomProjectDescription($custom_description->getId()),
                self::PROJECT_DESCRIPTION_PREFIX
            );
        }
        return $presenters;
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
    public function getUnixName() {
        return $this->creation_request->getUnixName();
    }

    /**
     *
     * @return string
     */
    public function getFullName() {
        return $this->creation_request->getFullName();
    }

    /**
     *
     * @return string
     */
    public function getShortDescription() {
        return $this->creation_request->getShortDescription();
    }

    /**
     *
     * @return int
     */
    public function getTemplateId() {
        return $this->creation_request->getTemplateId();
    }

    /**
     *
     * @return bool
     */
    public function isPublic() {
        return $this->creation_request->isPublic();
    }

    /**
     *
     * @return string
     */
    public function getLicenseType() {
        return $this->creation_request->getLicenseType();
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
     * @return text
     */
    public function getCustomLicense() {
        return $this->creation_request->getCustomLicense();
    }

    /**
     *
     * @return type
     */
    public function getTosApproval() {
        return $this->creation_request->getTosApproval();
    }

    public function getProjectDescriptionFields() {
        return $this->required_custom_description_presenters;
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
    }

    public function getChooseTemplateContainerTitle() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_template_title');
    }

    public function getChooseTemplateContainerDescriptionPartOne() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_template_description_part_one');
    }

    public function getChooseTemplateContainerDescriptionPartTwo() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_template_description_part_two');
    }

    public function getDefaultTemplatesTitle() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_default_templates');
    }

    public function getAdminProjectsTitle() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_admin_projects');
    }

    public function getCreateProjectButtonLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'submit_button');
    }

    public function getAgreeTOSLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'agree_TOS_label');
    }

    public function getTOSLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'TOS_label');
    }

    public function getAboutToCreateLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'about_to_create');
    }

    public function getAboutToCreateOptionalLabel() {
        return $GLOBALS['Language']->getText('register_project_one_step', 'about_to_create_optional');
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
        $projects = $this->project_manager->getSiteTemplates();
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
        $projects = $this->project_manager->getProjectsUserIsAdmin($this->creation_request->getCurrentUser());
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
}

?>
