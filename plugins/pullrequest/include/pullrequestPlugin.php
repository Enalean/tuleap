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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Git\DefaultSettings\Pane\DefaultSettingsPanesCollection;
use Tuleap\Git\Events\AfterRepositoryCreated;
use Tuleap\Git\Events\AfterRepositoryForked;
use Tuleap\Git\Events\GetPullRequestDashboardViewEvent;
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
use Tuleap\Git\PullRequestEndpointsAvailableEvent;
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
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Project\Admin\GetProjectHistoryEntryValue;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Dao as PullRequestDao;
use Tuleap\PullRequest\DefaultSettings\DefaultSettingsController;
use Tuleap\PullRequest\DefaultSettings\PullRequestPane as DefaultSettingsPullRequestPane;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\FileUniDiffBuilder;
use Tuleap\PullRequest\FrontendApps\FeatureFlagSetOldHomepageViewByDefault;
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
use Tuleap\PullRequest\PullRequestEmptyStatePresenterBuilder;
use Tuleap\PullRequest\PullRequestMerger;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\PullRequestUpdater;
use Tuleap\PullRequest\Reference\CrossReferencePullRequestOrganizer;
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
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Request\CollectRoutesEvent;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class pullrequestPlugin extends Plugin implements PluginWithConfigKeys
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
    }

    public function getServiceShortname(): string
    {
        return 'plugin_pullrequest';
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['git'];
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectAssets(CollectAssets $retriever): void
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../scripts/create-pullrequest-button/frontend-assets',
            '/assets/pullrequest/create-pullrequest-button'
        );

        $css_assets = new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons(
            $assets,
            'repository-style'
        );
        $retriever->addStylesheet($css_assets);

        $create_pullrequest = new JavascriptAsset($assets, 'create-pullrequest-button.js');
        $retriever->addScript($create_pullrequest);
    }

    /**
     * @return Tuleap\PullRequest\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function addRESTResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function pullRequestEndpointsAvailableEvent(PullRequestEndpointsAvailableEvent $event): void
    {
        $event->endpointsAreAvailable();
    }

    #[\Tuleap\Plugin\ListeningToEventName(GIT_ADDITIONAL_BODY_CLASSES)]
    public function gitAdditionalBodyClasses($params): void
    {
        if ($params['request']->get('action') === 'pull-requests') {
            $params['classes'][] = 'git-pull-requests';
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(GIT_ADDITIONAL_PERMITTED_ACTIONS)]
    public function gitAdditionalPermittedActions($params): void
    {
        $repository = $params['repository'];
        $user       = $params['user'];

        if ($repository && $repository->userCanRead($user) && ! $repository->isMigratedToGerrit()) {
            $params['permitted_actions'][] = 'pull-requests';
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function gitAdditionalAction(GitAdditionalActionEvent $event): void
    {
        if ($event->getRequest()->get('action') === 'pull-requests') {
            $layout = $this->getThemeManager()->getBurningParrot(UserManager::instance()->getCurrentUserWithLoggedInInformation());
            if ($layout === null) {
                throw new \Exception("Could not load BurningParrot theme");
            }
            $this->getPullRequestDisplayer()->display($event->getRequest(), $layout);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function postReceiveExecuteEvent(PostReceiveExecuteEvent $event): void
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
                if (! $user->isAnonymous()) {
                    $this->markManuallyMerged($user, $repository, $branch_name, $new_rev);
                }

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
        }
    }

    private function markManuallyMerged(
        PFUser $user,
        GitRepository $dest_repository,
        $dest_branch_name,
        $new_rev,
    ) {
        $pull_request_factory   = $this->getPullRequestFactory();
        $git_repository_factory = $this->getRepositoryFactory();
        $closer                 = $this->getPullRequestCloser();

        $prs = $pull_request_factory->getOpenedByDestinationBranch($dest_repository, $dest_branch_name);

        foreach ($prs as $pr) {
            $repository = $git_repository_factory->getRepositoryById($pr->getRepoDestId());
            $git_exec   = new GitExec($repository->getFullPath(), $repository->getFullPath());
            if ($git_exec->isAncestor($new_rev, $pr->getSha1Src())) {
                $closer->closeManuallyMergedPullRequest($pr, $user);
            }
        }
    }

    private function abandonFromSourceBranch(PFUser $user, GitRepository $repository, $branch_name)
    {
        $pull_request_factory = $this->getPullRequestFactory();
        $closer               = $this->getPullRequestCloser();

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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getReference(GetReferenceEvent $event): void
    {
        $keyword         = $event->getKeyword();
        $pull_request_id = (int) $event->getValue();

        if ($this->isReferenceAPullRequestReference($keyword)) {
            $reference = (new ReferenceFactory(
                new PullRequestRetriever(new PullRequestDao()),
                $this->getRepositoryFactory(),
                new ProjectReferenceRetriever(new ReferenceDao()),
                $this->getHTMLBuilder()
            ))->getReferenceByPullRequestId(
                $keyword,
                $pull_request_id
            );

            if ($reference !== null) {
                $event->setReference($reference);
            }
        }
    }

    private function isReferenceAPullRequestReference($keyword)
    {
        return $keyword === self::PR_REFERENCE_KEYWORD || $keyword === self::PULLREQUEST_REFERENCE_KEYWORD;
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES)]
    public function getPluginsAvailableKeywordsReferences($params): void
    {
        $params['keywords'] = array_merge(
            $params['keywords'],
            [self::PR_REFERENCE_KEYWORD, self::PULLREQUEST_REFERENCE_KEYWORD]
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            self::REFERENCE_NATURE,
            new Nature('pullrequest', 'fas fa-tlp-versioning-git', 'Git Pull Request', true)
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event): void
    {
        if ($event->getReference()->getNature() === self::REFERENCE_NATURE) {
                $pull_request_id = $event->getValue();
            (new PullRequestRetriever(new PullRequestDao()))->getPullRequestById((int) $pull_request_id)->match(
                function (\Tuleap\PullRequest\PullRequest $pull_request) use ($event) {
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
                },
                function () {
                    //do nothing No tooltip
                }
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectionOfLabelableDao(CollectionOfLabelableDao $event): void
    {
        $event->add(new PullRequestLabelDao());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function gitRepositoryDeletion(GitRepositoryDeletionEvent $event): void
    {
        $dao = new PullRequestDao();
        $dao->deleteAllPullRequestsOfRepository($event->getRepository()->getId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectGlyphLocations(GlyphLocationsCollector $glyph_locations_collector): void
    {
        $glyph_locations_collector->addLocation(
            'tuleap-pullrequest',
            new GlyphLocation(PULLREQUEST_BASE_DIR . '/glyphs')
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectLabeledItems(LabeledItemCollection $event): void
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (! ($git_plugin instanceof GitPlugin)) {
            throw new Exception("Pullrequest plugin cannot find git plugin");
        }

        $labeled_item_collector = new LabeledItemCollector(
            new PullRequestLabelDao(),
            new PullRequestRetriever(new PullRequestDao()),
            $this->getPullRequestPermissionsChecker(),
            $this->getHTMLBuilder(),
            new GlyphFinder(
                EventManager::instance()
            ),
            new GitRepositoryFactory(new GitDao(), ProjectManager::instance()),
            UserManager::instance(),
            new UserHelper(),
            $this->getGitRepositoryUrlManager(),
            $this->getTemplateRenderer()
        );

        $labeled_item_collector->collect($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function canProjectUseLabels(CanProjectUseLabels $event): void
    {
        if ($event->getProject()->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            $event->projectCanUseLabels();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProtectedGitReferences(GetProtectedGitReferences $event): void
    {
        $event->addProtectedReference(new ProtectedReferencePermission(GitPullRequestReference::PR_NAMESPACE . '*'));
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function markTechnicalReference(MarkTechnicalReference $event): void
    {
        if (strpos($event->getReferenceName(), GitPullRequestReference::PR_NAMESPACE) === 0) {
            $event->markAsTechnical();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function postInitGitRepositoryWithDataEvent(PostInitGitRepositoryWithDataEvent $event): void
    {
        (new GitPullRequestReferenceRemover())->removeAll(GitExec::buildFromRepository($event->getRepository()));
    }

    public static function getLogger()
    {
        return BackendLogger::getDefaultLogger('pullrequest_syslog');
    }

    #[\Tuleap\Plugin\ListeningToEventName('codendi_daily_start')]
    public function codendiDailyStart(): void
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function additionalInformationRepresentationRetriever(AdditionalInformationRepresentationRetriever $event): void
    {
        $this->getGitRestRouteAdditionaInformations()->getOpenPullRequestsCount($event);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function additionalInformationRepresentationCache(AdditionalInformationRepresentationCache $event): void
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function afterRepositoryForked(AfterRepositoryForked $event): void
    {
        $dao = new MergeSettingDAO();
        $dao->duplicateRepositoryMergeSettings(
            $event->getBaseRepository()->getId(),
            $event->getForkedRepository()->getId()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function afterRepositoryCreated(AfterRepositoryCreated $event): void
    {
        $repository = $event->getRepository();
        $dao        = new MergeSettingDAO();

        $dao->inheritFromTemplate(
            $repository->getId(),
            $repository->getProjectId()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $dao = new MergeSettingDAO();
        $dao->duplicateFromProjectTemplate(
            (int) $event->getTemplateProject()->getID(),
            (int) $event->getJustCreatedProject()->getID(),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectPanes(PanesCollection $collection): void
    {
        $collection->add(
            new PullRequestPane(
                $collection->getRepository(),
                $collection->getRequest(),
                new MergeSettingRetriever(new MergeSettingDAO())
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectDefaultSettingsPanes(DefaultSettingsPanesCollection $collection): void
    {
        $collection->add(
            new DefaultSettingsPullRequestPane(
                new MergeSettingRetriever(new MergeSettingDAO()),
                $collection->getProject(),
                $collection->getCurrentPane() === DefaultSettingsPullRequestPane::NAME
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getProjectHistoryEntryValue(GetProjectHistoryEntryValue $event): void
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
                new Git_SystemEventManager(SystemEventManager::instance()),
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
            new PullRequestRetriever(new PullRequestDao()),
            $permissions_checker,
            new PotentialReviewerRetriever($user_manager, new UserDao(), $permissions_checker),
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new SapiEmitter()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
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

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function repositoryExternalNavigationTabsCollector(RepositoryExternalNavigationTabsCollector $event): void
    {
        if ($event->getRepository()->isMigratedToGerrit()) {
            return;
        }

        $builder = new NavigationTabPresenterBuilder($this->getHTMLBuilder(), $this->getPullRequestFactory());
        $event->addNewTab($builder->build($event->getRepository(), $event->getSelectedTab()));
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
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
            $this->getRepositoryFactory(),
            new PullRequestEmptyStatePresenterBuilder(
                $this->getGitRepositoryUrlManager(),
                new URLVerification()
            ),
        );
    }

    private function getHTMLBuilder(): HTMLURLBuilder
    {
        return new HTMLURLBuilder(
            $this->getRepositoryFactory()
        );
    }

    private function getGitRepositoryUrlManager(): Git_GitRepositoryUrlManager
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (! ($git_plugin instanceof GitPlugin)) {
            throw new Exception("Pullrequest plugin cannot find git plugin");
        }
        return new Git_GitRepositoryUrlManager($git_plugin);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function crossReferenceByNatureOrganizer(CrossReferenceByNatureOrganizer $organizer): void
    {
        $pull_request_organizer = new CrossReferencePullRequestOrganizer(
            ProjectManager::instance(),
            new PullRequestRetriever(new \Tuleap\PullRequest\Dao()),
            $this->getPullRequestPermissionsChecker(),
            $this->getRepositoryFactory(),
            new \Tuleap\Date\TlpRelativeDatePresenterBuilder(),
            UserManager::instance(),
            UserHelper::instance(),
        );

        $pull_request_organizer->organizePullRequestReferences($organizer);
    }

    public function getConfigKeys(\Tuleap\Config\ConfigClassProvider $event): void
    {
        $event->addConfigClass(FeatureFlagSetOldHomepageViewByDefault::class);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function getPullRequestDashboardViewEvent(GetPullRequestDashboardViewEvent $event): void
    {
        $event->setIsOldViewEnabled(FeatureFlagSetOldHomepageViewByDefault::isActive());
    }
}
