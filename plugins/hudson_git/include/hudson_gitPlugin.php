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

require_once __DIR__ . '/../../hudson/include/hudsonPlugin.php';
require_once __DIR__ . '/../../git/include/gitPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use FastRoute\RouteCollector;
use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Git\CollectGitRoutesEvent;
use Tuleap\Git\Events\ExternalGitHomepagePluginInfos;
use Tuleap\Git\Events\GetExternalGitHomepagePluginsEvent;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\Events\XMLExportExternalContentEvent;
use Tuleap\Git\Events\XMLImportExternalContentEvent;
use Tuleap\Git\GitViews\RepoManagement\Pane\Hooks;
use Tuleap\Git\Hook\PostReceiveExecuteEvent;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\HudsonGit\Git\Administration\AddController;
use Tuleap\HudsonGit\Git\Administration\AdministrationController;
use Tuleap\HudsonGit\Git\Administration\AdministrationPaneBuilder;
use Tuleap\HudsonGit\Git\Administration\AjaxController;
use Tuleap\HudsonGit\Git\Administration\DeleteController;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerAdder;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerDao;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerDeleter;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;
use Tuleap\HudsonGit\Git\Administration\URLBuilder;
use Tuleap\HudsonGit\Git\Administration\XML\XMLExporter;
use Tuleap\HudsonGit\Git\Administration\XML\XMLImporter;
use Tuleap\HudsonGit\GitWebhooksSettingsEnhancer;
use Tuleap\HudsonGit\Hook;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookPayload;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookPrefixToken;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookTokenGeneratorDBStore;
use Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookTokenVerifierController;
use Tuleap\HudsonGit\HudsonGitPluginDefaultController;
use Tuleap\HudsonGit\Job\JobDao;
use Tuleap\HudsonGit\Job\ProjectJobDao;
use Tuleap\HudsonGit\Log\LogCreator;
use Tuleap\HudsonGit\Log\LogFactory;
use Tuleap\HudsonGit\Plugin\PluginInfo;
use Tuleap\HudsonGit\REST\ResourcesInjector;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
class hudson_gitPlugin extends Plugin
{
    public const string DISPLAY_HUDSON_ADDITION_INFO = 'display_hudson_addition_info';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-hudson_git', __DIR__ . '/../site-content');

        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook('cssfile', 'cssFile');

        if (defined('GIT_BASE_URL')) {
            $this->addHook(Hooks::ADDITIONAL_WEBHOOKS);
            $this->addHook(PostReceiveExecuteEvent::NAME);
            $this->addHook(self::DISPLAY_HUDSON_ADDITION_INFO);
            $this->addHook(GitAdminGetExternalPanePresenters::NAME);
            $this->addHook(CollectGitRoutesEvent::NAME);
            $this->addHook(XMLImportExternalContentEvent::NAME);
            $this->addHook(XMLExportExternalContentEvent::NAME);
            $this->addHook(Event::REST_RESOURCES);
            $this->addHook(GetExternalGitHomepagePluginsEvent::NAME);
        }
    }

    public function cssFile($params)
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        if (! $git_plugin instanceof GitPlugin) {
            throw new RuntimeException('Cannot instantiate Git plugin');
        }

        if (
            strpos($_SERVER['REQUEST_URI'], '/administration/jenkins') !== false
            && strpos($_SERVER['REQUEST_URI'], $git_plugin->getPluginPath()) === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getIncludeAssets()->getFileURL('style.css') . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . $git_plugin->getLegacyAssets()->getFileURL('default.css') . '" />';
        }
    }

    /**
     * @access protected for test purpose
     */
    protected function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/hudson_git'
        );
    }

    public function display_hudson_addition_info($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['installed'] = defined('GIT_BASE_URL');
    }

    /**
     * @see Plugin::getDependencies()
     */
    #[\Override]
    public function getDependencies()
    {
        return ['git', 'hudson'];
    }

    /**
     * @return PluginInfo
     */
    #[\Override]
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /** @see Tuleap\Git\GitViews\RepoManagement\Pane\Hooks::ADDITIONAL_WEBHOOKS */
    public function plugin_git_settings_additional_webhooks(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed($params['repository']->getProjectId())) {
            $xzibit = new GitWebhooksSettingsEnhancer(
                new Hook\HookDao(),
                new LogFactory(
                    new JobDao(),
                    new ProjectJobDao(),
                    new GitRepositoryFactory(
                        new GitDao(),
                        ProjectManager::instance()
                    )
                ),
                $this->getCSRF(),
                self::getJenkinsServerFactory(),
                Codendi_HTMLPurifier::instance(),
                new \Tuleap\Sanitizer\URISanitizer(new Valid_HTTPURI()),
            );
            $xzibit->pimp($params);
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeGetPostLegacyController'));

            $r->post('/jenkins_server', $this->getRouteHandler('getPostGitAdministrationJenkinsServer'));
            $r->post('/jenkins_server/delete', $this->getRouteHandler('getDeleteGitAdministrationJenkinsServer'));
            $r->post('/test_jenkins_server', $this->getRouteHandler('getAjaxAdministrationTestJenkinsServer'));
            $r->post('/jenkins_tuleap_hook_trigger_check', $this->getRouteHandler('routePostVerifyHookTrigger'));
        });
    }

    public static function getAjaxAdministrationTestJenkinsServer(): AjaxController
    {
        return new AjaxController(
            HttpClientFactory::createClient(new CookiePlugin(new CookieJar())),
            HTTPFactoryBuilder::requestFactory(),
            new WrapperLogger(self::getHudsonGitLogger(), 'hudson_git')
        );
    }

    public static function getDeleteGitAdministrationJenkinsServer(): DeleteController
    {
        return new DeleteController(
            self::getGitPermissionsManager(),
            self::getJenkinsServerFactory(),
            new JenkinsServerDeleter(
                new JenkinsServerDao(),
                new ProjectJobDao(),
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                )
            ),
            new CSRFSynchronizerToken(URLBuilder::buildDeleteUrl())
        );
    }

    public static function getPostGitAdministrationJenkinsServer(): AddController
    {
        return new AddController(
            ProjectManager::instance(),
            self::getGitPermissionsManager(),
            new JenkinsServerAdder(
                new JenkinsServerDao(),
                new Valid_HTTPURI(),
                (new \Tuleap\Cryptography\KeyFactoryFromFileSystem())->getLegacy2025EncryptionKey()
            ),
            new CSRFSynchronizerToken(URLBuilder::buildAddUrl())
        );
    }

    public function collectGitRoutesEvent(CollectGitRoutesEvent $event)
    {
        $event->getRouteCollector()->get(
            '/{project_name}/administration/jenkins',
            $this->getRouteHandler('routeGetGitJenkinsAdministration')
        );
    }

    public function routeGetGitJenkinsAdministration(): AdministrationController
    {
        $git_plugin = PluginManager::instance()->getPluginByName('git');
        assert($git_plugin instanceof GitPlugin);

        return new AdministrationController(
            ProjectManager::instance(),
            self::getGitPermissionsManager(),
            self::getJenkinsServerFactory(),
            new LogFactory(
                new JobDao(),
                new ProjectJobDao(),
                new GitRepositoryFactory(
                    new GitDao(),
                    ProjectManager::instance()
                )
            ),
            $git_plugin->getHeaderRenderer(),
            TemplateRendererFactory::build()->getRenderer(HUDSON_GIT_BASE_DIR . '/templates/git-administration'),
            $this->getIncludeAssets(),
            EventManager::instance()
        );
    }

    public function routePostVerifyHookTrigger(): JenkinsTuleapPluginHookTokenVerifierController
    {
        return new JenkinsTuleapPluginHookTokenVerifierController(
            HTTPFactoryBuilder::responseFactory(),
            new Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookTokenVerifierDBStore(
                new Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookTokenDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                new PrefixedSplitTokenSerializer(new JenkinsTuleapPluginHookPrefixToken()),
                new SplitTokenVerificationStringHasher(),
                self::getHudsonGitLogger(),
            ),
            new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter(),
            new DisableCacheMiddleware()
        );
    }

    private static function getGitPermissionsManager(): GitPermissionsManager
    {
        $git_system_event_manager = new Git_SystemEventManager(
            SystemEventManager::instance(),
        );

        $fine_grained_dao       = new FineGrainedDao();
        $fine_grained_retriever = new FineGrainedRetriever($fine_grained_dao);

        return new GitPermissionsManager(
            new Git_PermissionsDao(),
            $git_system_event_manager,
            $fine_grained_dao,
            $fine_grained_retriever
        );
    }

    public function routeGetPostLegacyController()
    {
        $request    = HTTPRequest::instance();
        $project_id = (int) $request->getProject()->getID();

        return new HudsonGitPluginDefaultController(
            $this->getHookController($request),
            $this->isAllowed($project_id)
        );
    }

    public function postReceiveExecuteEvent(PostReceiveExecuteEvent $event): void
    {
        $repository = $event->getRepository();
        if ($this->isAllowed($repository->getProjectId())) {
            $http_client     = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
            $request_factory = HTTPFactoryBuilder::requestFactory();
            $stream_factory  = HTTPFactoryBuilder::streamFactory();
            $encryption_key  = (new \Tuleap\Cryptography\KeyFactoryFromFileSystem())->getLegacy2025EncryptionKey();
            $controller      = new Hook\HookTriggerController(
                new Hook\HookDao(),
                new Hook\JenkinsClient(
                    $http_client,
                    $request_factory,
                    new JenkinsCSRFCrumbRetriever($http_client, $request_factory),
                    new JenkinsTuleapPluginHookPayload(
                        $repository,
                        $event->getRefname(),
                        new JenkinsTuleapPluginHookTokenGeneratorDBStore(
                            new Hook\JenkinsTuleapBranchSourcePluginHook\JenkinsTuleapPluginHookTokenDAO(),
                            new SplitTokenVerificationStringHasher(),
                            new PrefixedSplitTokenSerializer(new JenkinsTuleapPluginHookPrefixToken()),
                            new DateInterval('PT30S'),
                        ),
                        fn (): DateTimeImmutable => new DateTimeImmutable(),
                    ),
                    $stream_factory,
                    $encryption_key,
                ),
                $this->getLogger(),
                new LogCreator(
                    new JobDao(),
                    new ProjectJobDao(),
                    new DBTransactionExecutorWithConnection(
                        DBFactory::getMainTuleapDBConnection()
                    )
                ),
                self::getJenkinsServerFactory()
            );

            $controller->trigger(
                $repository,
                $event->getNewrev(),
                new DateTimeImmutable()
            );
        }
    }

    /**
     * @return Hook\HookController
     */
    private function getHookController(Codendi_Request $request)
    {
        return new Hook\HookController(
            $request,
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            new Hook\HookDao(),
            $this->getCSRF(),
            new Valid_HTTPURI(),
            (new \Tuleap\Cryptography\KeyFactoryFromFileSystem())->getLegacy2025EncryptionKey()
        );
    }

    private function getCSRF()
    {
        return new CSRFSynchronizerToken('hudson-git-hook-management');
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return new WrapperLogger(self::getHudsonGitLogger(), 'hudson_git');
    }

    private static function getHudsonGitLogger(): \Psr\Log\LoggerInterface
    {
        return \BackendLogger::getDefaultLogger('hudson_git_syslog');
    }

    public function gitAdminGetExternalPanePresenters(GitAdminGetExternalPanePresenters $event): void
    {
        if ($event->getCurrentTabName() === AdministrationPaneBuilder::PANE_NAME) {
            $event->addExternalPanePresenter(AdministrationPaneBuilder::buildActivePane($event->getProject()));
            return;
        }

        $event->addExternalPanePresenter(AdministrationPaneBuilder::buildPane($event->getProject()));
    }

    private static function getJenkinsServerFactory(): JenkinsServerFactory
    {
        return new JenkinsServerFactory(
            new JenkinsServerDao(),
            ProjectManager::instance()
        );
    }

    public function xmlImportExternalContentEvent(XMLImportExternalContentEvent $event): void
    {
        $project = $event->getProject();
        if (! $this->isAllowed((int) $project->getID())) {
            return;
        }

        $xml_importer = new XMLImporter(
            new JenkinsServerAdder(
                new JenkinsServerDao(),
                new Valid_HTTPURI(),
                (new \Tuleap\Cryptography\KeyFactoryFromFileSystem())->getLegacy2025EncryptionKey()
            ),
            $event->getLogger()
        );

        $xml_importer->import(
            $project,
            $event->getXMLGit()
        );
    }

    public function xmlExportExternalContentEvent(XMLExportExternalContentEvent $event): void
    {
        $project = $event->getProject();
        if (! $this->isAllowed((int) $project->getID())) {
            return;
        }

        $xml_importer = new XMLExporter(
            self::getJenkinsServerFactory(),
            $event->getLogger(),
            (new \Tuleap\Cryptography\KeyFactoryFromFileSystem())->getLegacy2025EncryptionKey()
        );

        $xml_importer->export(
            $project,
            $event->getXMLGit()
        );
    }

    /** @see Event::REST_RESOURCES */
    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function getExternalGitHomepagePluginsEvent(GetExternalGitHomepagePluginsEvent $event): void
    {
        $factory = self::getJenkinsServerFactory();
        $servers = $factory->getJenkinsServerOfProject($event->getProject());

        $plugin_infos = new ExternalGitHomepagePluginInfos('hudson_git', $servers);
        $event->addExternalPluginInfos($plugin_infos);
    }
}
