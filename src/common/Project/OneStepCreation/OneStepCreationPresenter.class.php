<?php
/**
  * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

/**
 * Presenter for one step creation project
 */
class Project_OneStepCreation_OneStepCreationPresenter //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{

    public const DEFAULT_TEMPLATE_ID = 100;

    public const FULL_NAME                       = 'form_full_name';
    public const UNIX_NAME                       = 'form_unix_name';
    public const IS_PUBLIC                       = 'is_public';
    public const USER_CAN_CHOOSE_PROJECT_PRIVACY = 'user_can_choose_project_privacy';
    public const TEMPLATE_ID                     = 'built_from_template';
    public const SHORT_DESCRIPTION               = 'form_short_description';
    public const TOS_APPROVAL                    = 'form_terms_of_services_approval';
    public const PROJECT_DESCRIPTION_PREFIX      = 'form_';
    public const TROVE_CAT_PREFIX                = 'trove';

    public $full_name_label                        = self::FULL_NAME;
    public $unix_name_label                        = self::UNIX_NAME;
    public $user_can_choose_project_privacy_label  = self::USER_CAN_CHOOSE_PROJECT_PRIVACY;
    public $is_public_label                        = self::IS_PUBLIC;
    /**
     * @var bool
     */
    public $has_project_without_restricted;
    public $template_id_label                      = self::TEMPLATE_ID;
    public $short_description_label                = self::SHORT_DESCRIPTION;
    public $term_of_service_approval_label         = self::TOS_APPROVAL;

    /**
     * @var Project_CustomDescription_CustomDescriptionPresenter[]
     */
    private $required_custom_description_presenters;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var Project_OneStepCreation_OneStepCreationRequest
     */
    private $creation_request;

    /**
     * @var array
     */
    public $trove_cats;

    /**
     * @var string
     */
    private $csrf_token;
    /**
     * @var bool
     */
    public $is_description_mandatory;

    public function __construct(
        Project_OneStepCreation_OneStepCreationRequest $creation_request,
        array $required_custom_descriptions,
        ProjectManager $project_manager,
        array $trove_cats,
        $csrf_token_field,
        bool $is_description_mandatory
    ) {
        $this->creation_request                       = $creation_request;
        $this->project_manager                        = $project_manager;
        $this->required_custom_description_presenters = $this->getCustomDescriptionPresenters($required_custom_descriptions);
        $this->trove_cats                             = array_values($trove_cats);
        $this->csrf_token                             = $csrf_token_field;
        $this->has_project_without_restricted         = ForgeConfig::areRestrictedUsersAllowed();
        $this->is_description_mandatory               = $is_description_mandatory;
    }

    public function hasTroveCats()
    {
        return count($this->trove_cats) > 0;
    }

    /**
     * @return Project_CustomDescription_CustomDescriptionPresenter[]
     */
    private function getCustomDescriptionPresenters(array $required_custom_descriptions)
    {
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

    public function getSysName()
    {
        return ForgeConfig::get('sys_name');
    }

    /**
     * @return bool
     */
    public function isProjectApprovalEnabled()
    {
        return ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL);
    }

    /**
     *
     * @return string
     */
    public function getUnixName()
    {
        return $this->creation_request->getUnixName();
    }

    /**
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->creation_request->getFullName();
    }

    /**
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->creation_request->getShortDescription();
    }

    /**
     *
     * @return int
     */
    public function getTemplateId()
    {
        return $this->creation_request->getTemplateId();
    }

    /**
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->creation_request->isPublic();
    }

    /**
     *
     * @return bool
     */
    public function userCanSelectProjectPrivacy()
    {
        return $this->creation_request->userCanSelectProjectPrivacy();
    }

    /**
     *
     * @return type
     */
    public function getTosApproval()
    {
        return $this->creation_request->getTosApproval();
    }

    public function getProjectDescriptionFields()
    {
        return $this->required_custom_description_presenters;
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'title');
    }

    public function getPageDescriptionBeg()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'page_description_beg');
    }

    public function getPageDescriptionEnd()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'page_description_end');
    }

    public function getWarning()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'warning');
    }

    public function getWarningMessage()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'warning_message');
    }

    public function getDescriptionContainerTitle()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_title');
    }

    public function getDescriptionContainerFullName()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_full_name');
    }

    public function getDescriptionContainerShortName()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_name');
    }

    public function getDescriptionContainerFullNameHelp()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_full_name_help');
    }

    public function getDescriptionContainerShortNameHelp()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_name_help');
    }

    public function getDescriptionContainerShortNameLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_name_label');
    }

    public function getDescriptionContainerShortDescription()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_description');
    }

    public function getDescriptionContainerShortDescriptionHelp()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_short_description_help');
    }

    public function getDescriptionContainerProjectDescription()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_description');
    }

    public function getDescriptionContainerProjectPrivacy()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_privacy');
    }

    public function getDescriptionContainerPublicLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_public_label');
    }

    public function getDescriptionContainerPrivateLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'description_container_project_private_label');
    }

    public function getChooseTemplateContainerTitle()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_template_title');
    }

    public function getChooseTemplateContainerDescriptionPartOne()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_template_description_part_one');
    }

    public function getChooseTemplateContainerDescriptionPartTwo()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_template_description_part_two');
    }

    public function getDefaultTemplatesTitle()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_default_templates');
    }

    public function getAdminProjectsTitle()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'choose_admin_projects');
    }

    public function getCreateProjectButtonLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'submit_button');
    }

    public function getAgreeTOSLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'agree_TOS_label');
    }

    public function getTOSLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'TOS_label');
    }

    public function getAboutToCreateLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'about_to_create');
    }

    public function getAboutToCreateOptionalLabel()
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'about_to_create_optional');
    }

    public function trove_cat_legend() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('register_project_one_step', 'trove_cat_legend');
    }

    public function none_selected() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $GLOBALS['Language']->getText('include_trove', 'none_selected');
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneAvailableTemplate()
    {
        return $this->hasUserTemplates() || $this->hasMoreThanOneDefaultTemplates();
    }

    /**
     *
     * @return ProjectCreationTemplatePresenter[]
     */
    public function getDefaultTemplates()
    {
        $projects = $this->project_manager->getSiteTemplates();
        return $this->generateTemplatesFromParsedDbData($projects);
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneDefaultTemplates()
    {
        return count($this->getDefaultTemplates()) > 1;
    }

    /**
     *
     * @return ProjectCreationTemplatePresenter[]
     */
    public function getUserTemplates()
    {
        $projects = $this->project_manager->getProjectsUserIsAdmin($this->creation_request->getCurrentUser());
        return $this->generateTemplatesFromParsedDbData($projects);
    }

    /**
     * @return bool
     */
    public function hasUserTemplates()
    {
        return count($this->getUserTemplates()) > 0;
    }

    /**
     * @param Project[] $projects
     * @return ProjectCreationTemplatePresenter[]
     */
    private function generateTemplatesFromParsedDbData(array $projects)
    {
        $templates = array();
        foreach ($projects as $project) {
            /** @var Project $project */
            $templates[] = new ProjectCreationTemplatePresenter($project, $this->getTemplateId());
        }
        return $templates;
    }

    public function csrf_token() //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return $this->csrf_token;
    }
}
