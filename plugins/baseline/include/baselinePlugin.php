<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

use Tuleap\Baseline\Adapter\Administration\BaselineUserGroupRetriever;
use Tuleap\Baseline\Adapter\Administration\PermissionPerGroupBaselineServicePaneBuilder;
use Tuleap\Baseline\Adapter\Administration\RoleAssignmentsHistoryEntryAdder;
use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Adapter\Routing\RejectNonBaselineAdministratorMiddleware;
use Tuleap\Baseline\Adapter\UserGroupProxy;
use Tuleap\Baseline\BaselineTuleapService;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleAssignmentsDeleter;
use Tuleap\Baseline\Domain\RoleAssignmentsHistorySaver;
use Tuleap\Baseline\REST\BaselineRestResourcesInjector;
use Tuleap\Baseline\ServiceController;
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\Project\Admin\History\GetHistoryKeyLabel;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\Service\UserCanAccessToServiceEvent;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

class baselinePlugin extends Plugin implements PluginWithService // @codingStandardsIgnoreLine
{
    public const string NAME              = 'baseline';
    public const string SERVICE_SHORTNAME = 'plugin_baseline';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-baseline', __DIR__ . '/../site-content');
    }

    #[Override]
    public function getDependencies(): array
    {
        return ['tracker'];
    }

    #[Override]
    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    #[Override]
    public function getPluginInfo(): \PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \PluginInfo($this);
            $this->pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-baseline', 'Baseline'),
                    dgettext('tuleap-baseline', 'Set and compare baseline of items')
                )
            );
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function userCanAccessToService(UserCanAccessToServiceEvent $event): void
    {
        if ($event->getService()->getShortName() !== self::SERVICE_SHORTNAME) {
            return;
        }

        $authorizations = ContainerBuilderFactory::create()->build()->get(Authorizations::class);
        assert($authorizations instanceof Authorizations);

        $user    = \Tuleap\Baseline\Adapter\UserProxy::fromUser($event->getUser());
        $project = \Tuleap\Baseline\Adapter\ProjectProxy::buildFromProject($event->getService()->getProject());

        $can_read_baselines_on_project   = $authorizations->canReadBaselinesOnProject($user, $project);
        $can_read_comparisons_on_project = $authorizations->canReadComparisonsOnProject($user, $project);

        if (! $can_read_baselines_on_project && ! $can_read_comparisons_on_project) {
            $event->forbidAccessToService();
        }
    }

    #[Override]
    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
    {
        $event->addService(self::SERVICE_SHORTNAME, BaselineTuleapService::class);
    }

    /**
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     *
     * @see Event::SERVICE_IS_USED
     */
    #[Override]
    public function serviceIsUsed(array $params): void
    {
        // nothing to do for baseline
    }

    #[Override]
    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for baseline
    }

    #[Override]
    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for baseline
    }

    #[Override]
    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for baseline
    }

    public function routeGetSlash(): ServiceController
    {
        $container = ContainerBuilderFactory::create()->build();

        return new ServiceController(
            ProjectManager::instance(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates'),
            $this,
            new ProjectFlagsBuilder(new ProjectFlagsDao()),
            $container->get(Authorizations::class),
        );
    }

    public function routeGetProjectAdmin(): \Tuleap\Request\DispatchableWithRequest
    {
        $container = ContainerBuilderFactory::create()->build();

        return new \Tuleap\Baseline\ServiceAdministrationController(
            \Tuleap\Http\HTTPFactoryBuilder::responseFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            $this,
            TemplateRendererFactory::build(),
            new \Tuleap\Baseline\Adapter\Administration\AdminPermissionsPresenterBuilder(
                new User_ForgeUserGroupFactory(new UserGroupDao()),
                $container->get(RoleAssignmentRepository::class),
            ),
            new \Tuleap\Baseline\CSRFSynchronizerTokenProvider(),
            new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter(),
            new \Tuleap\Http\Server\ServiceInstrumentationMiddleware(self::NAME),
            new \Tuleap\Project\Routing\ProjectByNameRetrieverMiddleware(\Tuleap\Request\ProjectRetriever::buildSelf()),
            new RejectNonBaselineAdministratorMiddleware(
                UserManager::instance(),
                new \Tuleap\Project\Admin\Routing\ProjectAdministratorChecker(),
                $container->get(Authorizations::class),
            )
        );
    }

    public function routePostProjectAdmin(): \Tuleap\Request\DispatchableWithRequest
    {
        $container = ContainerBuilderFactory::create()->build();

        return new \Tuleap\Baseline\ServiceSavePermissionsController(
            new \Tuleap\Baseline\Domain\RoleAssignmentsSaver(
                $container->get(RoleAssignmentRepository::class),
                new BaselineUserGroupRetriever(ProjectManager::instance(), new UGroupManager()),
                new RoleAssignmentsHistorySaver(
                    new RoleAssignmentsHistoryEntryAdder(
                        new ProjectHistoryDao(),
                        ProjectManager::instance(),
                        UserManager::instance(),
                    )
                ),
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                \Tuleap\Http\HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new \Tuleap\Baseline\CSRFSynchronizerTokenProvider(),
            new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter(),
            new \Tuleap\Http\Server\ServiceInstrumentationMiddleware(self::NAME),
            new \Tuleap\Project\Routing\ProjectByNameRetrieverMiddleware(\Tuleap\Request\ProjectRetriever::buildSelf()),
            new RejectNonBaselineAdministratorMiddleware(
                UserManager::instance(),
                new \Tuleap\Project\Admin\Routing\ProjectAdministratorChecker(),
                $container->get(Authorizations::class),
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            $this->getPluginPath(),
            function (FastRoute\RouteCollector $r) {
                $r->get(
                    '/{' . ServiceController::PROJECT_NAME_VARIABLE_NAME . '}/admin',
                    $this->getRouteHandler('routeGetProjectAdmin')
                );
                $r->post(
                    '/{' . ServiceController::PROJECT_NAME_VARIABLE_NAME . '}/admin',
                    $this->getRouteHandler('routePostProjectAdmin')
                );
                $r->get(
                    '/{' . ServiceController::PROJECT_NAME_VARIABLE_NAME . '}[/{vue-routing:.*}]',
                    $this->getRouteHandler('routeGetSlash')
                );
            }
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources($params): void
    {
        $injector = new BaselineRestResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getHistoryKeyLabel(GetHistoryKeyLabel $event): void
    {
        $label = RoleAssignmentsHistorySaver::getLabelFromKey($event->getKey());
        if ($label) {
            $event->setLabel($label);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('fill_project_history_sub_events')]
    public function fillProjectHistorySubEvents(array $params): void
    {
        RoleAssignmentsHistorySaver::fillProjectHistorySubEvents($params);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event): void
    {
        $project = $event->getProject();
        $service = $project->getService(self::SERVICE_SHORTNAME);
        if (! $service instanceof BaselineTuleapService) {
            return;
        }

        if (! $this->isAllowed($project->getID())) {
            return;
        }

        $ugroup_manager = new UGroupManager();
        $container      = ContainerBuilderFactory::create()->build();

        $service_pane_builder = new PermissionPerGroupBaselineServicePaneBuilder(
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            $container->get(RoleAssignmentRepository::class),
            $ugroup_manager,
        );

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(__DIR__ . '/../templates')
            ->renderToString(
                'project-admin-permission-per-group',
                $service_pane_builder->buildPresenter($event)
            );

        $rank_in_project = $service->getRank();
        $event->addPane($admin_permission_pane, $rank_in_project);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(
        NavigationDropdownQuickLinksCollector $quick_links_collector,
    ): void {
        $project = $quick_links_collector->getProject();
        $service = $project->getService(self::SERVICE_SHORTNAME);
        if (! $service instanceof BaselineTuleapService) {
            return;
        }

        if (! $this->isAllowed($project->getID())) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-baseline', 'Baseline'),
                \Tuleap\Baseline\ServiceAdministrationController::getAdminUrl($project),
            )
        );
    }

    #[Override]
    public function serviceEnableForXmlImportRetriever(\Tuleap\Project\XML\ServiceEnableForXmlImportRetriever $event): void
    {
    }

    #[\Tuleap\Plugin\ListeningToEventName('project_admin_ugroup_deletion')]
    public function projectAdminUgroupDeletion(array $params): void
    {
        $project = $params['project'];
        $ugroup  = $params['ugroup'];

        $user_group_proxy = UserGroupProxy::fromProjectUGroup($ugroup);
        $project_proxy    = ProjectProxy::buildFromProject($project);

        (new RoleAssignmentsDeleter(
            ContainerBuilderFactory::create()->build()->get(RoleAssignmentRepository::class),
            new RoleAssignmentsHistorySaver(
                new RoleAssignmentsHistoryEntryAdder(
                    new ProjectHistoryDao(),
                    ProjectManager::instance(),
                    UserManager::instance(),
                )
            ),
        ))->deleteRoleAssignments(
            $project_proxy,
            $user_group_proxy,
        );
    }
}
