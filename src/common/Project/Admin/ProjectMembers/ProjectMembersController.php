<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use ArtifactTypeFactory;
use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use ProjectUGroup;
use TemplateRendererFactory;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\InviteBuddy\InvitationInstrumentation;
use Tuleap\InviteBuddy\InvitationLimitChecker;
use Tuleap\InviteBuddy\InviteBuddiesPresenterBuilder;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Invitations\CSRFSynchronizerTokenProvider;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectUGroup\MinimalUGroupPresenter;
use Tuleap\Project\ProjectPresentersBuilder;
use Tuleap\Project\UGroups\InvalidUGroupException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\User\StatusPresenter;
use UGroupBinding;
use UGroupManager;
use UGroupUserDao;
use UserHelper;
use UserImport;

class ProjectMembersController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var ProjectMembersDAO
     */
    private $members_dao;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var UGroupBinding
     */

    private $user_group_bindings;
    /**
     * @var UserRemover
     */
    private $user_remover;

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var UserImport
     */
    private $user_importer;

    /**
     * @var MinimalUGroupPresenter[]
     */
    private $ugroup_presenters = [];
    /**
     * @var SynchronizedProjectMembershipDetector
     */
    private $synchronized_project_membership_detector;
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;

    public function __construct(
        ProjectMembersDAO $members_dao,
        UserHelper $user_helper,
        UGroupBinding $user_group_bindings,
        UserRemover $user_remover,
        EventManager $event_manager,
        UGroupManager $ugroup_manager,
        UserImport $user_importer,
        ProjectRetriever $project_retriever,
        SynchronizedProjectMembershipDetector $synchronized_project_membership_detector,
        private EnsureUserCanManageProjectMembers $members_manager_checker,
        private ListOfPendingInvitationsPresenterBuilder $pending_invitations_presenter_builder,
    ) {
        $this->members_dao                              = $members_dao;
        $this->user_helper                              = $user_helper;
        $this->user_group_bindings                      = $user_group_bindings;
        $this->user_remover                             = $user_remover;
        $this->event_manager                            = $event_manager;
        $this->ugroup_manager                           = $ugroup_manager;
        $this->user_importer                            = $user_importer;
        $this->synchronized_project_membership_detector = $synchronized_project_membership_detector;
        $this->project_retriever                        = $project_retriever;
    }

    public static function buildSelf(): self
    {
        $event_manager  = EventManager::instance();
        $user_manager   = \UserManager::instance();
        $user_helper    = new UserHelper();
        $ugroup_manager = new UGroupManager();
        $ugroup_binding = new UGroupBinding(new UGroupUserDao(), $ugroup_manager);
        $configuration  = new InviteBuddyConfiguration($event_manager);
        $invitation_dao = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            new InvitationInstrumentation(Prometheus::instance()),
        );

        return new self(
            new ProjectMembersDAO(),
            $user_helper,
            $ugroup_binding,
            new UserRemover(
                ProjectManager::instance(),
                $event_manager,
                new ArtifactTypeFactory(false),
                new UserRemoverDao(),
                $user_manager,
                new ProjectHistoryDao(),
                $ugroup_manager,
                new UserPermissionsDao(),
            ),
            $event_manager,
            $ugroup_manager,
            new UserImport(
                $user_manager,
                $user_helper,
                ProjectMemberAdderWithStatusCheckAndNotifications::build()
            ),
            ProjectRetriever::buildSelf(),
            new SynchronizedProjectMembershipDetector(
                new SynchronizedProjectMembershipDao()
            ),
            new UserCanManageProjectMembersChecker(new MembershipDelegationDao()),
            new ListOfPendingInvitationsPresenterBuilder(
                $configuration,
                $invitation_dao,
                new TlpRelativeDatePresenterBuilder(),
                new CSRFSynchronizerTokenProvider(),
                new InviteBuddiesPresenterBuilder(
                    new InvitationLimitChecker(
                        $invitation_dao,
                        $configuration
                    ),
                    $configuration,
                    new ProjectPresentersBuilder(),
                    new UserCanManageProjectMembersChecker(new MembershipDelegationDao()),
                ),
            ),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $user    = $request->getCurrentUser();
        try {
            $this->members_manager_checker->checkUserCanManageProjectMembers($user, $project);
        } catch (UserIsNotAllowedToManageProjectMembersException $e) {
            throw new ForbiddenException(_("You don't have permission to access administration of this project."), $e);
        }

        if ($project->getStatus() !== Project::STATUS_ACTIVE && ! $user->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->csrf_token = new CSRFSynchronizerToken('/project/' . (int) $project->getID() . '/admin/members');

        switch ($request->get('action')) {
            case 'add-user':
                $this->addUserToProject($request, $project);
                $this->redirect($project, $layout);
                break;

            case 'remove-user':
                $this->removeUserFromProject($request, $project);
                $this->redirect($project, $layout);
                break;

            case 'import':
                $this->csrf_token->check();
                $this->importMembers($project, $user);
                $this->redirect($project, $layout);
                break;

            default:
                $event = new MembersEditProcessAction(
                    $request,
                    $project,
                    $this->csrf_token
                );

                $this->event_manager->processEvent($event);
                if ($event->hasBeenHandled()) {
                    $this->redirect($project, $layout);
                } else {
                    $this->display($request, $layout, $project);
                }
                break;
        }
    }

    private function redirect(Project $project, BaseLayout $layout)
    {
        $layout->redirect('/project/' . urlencode((string) $project->getID()) . '/admin/members');
    }

    private function display(HTTPRequest $request, BaseLayout $layout, Project $project)
    {
        $title = _('Members');

        $project_members_list = $this->getFormattedProjectMembers($project);
        $template_path        = ForgeConfig::get('tuleap_dir') . '/src/templates/project/members';
        $renderer             = TemplateRendererFactory::build()->getRenderer($template_path);
        $user_locale          = $request->getCurrentUser()->getLocale();
        $additional_modals    = new ProjectMembersAdditionalModalCollectionPresenter(
            $project,
            $this->csrf_token,
            $user_locale
        );

        $pending_invitations = $this->pending_invitations_presenter_builder->getPendingInvitationsPresenter(
            $project,
            $request->getCurrentUser()
        );
        if ($pending_invitations) {
            $layout->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());
        }

        $this->event_manager->processEvent($additional_modals);

        $this->displayHeader($title, $project, $layout, $additional_modals);


        $renderer->renderToPage(
            'project-members',
            new ProjectMembersPresenter(
                $project_members_list,
                $this->csrf_token,
                $project,
                $additional_modals,
                $user_locale,
                $this->canUserSeeUGroups($request->getCurrentUser(), $project),
                $this->synchronized_project_membership_detector->isSynchronizedWithProjectMembers($project),
                $pending_invitations,
            )
        );

        TemplateRendererFactory::build()
            ->getRenderer(__DIR__ . '/../../../../templates/project')
            ->renderToPage('end-project-admin-content', []);
        site_project_footer([]);
    }

    private function addUserToProject(HTTPRequest $request, Project $project)
    {
        $this->csrf_token->check();

        $form_unix_name = $request->get('new_project_member');

        if (! $form_unix_name) {
            return;
        }

        require_once __DIR__ . '/../../../../www/include/account.php';
        \account_add_user_to_group($project->getID(), $form_unix_name);
    }

    private function removeUserFromProject(HTTPRequest $request, Project $project)
    {
        $this->csrf_token->check();

        $rm_id = $request->getValidated(
            'user_id',
            'uint',
            0
        );

        $this->user_remover->removeUserFromProject($project->getID(), $rm_id);
        $this->user_group_bindings->reloadUgroupBindingInProject($project);
    }

    private function displayHeader($title, Project $project, BaseLayout $layout, ProjectMembersAdditionalModalCollectionPresenter $additional_modal)
    {
        $include_assets = new \Tuleap\Layout\IncludeCoreAssets();

        $layout->includeFooterJavascriptFile($include_assets->getFileURL('project-admin.js'));
        if ($additional_modal->hasJavascriptFile()) {
            $layout->includeFooterJavascriptFile($additional_modal->getJavascriptFile());
        }
        if ($additional_modal->hasCssAsset()) {
            $layout->addCssAsset($additional_modal->getCssAsset());
        }

        $header_displayer = new HeaderNavigationDisplayer();
        $header_displayer->displayBurningParrotNavigation($title, $project, 'members');
    }

    private function getFormattedProjectMembers(Project $project)
    {
        $database_results = $this->members_dao->searchProjectMembers($project->getID());

        $project_members = [];

        foreach ($database_results as $member) {
            $user                       = new \PFUser($member);
            $member['avatar_url']       = $user->getAvatarUrl();
            $member['ugroups']          = $this->getUGroupsPresenters($project, $member);
            $member['profile_page_url'] = "/users/" . urlencode($member['user_name']) .  "/";
            $member['is_project_admin'] = $member['admin_flags'] === UserPermissionsDao::PROJECT_ADMIN_FLAG;
            $member['username_display'] = $this->user_helper->getDisplayName(
                $member['user_name'],
                $member['realname']
            );


            $member['status_presenter'] = new StatusPresenter($member['status']);
            $project_members[]          = $member;
        }

        return $project_members;
    }

    private function getUGroupsPresenters(Project $project, array $member)
    {
        $ugroups = [];

        if ($member['admin_flags'] === UserPermissionsDao::PROJECT_ADMIN_FLAG) {
            $ugroups[] = new MinimalUGroupPresenter(
                $this->ugroup_manager->getProjectAdminsUGroup($project)
            );
        }

        if ($member['wiki_flags'] === UserPermissionsDao::WIKI_ADMIN_FLAG && $project->usesWiki()) {
            $this->appendUgroups($ugroups, $project, ProjectUGroup::WIKI_ADMIN);
        }

        if ($member['forum_flags'] === UserPermissionsDao::FORUM_ADMIN_FLAG && $project->usesForum()) {
            $this->appendUgroups($ugroups, $project, ProjectUGroup::FORUM_ADMIN);
        }

        if (
            in_array($member['news_flags'], [UserPermissionsDao::NEWS_WRITER_FLAG, UserPermissionsDao::NEWS_ADMIN_FLAG])
            && $project->usesNews()
        ) {
            $this->appendUgroups($ugroups, $project, ProjectUGroup::NEWS_WRITER);
        }

        if ($member['news_flags'] === UserPermissionsDao::NEWS_ADMIN_FLAG && $project->usesNews()) {
            $this->appendUgroups($ugroups, $project, ProjectUGroup::NEWS_ADMIN);
        }

        if (! $member['ugroups_ids']) {
            return $ugroups;
        }

        $ugroups_ids = explode(',', $member['ugroups_ids']);
        foreach ($ugroups_ids as $ugroup_id) {
            $ugroups[] = $this->getMinimalUGroupPresenter($project, $ugroup_id);
        }

        return $ugroups;
    }

    private function appendUgroups(array &$ugroups, Project $project, int $ugroup_id): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
        if ($ugroup) {
            $ugroups[] = new MinimalUGroupPresenter($ugroup);
        }
    }

    /**
     * @return MinimalUGroupPresenter
     */
    private function getMinimalUGroupPresenter(Project $project, $ugroup_id)
    {
        if (! isset($this->ugroup_presenters[$ugroup_id])) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
            if (! $ugroup) {
                throw new InvalidUGroupException($ugroup_id);
            }
            $this->ugroup_presenters[$ugroup_id] = new MinimalUGroupPresenter(
                $ugroup
            );
        }

        return $this->ugroup_presenters[$ugroup_id];
    }

    private function importMembers(Project $project, PFUser $project_admin)
    {
        $import_file = $_FILES['user_filename']['tmp_name'];

        if (! file_exists($import_file) || ! is_readable($import_file)) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('You should provide a file in entry.'));
            return;
        }

        $user_collection = $this->user_importer->parse($project->getID(), $import_file);
        if ($user_collection) {
            $this->user_importer->updateDB($project, $user_collection, $project_admin);
            $this->user_group_bindings->reloadUgroupBindingInProject($project);
        }
    }

    private function canUserSeeUGroups(PFUser $user, Project $project)
    {
        return $user->isAdmin($project->getID());
    }
}
