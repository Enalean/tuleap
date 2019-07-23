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

use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;

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

    public function __construct(
        Codendi_Request $request,
        ProjectManager $project_manager,
        DefaultProjectVisibilityRetriever $default_project_visibility_retriever,
        Project_CustomDescription_CustomDescriptionFactory $custom_description_factory,
        TroveCatFactory $trove_cat_factory,
        CSRFSynchronizerToken $csrf_token
    ) {
        parent::__construct('project', $request);
        $this->project_manager              = $project_manager;
        $this->required_custom_descriptions = $custom_description_factory->getRequiredCustomDescriptions();
        $this->trove_cats                   = $trove_cat_factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren();

        $this->creation_request = new Project_OneStepCreation_OneStepCreationRequest(
            $request,
            $project_manager,
            $default_project_visibility_retriever
        );
        $this->csrf_token       = $csrf_token;

        $this->presenter = new Project_OneStepCreation_OneStepCreationPresenter(
            $this->creation_request,
            $this->required_custom_descriptions,
            $project_manager,
            $this->trove_cats,
            $csrf_token->fetchHTMLInput()
        );

        $this->default_project_visibility_retriever = $default_project_visibility_retriever;
    }

    /**
     * Display the create project form
     */
    public function index() {
        $GLOBALS['HTML']->header(array('title'=> $GLOBALS['Language']->getText('register_index','project_registration')));
        if ($this->project_manager->userCanCreateProject($this->request->getCurrentUser())) {
            $this->render('register', $this->presenter);
        } else {
            $this->render('register-disabled', []);
        }
        $GLOBALS['HTML']->footer(array());
        exit;
    }

    /**
     * Create the project if request is valid
     */
    public function create() {
        if ($this->project_manager->userCanCreateProject($this->request->getCurrentUser())) {
            $this->csrf_token->check();
            $this->validate();
            $project = $this->doCreate();
            $this->notifySiteAdmin($project);
            $this->postCreate($project);
        } else {
            $GLOBALS['HTML']->header(array('title'=> $GLOBALS['Language']->getText('register_index','project_registration')));
            $this->render('register-disabled', []);
            $GLOBALS['HTML']->footer(array());
        }
    }

    private function validate() {
        $validator = new Project_OneStepCreation_OneStepCreationValidator(
            $this->creation_request,
            $this->required_custom_descriptions,
            $this->trove_cats
        );

        if (! $validator->validateAndGenerateErrors()) {
            $this->index();
        }
    }

    private function doCreate() {
        $send_notifications = true;
        $ugroup_user_dao    = new UGroupUserDao();
        $ugroup_manager     = new UGroupManager();
        $ugroup_binding     = new UGroupBinding($ugroup_user_dao, $ugroup_manager);
        $ugroup_duplicator  = new UgroupDuplicator(
            new UGroupDao(),
            $ugroup_manager,
            $ugroup_binding,
            MemberAdder::build(ProjectMemberAdderWithoutStatusCheckAndNotifications::build()),
            EventManager::instance()
        );

        $user_manager   = UserManager::instance();
        $widget_factory = new WidgetFactory(
            $user_manager,
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );

        $widget_dao        = new DashboardWidgetDao($widget_factory);
        $project_dao       = new ProjectDashboardDao($widget_dao);
        $project_retriever = new ProjectDashboardRetriever($project_dao);
        $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
        $duplicator        = new ProjectDashboardDuplicator(
            $project_dao,
            $project_retriever,
            $widget_dao,
            $widget_retriever,
            $widget_factory
        );

        $force_activation = false;

        $projectCreator = new ProjectCreator(
            $this->project_manager,
            ReferenceManager::instance(),
            $user_manager,
            $ugroup_duplicator,
            $send_notifications,
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao(),
                new ProjectHistoryDao()
            ),
            $duplicator,
            new ServiceCreator(),
            new LabelDao(),
            $this->default_project_visibility_retriever,
            new SynchronizedProjectMembershipDuplicator(new SynchronizedProjectMembershipDao()),
            $force_activation
        );

        $data         = $this->creation_request->getProjectValues();
        $creationData = ProjectCreationData::buildFromFormArray($this->default_project_visibility_retriever, $data);

        try {
            return $projectCreator->build($creationData);
        } catch (Exception $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $exception->getMessage());
            $GLOBALS['Response']->redirect('/');
        }
    }

    private function notifySiteAdmin(Project $project) {
        $subject = $GLOBALS['Language']->getText('register_project_one_step', 'complete_mail_subject', array($project->getPublicName()));
        $presenter = new MailPresenterFactory();
        $renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/mail/');
        $mail = new TuleapRegisterMail($presenter, $renderer, "mail-project-register-admin");
        $mail = $mail->getMailNotificationProject($subject, ForgeConfig::get('sys_noreply'), ForgeConfig::get('sys_email_admin'), $project);

        if (! $mail->send()) {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
        }
    }

    private function postCreate(Project $project) {
        $one_step_registration_factory = new Project_OneStepRegistration_OneStepRegistrationPresenterFactory($project);
        $GLOBALS['HTML']->header(array('title'=> $GLOBALS['Language']->getText('register_confirmation', 'registration_complete')));
        $this->render('confirmation', $one_step_registration_factory->create());
        $GLOBALS['HTML']->footer(array());
    }
}
