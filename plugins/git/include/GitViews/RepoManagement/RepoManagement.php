<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\Git\GitViews\RepoManagement\Pane;
use Tuleap\Git\GitViews\RepoManagement\Pane\CIBuilds;
use Tuleap\Git\GitViews\RepoManagement\Pane\PanesCollection;
use Tuleap\Git\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Git\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\Git\Webhook\WebhookDao;
use Tuleap\Git\Webhook\WebhookFactory;

/**
 * Dedicated screen for repo management
 */
class GitViews_RepoManagement // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * @var Pane\Pane[]
     */
    private array $panes;
    private string $current_pane;

    public function __construct(
        private GitRepository $repository,
        private \Tuleap\HTTPRequest $request,
        private Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        private array $gerrit_servers,
        private array $gerrit_config_templates,
        private GerritCanMigrateChecker $gerrit_can_migrate_checker,
        private FineGrainedPermissionFactory $fine_grained_permission_factory,
        private FineGrainedRetriever $fine_grained_retriever,
        private FineGrainedRepresentationBuilder $fine_grained_builder,
        private DefaultFineGrainedPermissionFactory $default_fine_grained_factory,
        private GitPermissionsManager $git_permission_manager,
        private RegexpFineGrainedRetriever $regexp_retriever,
        private EventManager $event_manager,
        private ProjectManager $project_manager,
        private VerifyArtifactClosureIsAllowed $closure_verifier,
        private readonly User_ForgeUserGroupFactory $user_group_factory,
    ) {
        $this->panes        = $this->buildPanes($repository);
        $this->current_pane = 'settings';

        if (isset($this->panes[$request->get('pane')])) {
            $this->current_pane = $request->get('pane');
        }
    }

    /**
     * @return Pane\Pane[]
     */
    private function buildPanes(GitRepository $repository): array
    {
        $collection = new PanesCollection($repository, $this->request);
        $collection->add(new Pane\GeneralSettings($repository, $this->request, $this->closure_verifier));

        if ($repository->getBackendType() == GitDao::BACKEND_GITOLITE) {
            $collection->add(
                new Pane\Gerrit(
                    $repository,
                    $this->request,
                    $this->driver_factory,
                    $this->gerrit_can_migrate_checker,
                    $this->gerrit_servers,
                    $this->gerrit_config_templates,
                    $this->project_manager,
                    TemplateRendererFactory::build()->getRenderer(__DIR__ . '/Pane/Gerrit/'),
                    new Git_Driver_Gerrit_ProjectCreatorStatus(
                        new Git_Driver_Gerrit_ProjectCreatorStatusDao()
                    ),
                )
            );
        }

        $collection->add(new Pane\AccessControl(
            $repository,
            $this->request,
            $this->fine_grained_permission_factory,
            $this->fine_grained_retriever,
            $this->fine_grained_builder,
            $this->default_fine_grained_factory,
            $this->git_permission_manager,
            $this->regexp_retriever
        ));
        $collection->add(new CIBuilds($repository, $this->request));

        $webhook_dao                  = new WebhookDao();
        $webhook_factory              = new WebhookFactory($webhook_dao);
        $user_to_be_notified_builder  = new CollectionOfUserToBeNotifiedPresenterBuilder(new UsersToNotifyDao());
        $group_to_be_notified_builder = new CollectionOfUgroupToBeNotifiedPresenterBuilder(
            new UgroupsToNotifyDao()
        );

        $collection->add(new Pane\Notification(
            $repository,
            $this->request,
            $this->event_manager,
            $user_to_be_notified_builder,
            $group_to_be_notified_builder,
            $this->user_group_factory,
        ));
        $collection->add(new Pane\Hooks($repository, $this->request, $webhook_factory, $webhook_dao));

        $this->event_manager->processEvent($collection);

        $collection->add(new Pane\Delete($repository, $this->request));

        $indexed_panes = [];
        foreach ($collection->getPanes() as $pane) {
            if ($pane->canBeDisplayed()) {
                $indexed_panes[$pane->getIdentifier()] = $pane;
            }
        }
        return $indexed_panes;
    }

    public function display(): void
    {
        echo '<div class="main-project-tabs"><nav class="tlp-tabs">';
        foreach ($this->panes as $pane) {
            $this->displayTab($pane);
        }
        echo '</nav></div>';
        echo '<div class="tlp-framed">';
        echo $this->panes[$this->current_pane]->getContent();
        echo '</div>';
    }

    private function displayTab(Pane\Pane $pane): void
    {
        $url      = GIT_BASE_URL . '/?' . http_build_query(
            [
                'action' => 'repo_management',
                'group_id' => $this->repository->getProjectId(),
                'repo_id'  => $this->repository->getId(),
                'pane'     => $pane->getIdentifier(),
            ]
        );
        $purifier = Codendi_HTMLPurifier::instance();

        echo '<a href="' . $url . '" title="' . $purifier->purify($pane->getTitle()) . '" data-test="' . $purifier->purify($pane->getIdentifier()) . '"
            class="tlp-tab ' . ($this->current_pane == $pane->getIdentifier() ? 'tlp-tab-active' : '') . '"
        >' . $purifier->purify($pane->getLabel()) . '</a>';
    }

    public function addPaneAssets(): void
    {
        assert($GLOBALS['HTML'] instanceof \Tuleap\Layout\BaseLayout);
        foreach ($this->panes[$this->current_pane]->getJavascriptAssets() as $asset) {
            $GLOBALS['HTML']->addJavascriptAsset($asset);
        }
    }
}
