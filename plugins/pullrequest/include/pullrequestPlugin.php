<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

require_once 'constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';

use Tuleap\Git\DefaultSettings\Pane\DefaultSettingsPanesCollection;
use Tuleap\Git\Events\AfterRepositoryCreated;
use Tuleap\Git\Events\AfterRepositoryForked;
use Tuleap\Git\GitAdditionalActionEvent;
use Tuleap\Git\GitRepositoryDeletionEvent;
use Tuleap\Git\GitViews\RepoManagement\Pane\PanesCollection;
use Tuleap\Git\Hook\PostReceiveExecuteEvent;
use Tuleap\Git\MarkTechnicalReference;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\GetProtectedGitReferences;
use Tuleap\Git\Permissions\ProtectedReferencePermission;
use Tuleap\Git\PostInitGitRepositoryWithDataEvent;
use Tuleap\Git\Repository\AdditionalInformationRepresentationCache;
use Tuleap\Git\Repository\AdditionalInformationRepresentationRetriever;
use Tuleap\Git\Repository\CollectAssets;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayerBuilder;
use Tuleap\Git\Repository\View\RepositoryExternalNavigationTabsCollector;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Glyph\GlyphLocation;
use Tuleap\Glyph\GlyphLocationsCollector;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Label\CanProjectUseLabels;
use Tuleap\Label\CollectionOfLabelableDao;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\layout\ScriptAsset;
use Tuleap\Project\Admin\GetProjectHistoryEntryValue;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\DefaultSettings\DefaultSettingsController;
use Tuleap\PullRequest\DefaultSettings\PullRequestPane as DefaultSettingsPullRequestPane;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceBulkConverter;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceDAO;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceNamespaceAvailabilityChecker;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceRemover;
use Tuleap\PullRequest\GitReference\GitPullRequestReferenceUpdater;
use Tuleap\PullRequest\GitRestRouteAdditionalInformations;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;
use Tuleap\PullRequest\InlineComment\InlineCommentUpdater;
use Tuleap\PullRequest\Label\LabeledItemCollector;
use Tuleap\PullRequest\Label\PullRequestLabelDao;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;
use Tuleap\PullRequest\NavigationTab\NavigationTabPresenterBuilder;
use Tuleap\PullRequest\Notification\PullRequestNotificationSupport;
use Tuleap\PullRequest\PluginInfo;
use Tuleap\PullRequest\PullRequestCloser;
use Tuleap\PullRequest\PullrequestDisplayer;
use Tuleap\PullRequest\PullRequestMerger;
use Tuleap\PullRequest\PullRequestUpdater;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Reference\ProjectReferenceRetriever;
use Tuleap\PullRequest\Reference\ReferenceDao;
use Tuleap\PullRequest\Reference\ReferenceFactory;
use Tuleap\PullRequest\RepoManagement\PullRequestPane;
use Tuleap\PullRequest\RepoManagement\RepoManagementController;
use Tuleap\PullRequest\REST\ResourcesInjector;
use Tuleap\PullRequest\Reviewer\Autocompleter\PotentialReviewerRetriever;
use Tuleap\PullRequest\Reviewer\Autocompleter\ReviewerAutocompleterController;
use Tuleap\PullRequest\Timeline\Dao as TimelineDao;
use Tuleap\PullRequest\Timeline\TimelineEventCreator;
use Tuleap\PullRequest\Tooltip\Presenter;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

class pullrequestPlugin extends Plugin // phpcs:ignore
{

    public const PR_REFERENCE_KEYWORD          = 'pr';
    public const PULLREQUEST_REFERENCE_KEYWORD = 'pullrequest';
    public const REFERENCE_NATURE              = 'pullrequest';
    private $git_rest_route_additional_informations;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-pullrequest', __DIR__ . '/../site-content/');

        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::GET_AVAILABLE_REFERENCE_NATURE);
        $this->addHook('codendi_daily_start', 'dailyExecution');
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipContentEvent::NAME);
        $this->addHook(CollectionOfLabelableDao::NAME);
        $this->addHook(LabeledItemCollection::NAME);
        $this->addHook(GlyphLocationsCollector::NAME);
        $this->addHook(CanProjectUseLabels::NAME);
        $this->addHook(GetProtectedGitReferences::NAME);
        $this->addHook(MarkTechnicalReference::NAME);
        $this->addHook(PostInitGitRepositoryWithDataEvent::NAME);
        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(GetProjectHistoryEntryValue::NAME);
        $this->addHook(WorkerEvent::NAME);

        if (defined('GIT_BASE_URL')) {
            $this->addHook(REST_GIT_PULL_REQUEST_ENDPOINTS);
            $this->addHook(REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY);
            $this->addHook(GIT_ADDITIONAL_BODY_CLASSES);
            $this->addHook(GIT_ADDITIONAL_PERMITTED_ACTIONS);
            $this->addHook(PostReceiveExecuteEvent::NAME);
            $this->addHook(GitRepositoryDeletionEvent::NAME);
            $this->addHook(GitAdditionalActionEvent::NAME);
            $this->addHook(AdditionalInformationRepresentationRetriever::NAME);
            $this->addHook(AdditionalInformationRepresentationCache::NAME);
            $this->addHook(AfterRepositoryForked::NAME);
            $this->addHook(AfterRepositoryCreated::NAME);
            $this->addHook(PanesCollection::NAME);
            $this->addHook(DefaultSettingsPanesCollection::NAME);
            $this->addHook(CollectAssets::NAME);
            $this->addHook(RepositoryExternalNavigationTabsCollector::NAME);
        }
    }

    public function getServiceShortname()
    {
        return 'plugin_pullrequest';
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('git');
    }

    public function service_classnames($params) // phpcs:ignore
    {
        $params['classnames'][$this->getServiceShortname()] = 'PullRequest\\Service';
    }

    public function collectAssets(CollectAssets $retriever)
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/pullrequest',
            '/assets/pullrequest'
        );

        $css_assets = new CssAsset(
            $assets,
            'repository'
        );
        $retriever->addStylesheet($css_assets);



        $create_pullrequest = new ScriptAsset($assets, 'create-pullrequest-button.js');
        $retriever->addScript($create_pullrequest);
    }

    /**
     * @return Tuleap\PullRequest\PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params) // phpcs:ignore
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_GIT_PULL_REQUEST_ENDPOINTS
     */
    public function rest_git_pull_request_endpoints($params) // phpcs:ignore
    {
        $params['available'] = true;
    }

    /**
     * @see REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY
     */
    public function rest_git_pull_request_get_for_repository($params) // phpcs:ignore
    {
        $version = $params['version'];
        $class   = "\\Tuleap\\PullRequest\\REST\\$version\\RepositoryResource";
        $repository_resource = new $class;

        $params['result'] = $repository_resource->getPaginatedPullRequests(
            $params['repository'],
            $params['query'],
            $params['limit'],
            $params['offset']
        );
    }

    /**
     * @see GIT_ADDITIONAL_BODY_CLASSES
     */
    public function git_additional_body_classes($params) // phpcs:ignore
    {
        if ($params['request']->get('action') === 'pull-requests') {
            $params['classes'][] = 'git-pull-requests';
        }
    }

    /**
     * @see GIT_ADDITIONAL_PERMITTED_ACTIONS
     */
    public function git_additional_permitted_actions($params) // phpcs:ignore
    {
        $repository = $params['repository'];
        $user       = $params['user'];

        if ($repository && $repository->userCanRead($user) && ! $repository->isMigratedToGerrit()) {
            $params['permitted_actions'][] = 'pull-requests';
        }
    }

    public function gitAdditionalAction(GitAdditionalActionEvent $event)
    {
        if ($event->getRequest()->get('action') === 'pull-requests') {
            $layout          = $this->getThemeManager()->getBurningParrot($event->getRequest()->getCurrentUser());
            $this->getPullRequestDisplayer()->display($event->getRequest(), $layout);
        }
    }

    public function postReceiveExecuteEvent(PostReceiveExecuteEvent $event)
    {
        $refname     = $event->getRefname();
        $branch_name = $this->getBranchNameFromRef($refname);

        if ($branch_name != null) {
            $new_rev    = $event->getNewrev();
            $repository = $event->getRepository();
            $user       = $event->getUser();

            if ($new_rev == '0000000000000000000000000000000000000000') {
                $this->abandonFromSourceBranch($user, $repository, $branch_name);
            } else {
                $pull_request_updater = new PullRequestUpdater(
                    $this->getPullRequestFactory(),
                    new PullRequestMerger(
                        new MergeSettingRetriever(new MergeSettingDAO())
                    ),
                    new InlineCommentDao(),
                    new InlineCommentUpdater(),
                    new FileUniDiffBuilder(),
                    $this->getTimelineEventCreator(),
                    $this->getRepositoryFactory(),
                    new \Tuleap\PullRequest\GitExecFactory(),
                    new GitPullRequestReferenceUpdater(
                        new GitPullRequestReferenceDAO(),
                        new GitPullRequestReferenceNamespaceAvailabilityChecker()
                    ),
                    PullRequestNotificationSupport::buildDispatcher(self::getLogger())
                );
                $pull_request_updater->updatePullRequests($user, $repository, $branch_name, $new_rev);
            }

            if (! $user->isAnonymous()) {
                $this->markManuallyMerged($user, $repository, $branch_name, $new_rev);
            }
        }
    }

    private function markManuallyMerged(
        PFUser $user,
        GitRepository $dest_repository,
        $dest_branch_name,
        $new_rev
    ) {
        $pull_request_factory   = $this->getPullRequestFactory();
        $git_repository_factory = $this->getRepositoryFactory();
        $closer                 = $this->getPullRequestCloser();

        $prs = $pull_request_factory->getOpenedByDestinationBranch($dest_repository, $dest_branch_name);

        foreach ($prs as $pr) {
            $repository = $git_repository_factory->getRepositoryById($pr->getRepoDestId());
            $git_exec = new GitExec($repository->getFullPath(), $repository->getFullPath());
            if ($git_exec->isAncestor($new_rev, $pr->getSha1Src())) {
                $closer->closeManuallyMergedPullRequest($pr, $user);
            }
        }
    }

    private function abandonFromSourceBranch(PFUser $user, GitRepository $repository, $branch_name)
    {
        $pull_request_factory   = $this->getPullRequestFactory();
        $closer                 = $this->getPullRequestCloser();

        $prs = $pull_request_factory->getOpenedBySourceBranch($repository, $branch_name);
        foreach ($prs as $pr) {
            $closer->abandon($pr, $user);
        }
    }

    private function getPullRequestCloser(): PullRequestCloser
    {
        return new PullRequestCloser(
            new PullRequestDao(),
            new PullRequestMerger(
                new MergeSettingRetriever(new MergeSettingDAO())
            ),
            $this->getTimelineEventCreator(),
            PullRequestNotificationSupport::buildDispatcher(self::getLogger())
        );
    }

    private function getBranchNameFromRef($refname)
    {
        $prefix = 'refs/heads/';

        if (substr($refname, 0, strlen($prefix)) == $prefix) {
            $refname = substr($refname, strlen($prefix));
            return $refname;
        }

        return null;
    }

    private function getPullRequestFactory()
    {
        return new Factory(new PullRequestDao(), ReferenceManager::instance());
    }

    private function getPullRequestPermissionsChecker(): PullRequestPermissionChecker
    {
        return new PullRequestPermissionChecker(
            $this->getRepositoryFactory(),
            new \Tuleap\Project\ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            ),
            new AccessControlVerifier(
                new FineGrainedRetriever(new FineGrainedDao()),
                new \System_Command()
            )
        );
    }

    private function getRepositoryFactory()
    {
        return new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(PULLREQUEST_BASE_DIR . '/templates');
    }

    private function getTimelineEventCreator()
    {
        return new TimelineEventCreator(new TimelineDao());
    }

    public function get_reference($params) // phpcs:ignore
    {
        $keyword         = $params['keyword'];
        $pull_request_id = $params['value'];

        if ($this->isReferenceAPullRequestReference($keyword)) {
            $params['reference'] = $this->getReferenceFactory()->getReferenceByPullRequestId(
                $keyword,
                $pull_request_id
            );
        }
    }

    /**
     * @return ReferenceFactory
     */
    private function getReferenceFactory()
    {
        return new ReferenceFactory(
            $this->getPullRequestFactory(),
            $this->getRepositoryFactory(),
            new ProjectReferenceRetriever(new ReferenceDao()),
            $this->getHTMLBuilder()
        );
    }

    private function isReferenceAPullRequestReference($keyword)
    {
        return $keyword === self::PR_REFERENCE_KEYWORD || $keyword === self::PULLREQUEST_REFERENCE_KEYWORD;
    }

    public function get_plugins_available_keywords_references($params) // phpcs:ignore
    {
        $params['keywords'] = array_merge(
            $params['keywords'],
            array(self::PR_REFERENCE_KEYWORD, self::PULLREQUEST_REFERENCE_KEYWORD)
        );
    }

    public function get_available_reference_natures($params) // phpcs:ignore
    {
        $nature = array(self::REFERENCE_NATURE => array(
            'keyword' => 'pullrequest',
            'label'   => 'Git Pull Request'
        ));

        $params['natures'] = array_merge($params['natures'], $nature);
    }

    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event)
    {
        if ($event->getReference()->getNature() === self::REFERENCE_NATURE) {
            try {
                $pull_request_id            = $event->getValue();
                $pull_request               = $this->getPullRequestFactory()->getPullRequestById($pull_request_id);
                $pull_request_title         = $pull_request->getTitle();
                $pull_request_status        = $pull_request->getStatus();
                $pull_request_creation_date = $pull_request->getCreationDate();

                $renderer  = $this->getTemplateRenderer();
                $presenter = new Presenter(
                    $pull_request_title,
                    $pull_request_status,
                    $pull_request_creation_date
                );
                $event->setOutput($renderer->renderToString($presenter->getTemplateName(), $presenter));
            } catch (\Tuleap\PullRequest\Exception\PullRequestNotFoundException $exception) {
                // No tooltip
            }
        }
    }

    public function collectionOfLabelableDao(CollectionOfLabelableDao $event)
    {
        $event->add(new PullRequestLabelDao());
    }

    public function gitRepositoryDeletion(GitRepositoryDeletionEvent $event)
    {
        $dao = new PullRequestDao();
        $dao->deleteAllPullRequestsOfRepository($event->getRepository()->getId());
    }

    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector)
    {
        $glyph_locations_collector->addLocation(
            'tuleap-pullrequest',
            new GlyphLocation(PULLREQUEST_BASE_DIR . '/glyphs')
        );
    }

    public function collectLabeledItems(LabeledItemCollection $event)
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');

        $labeled_item_collector = new LabeledItemCollector(
            new PullRequestLabelDao(),
            $this->getPullRequestFactory(),
            $this->getPullRequestPermissionsChecker(),
            $this->getHTMLBuilder(),
            new GlyphFinder(
                EventManager::instance()
            ),
            new GitRepositoryFactory(new GitDao(), ProjectManager::instance()),
            UserManager::instance(),
            new UserHelper(),
            new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder()),
            $this->getTemplateRenderer()
        );

        $labeled_item_collector->collect($event);
    }

    public function canProjectUseLabels(CanProjectUseLabels $event)
    {
        if ($event->getProject()->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            $event->projectCanUseLabels();
        }
    }

    public function getProtectedGitReferences(GetProtectedGitReferences $event)
    {
        $event->addProtectedReference(new ProtectedReferencePermission(GitPullRequestReference::PR_NAMESPACE . '*'));
    }

    public function markTechnicalReference(MarkTechnicalReference $event)
    {
        if (strpos($event->getReferenceName(), GitPullRequestReference::PR_NAMESPACE) === 0) {
            $event->markAsTechnical();
        }
    }

    public function postInitGitRepositoryWithDataEvent(PostInitGitRepositoryWithDataEvent $event)
    {
        (new GitPullRequestReferenceRemover)->removeAll(GitExec::buildFromRepository($event->getRepository()));
    }

    public static function getLogger()
    {
        return BackendLogger::getDefaultLogger('pullrequest_syslog');
    }

    public function dailyExecution()
    {
        $pull_request_git_reference_dao            = new GitPullRequestReferenceDAO();
        $pull_request_git_reference_bulk_converter = new GitPullRequestReferenceBulkConverter(
            $pull_request_git_reference_dao,
            new GitPullRequestReferenceUpdater(
                $pull_request_git_reference_dao,
                new GitPullRequestReferenceNamespaceAvailabilityChecker()
            ),
            $this->getPullRequestFactory(),
            $this->getRepositoryFactory(),
            self::getLogger(),
        );
        $pull_request_git_reference_bulk_converter->convertAllPullRequestsWithoutAGitReference();
    }

    public function additionalInformationRepresentationRetriever(AdditionalInformationRepresentationRetriever $event)
    {
        $this->getGitRestRouteAdditionaInformations()->getOpenPullRequestsCount($event);
    }

    public function additionalInformationRepresentationCache(AdditionalInformationRepresentationCache $event)
    {
        $this->getGitRestRouteAdditionaInformations()->createCache($event);
    }

    private function getGitRestRouteAdditionaInformations()
    {
        if ($this->git_rest_route_additional_informations === null) {
            $this->git_rest_route_additional_informations = new GitRestRouteAdditionalInformations(new PullRequestDao());
        }
        return $this->git_rest_route_additional_informations;
    }

    public function afterRepositoryForked(AfterRepositoryForked $event)
    {
        $dao = new MergeSettingDAO();
        $dao->duplicateRepositoryMergeSettings(
            $event->getBaseRepository()->getId(),
            $event->getForkedRepository()->getId()
        );
    }

    public function afterRepositoryCreated(AfterRepositoryCreated $event)
    {
        $repository = $event->getRepository();
        $dao        = new MergeSettingDAO();

        $dao->inheritFromTemplate(
            $repository->getId(),
            $repository->getProjectId()
        );
    }

    public function register_project_creation(array $params) // phpcs:ignore
    {
        $dao = new MergeSettingDAO();
        $dao->duplicateFromProjectTemplate(
            $params['template_id'],
            $params['group_id']
        );
    }

    public function collectPanes(PanesCollection $collection)
    {
        $collection->add(
            new PullRequestPane(
                $collection->getRepository(),
                $collection->getRequest(),
                new MergeSettingRetriever(new MergeSettingDAO())
            )
        );
    }

    public function collectDefaultSettingsPanes(DefaultSettingsPanesCollection $collection)
    {
        $collection->add(
            new DefaultSettingsPullRequestPane(
                new MergeSettingRetriever(new MergeSettingDAO()),
                $collection->getProject(),
                $collection->getCurrentPane() === DefaultSettingsPullRequestPane::NAME
            )
        );
    }

    public function getProjectHistoryEntryValue(GetProjectHistoryEntryValue $event)
    {
        $project_history_entry = $event->getRow();
        if ($project_history_entry['field_name'] === DefaultSettingsController::HISTORY_FIELD_NAME) {
            $is_merge_commit_allowed = $event->getValue();
            if ($is_merge_commit_allowed) {
                $value = dgettext('tuleap-pullrequest', 'Default (Will fast-forward when possible, fallback to merge when not possible)');
            } else {
                $value = dgettext('tuleap-pullrequest', 'Fast-forward only');
            }

            $event->setValue($value);
        }
    }

    public function routePostRepositorySettings(): RepoManagementController
    {
        $repository_factory = new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
        $fine_grained_dao   = new FineGrainedDao();

        return new RepoManagementController(
            new MergeSettingDAO(),
            $repository_factory,
            new GitPermissionsManager(
                new Git_PermissionsDao(),
                new Git_SystemEventManager(SystemEventManager::instance(), $repository_factory),
                $fine_grained_dao,
                new FineGrainedRetriever($fine_grained_dao)
            )
        );
    }

    public function routePostDefaultSettings(): DefaultSettingsController
    {
        return new DefaultSettingsController(new MergeSettingDAO(), new ProjectHistoryDao());
    }

    public function routeGetAutocompleterReviewers(): ReviewerAutocompleterController
    {
        $user_manager        = UserManager::instance();
        $permissions_checker = $this->getPullRequestPermissionsChecker();

        return new ReviewerAutocompleterController(
            $user_manager,
            $this->getPullRequestFactory(),
            $permissions_checker,
            new PotentialReviewerRetriever($user_manager, new UserDao(), $permissions_checker),
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new SapiEmitter()
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup(
            $this->getPluginPath(),
            function (FastRoute\RouteCollector $r) {
                $r->post(
                    '/repository-settings',
                    $this->getRouteHandler('routePostRepositorySettings')
                );
                $r->post(
                    '/default-settings',
                    $this->getRouteHandler('routePostDefaultSettings')
                );
                $r->get(
                    '/autocompleter_reviewers/{pull_request_id:\d+}',
                    $this->getRouteHandler('routeGetAutocompleterReviewers')
                );
            }
        );
    }

    public function repositoryExternalNavigationTabsCollector(RepositoryExternalNavigationTabsCollector $event)
    {
        if ($event->getRepository()->isMigratedToGerrit()) {
            return;
        }

        $builder = new NavigationTabPresenterBuilder($this->getHTMLBuilder(), $this->getPullRequestFactory());
        $event->addNewTab($builder->build($event->getRepository(), $event->getSelectedTab()));
    }

    public function workerEvent(WorkerEvent $event): void
    {
        PullRequestNotificationSupport::listen($event);
    }

    /**
     * @return ThemeManager
     */
    private function getThemeManager()
    {
        $theme_manager = new \ThemeManager(
            new \Tuleap\BurningParrotCompatiblePageDetector(
                new \Tuleap\Request\CurrentPage(),
                new \User_ForgeUserGroupPermissionsManager(
                    new \User_ForgeUserGroupPermissionsDao()
                )
            )
        );
        return $theme_manager;
    }

    /**
     * @return PullrequestDisplayer
     */
    private function getPullRequestDisplayer()
    {
        $header_builder = new GitRepositoryHeaderDisplayerBuilder();
        return new PullrequestDisplayer(
            $this->getPullRequestFactory(),
            $this->getTemplateRenderer(),
            new MergeSettingRetriever(new MergeSettingDAO()),
            $header_builder->build(NavigationTabPresenterBuilder::TAB_PULLREQUEST),
            $this->getRepositoryFactory()
        );
    }

    private function getHTMLBuilder(): HTMLURLBuilder
    {
        return new HTMLURLBuilder(
            $this->getRepositoryFactory(),
            new \Tuleap\InstanceBaseURLBuilder()
        );
    }
}
