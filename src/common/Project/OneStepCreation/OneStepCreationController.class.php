<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectDescriptionUsageRetriever;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\RegistrationForbiddenException;

/**
 * Base controller for one step creation project
 */
class Project_OneStepCreation_OneStepCreationController extends MVC2_Controller //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;

    /** @var Project_OneStepCreation_OneStepCreationRequest */
    private $creation_request;

    /** @var Project_OneStepCreation_OneStepCreationPresenter */
    private $presenter;

    /** @var Project_CustomDescription_CustomDescription[] */
    private $required_custom_descriptions;
    /** @var TroveCat[] */
    private $trove_cats;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function __construct(
        Codendi_Request $request,
        ProjectManager $project_manager,
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        Project_CustomDescription_CustomDescriptionFactory $custom_description_factory,
        TroveCatFactory $trove_cat_factory,
        CSRFSynchronizerToken $csrf_token,
        ProjectRegistrationUserPermissionChecker $permission_checker
    ) {
        parent::__construct('project', $request);
        $this->project_manager              = $project_manager;
        $this->required_custom_descriptions = $custom_description_factory->getRequiredCustomDescriptions();
        $this->trove_cats                   = $trove_cat_factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren();

        $this->creation_request = new Project_OneStepCreation_OneStepCreationRequest(
            $request,
            $default_project_visibility_retriever
        );
        $this->csrf_token       = $csrf_token;

        $this->presenter = new Project_OneStepCreation_OneStepCreationPresenter(
            $this->creation_request,
            $this->required_custom_descriptions,
            $project_manager,
            $this->trove_cats,
            $csrf_token->fetchHTMLInput(),
            ProjectDescriptionUsageRetriever::isDescriptionMandatory()
        );

        $this->default_project_visibility_retriever = $default_project_visibility_retriever;
        $this->permission_checker = $permission_checker;
    }

    /**
     * Display the create project form
     */
    public function index()
    {
        try {
            $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('register_index', 'project_registration')));
            $this->permission_checker->checkUserCreateAProject($this->request->getCurrentUser());
            $this->render('register', $this->presenter);
        } catch (RegistrationForbiddenException $exception) {
            $this->render('register-disabled', []);
        }
        $GLOBALS['HTML']->footer(array());
        exit;
    }

    /**
     * Create the project if request is valid
     */
    public function create()
    {
        try {
            $this->permission_checker->checkUserCreateAProject($this->request->getCurrentUser());
            $this->csrf_token->check();
            $this->validate();
            $project = $this->doCreate();
            $this->notifySiteAdmin($project);
            $this->postCreate($project);
        } catch (RegistrationForbiddenException $exception) {
            $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('register_index', 'project_registration')));
            $this->render('register-disabled', []);
            $GLOBALS['HTML']->footer(array());
        }
    }

    private function validate()
    {
        $validator = new Project_OneStepCreation_OneStepCreationValidator(
            $this->creation_request,
            $this->required_custom_descriptions,
            $this->trove_cats,
            $this->project_manager,
            new Rule_ProjectFullName(),
            new Rule_ProjectName()
        );

        if (! $validator->validateAndGenerateErrors()) {
            $this->index();
        }
    }

    private function doCreate()
    {
        $projectCreator = ProjectCreator::buildSelfRegularValidation();

        $data         = $this->creation_request->getProjectValues();
        $creationData = ProjectCreationData::buildFromFormArray(
            $this->default_project_visibility_retriever,
            $data['project']['built_from_template'],
            $data
        );

        try {
            return $projectCreator->build($creationData);
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $exception->getMessage());
            $GLOBALS['Response']->redirect('/');
        }
    }

    private function notifySiteAdmin(Project $project)
    {
        $subject = $GLOBALS['Language']->getText('register_project_one_step', 'complete_mail_subject', array($project->getPublicName()));
        $presenter = new MailPresenterFactory();
        $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/mail/');
        $mail = new TuleapRegisterMail($presenter, $renderer, "mail-project-register-admin");
        $mail = $mail->getMailNotificationProject($subject, ForgeConfig::get('sys_noreply'), ForgeConfig::get('sys_email_admin'), $project);

        if (! $mail->send()) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
        }
    }

    private function postCreate(Project $project)
    {
        $one_step_registration_factory = new Project_OneStepRegistration_OneStepRegistrationPresenterFactory($project);
        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('register_confirmation', 'registration_complete')));
        $this->render('confirmation', $one_step_registration_factory->create());
        $GLOBALS['HTML']->footer(array());
    }
}
