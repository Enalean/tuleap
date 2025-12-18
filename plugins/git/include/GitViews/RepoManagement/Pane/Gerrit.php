<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use Git_Driver_Gerrit;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_Template_Template;
use Git_RemoteServer_Gerrit_ProjectNameBuilder;
use Git_RemoteServer_GerritServer;
use GitRepository;
use ProjectManager;
use TemplateRenderer;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Git\Driver\Gerrit\UnsupportedGerritVersionException;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\DisconnectFromGerritPanePresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\GerritMigrationFailurePresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\GerritPanePresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\GerritRepositoryPresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\GerritServerPresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\GerritTemplatePresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\MigrateToGerritPanePresenter;
use Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit\MigrationToGerritFailedFault;
use Tuleap\Git\RemoteServer\GerritCanMigrateChecker;
use Tuleap\HTTPRequest;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final class Gerrit extends Pane
{
    public const string OPTION_DISCONNECT_GERRIT_PROJECT = 'gerrit_project_delete';
    public const string OPTION_DELETE_GERRIT_PROJECT     = 'delete';
    public const string OPTION_READONLY_GERRIT_PROJECT   = 'read-only';
    public const string ID                               = 'gerrit';
    public const string CONFIRM_DISCONNECT_ACTION        = 'confirm_disconnect_gerrit';

    /**
     * @param Git_RemoteServer_GerritServer[]       $gerrit_servers
     * @param Git_Driver_Gerrit_Template_Template[] $gerrit_config_templates
     */
    public function __construct(
        GitRepository $repository,
        HTTPRequest $request,
        private readonly Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        private readonly GerritCanMigrateChecker $gerrit_can_migrate_checker,
        private readonly array $gerrit_servers,
        private readonly array $gerrit_config_templates,
        private readonly ProjectManager $project_manager,
        private readonly TemplateRenderer $template_renderer,
        private readonly Git_Driver_Gerrit_ProjectCreatorStatus $gerrit_creator_status,
    ) {
        parent::__construct($repository, $request);
    }

    /**
     * @return bool true if the pane can be displayed
     */
    #[\Override]
    public function canBeDisplayed(): bool
    {
        return $this->gerrit_can_migrate_checker->canMigrate($this->repository->getProject());
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    #[\Override]
    public function getIdentifier(): string
    {
        return self::ID;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    #[\Override]
    public function getTitle(): string
    {
        return dgettext('tuleap-git', 'Gerrit');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    #[\Override]
    public function getContent(): string
    {
        if ($this->repository->isMigratedToGerrit()) {
            try {
                $user = HTTPRequest::instance()->getCurrentUser();
                return $this->getContentAlreadyMigrated($user);
            } catch (UnsupportedGerritVersionException) {
                return sprintf(
                    '<div class="tlp-alert-danger">%s</div>',
                    dgettext(
                        'tuleap-git',
                        'You are using a version of Gerrit that is not supported, please contact your site administrators.'
                    )
                );
            }
        }

        $parent       = $this->project_manager->getParentProject($this->repository->getProjectId());
        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        $presenter = new MigrateToGerritPanePresenter(
            $this->csrf_token(),
            $this->repository,
            Option::fromNullable($parent),
            $name_builder->getGerritProjectName($this->repository),
            $this->buildGerritServerPresenters(),
            $this->buildGerritTemplatePresenters(),
        );
        return $this->template_renderer->renderToString('settings-pane-migrate-to-gerrit', $presenter);
    }

    #[\Override]
    public function getJavascriptAssets(): array
    {
        return [
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../../scripts/repository-admin/frontend-assets',
                    '/assets/git/repository-admin'
                ),
                'src/gerrit-pane.ts'
            ),
        ];
    }

    /**
     * @return list<GerritServerPresenter>
     */
    private function buildGerritServerPresenters(): array
    {
        $presenters = [];
        foreach ($this->gerrit_servers as $server) {
            $driver = $this->driver_factory->getDriver($server);
            try {
                $is_delete_plugin_enabled = (int) $driver->isDeletePluginEnabled($server);
                $should_delete            = (int) $this->doesRemoteGerritProjectNeedDeleting($server);
            } catch (UnsupportedGerritVersionException) {
                continue;
            }
            $presenters[] = new GerritServerPresenter($server, $is_delete_plugin_enabled, $should_delete);
        }
        return $presenters;
    }

    /**
     * @return list<GerritTemplatePresenter>
     */
    private function buildGerritTemplatePresenters(): array
    {
        return array_values(
            array_map(
                static fn(Git_Driver_Gerrit_Template_Template $template) => new GerritTemplatePresenter($template),
                $this->gerrit_config_templates
            )
        );
    }

    private function doesRemoteGerritProjectNeedDeleting(Git_RemoteServer_GerritServer $server): bool
    {
        if ($server->getId() != $this->repository->getRemoteServerId()) {
            return false;
        }

        if (! $this->repository->wasPreviouslyMigratedButNotDeleted()) {
            return false;
        }

        $driver       = $this->getGerritDriverForRepository($this->repository);
        $project_name = $driver->getGerritProjectName($this->repository);
        try {
            if (! $driver->doesTheProjectExist($server, $project_name)) {
                return false;
            }
        } catch (Git_Driver_Gerrit_Exception $e) {
            return false;
        }

        return true;
    }

    private function getContentAlreadyMigrated(\PFUser $user): string
    {
        if ($this->request->get(self::CONFIRM_DISCONNECT_ACTION)) {
            $disconnect_presenter = new DisconnectFromGerritPanePresenter(
                $this->csrf_token(),
                $this->repository,
                $this->request->get(self::OPTION_DISCONNECT_GERRIT_PROJECT) ?? '',
            );
            return $this->template_renderer->renderToString('settings-pane-gerrit-confirm-disconnect', $disconnect_presenter);
        }

        $presenter = $this->getPresenterBasedOnMigrationStatus()
            ->match(
                fn(?GerritRepositoryPresenter $repository_presenter) => new GerritPanePresenter(
                    $this->csrf_token(),
                    $this->repository,
                    $repository_presenter,
                    null,
                ),
                function (MigrationToGerritFailedFault $fault) use ($user) {
                    $date_builder      = new TlpRelativeDatePresenterBuilder();
                    $migration_failure = new GerritMigrationFailurePresenter(
                        $date_builder->getTlpRelativeDatePresenterInInlineContext($fault->date, $user),
                        $fault->logs
                    );
                    return new GerritPanePresenter(
                        $this->csrf_token(),
                        $this->repository,
                        null,
                        $migration_failure,
                    );
                }
            );
        return $this->template_renderer->renderToString('settings-pane-gerrit', $presenter);
    }

    /**
     * @return Ok<GerritRepositoryPresenter> | Ok<null> | Err<MigrationToGerritFailedFault>
     */
    private function getPresenterBasedOnMigrationStatus(): Ok|Err
    {
        $status = $this->gerrit_creator_status->getStatus($this->repository);
        if ($status === Git_Driver_Gerrit_ProjectCreatorStatus::ERROR) {
            $migration_date = \DateTimeImmutable::createFromTimestamp(
                $this->gerrit_creator_status->getEventDate($this->repository)
            );
            return Result::err(
                new MigrationToGerritFailedFault(
                    $migration_date,
                    $this->gerrit_creator_status->getLog($this->repository),
                )
            );
        }
        if ($status === null || $status === Git_Driver_Gerrit_ProjectCreatorStatus::DONE) {
            return Result::ok(
                new GerritRepositoryPresenter(
                    $this->repository,
                    $this->getGerritDriverForRepository($this->repository),
                    $this->getGerritServerForRepository($this->repository),
                )
            );
        }
        return Result::ok(null);
    }

    private function getGerritDriverForRepository(GitRepository $repository): Git_Driver_Gerrit
    {
        $server = $this->getGerritServerForRepository($repository);

        return $this->driver_factory->getDriver($server);
    }

    private function getGerritServerForRepository(GitRepository $repository): Git_RemoteServer_GerritServer
    {
        return $this->gerrit_servers[$repository->getRemoteServerId()];
    }
}
