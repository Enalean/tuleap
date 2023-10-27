<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\Download\DocmanFileDownloadController;
use Tuleap\Docman\Download\DocmanFileDownloadResponseGenerator;
use Tuleap\Docman\FilenamePattern\FilenamePatternRetriever;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\Item\OpenItemHref;
use Tuleap\Docman\Settings\SettingsDAO;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\Document\Tree\Create\NewItemAlternative;
use Tuleap\Document\Tree\Create\NewItemAlternativeCollector;
use Tuleap\Document\Tree\Create\NewItemAlternativeSection;
use Tuleap\Document\Tree\ShouldDisplaySourceColumnForFileVersions;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\OnlyOffice\Administration\OnlyOfficeAdminSettingsController;
use Tuleap\OnlyOffice\Administration\OnlyOfficeAvailabilityChecker;
use Tuleap\OnlyOffice\Administration\OnlyOfficeCreateAdminSettingsController;
use Tuleap\OnlyOffice\Administration\OnlyOfficeDeleteAdminSettingsController;
use Tuleap\OnlyOffice\Administration\OnlyOfficeRestrictAdminSettingsController;
use Tuleap\OnlyOffice\Administration\OnlyOfficeUpdateAdminSettingsController;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerDao;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerKeyEncryption;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerProjectRestrictionDAO;
use Tuleap\OnlyOffice\Download\DownloadDocumentWithTokenMiddleware;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenDAO;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenVerifier;
use Tuleap\OnlyOffice\Save\CallbackURLSaveTokenIdentifierExtractor;
use Tuleap\OnlyOffice\Save\DocumentServerForSaveDocumentTokenRetriever;
use Tuleap\OnlyOffice\Save\OnlyOfficeRefreshCallbackURLTokenController;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveDocumentTokenDAO;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveDocumentTokenGeneratorDBStore;
use Tuleap\OnlyOffice\Download\PrefixOnlyOfficeDocumentDownload;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveDocumentTokenRefresherDBStore;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveDocumentTokenVerifier;
use Tuleap\OnlyOffice\Save\PrefixOnlyOfficeDocumentSave;
use Tuleap\OnlyOffice\Open\DocmanFileLastVersionProvider;
use Tuleap\OnlyOffice\Open\DocmanFileLastVersionToOnlyOfficeDocumentTransformer;
use Tuleap\OnlyOffice\Open\OnlyOfficeEditorController;
use Tuleap\OnlyOffice\Open\OpenInOnlyOfficeController;
use Tuleap\OnlyOffice\Open\ProvideDocmanFileLastVersion;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveCallbackURLGenerator;
use Tuleap\OnlyOffice\Save\OnlyOfficeSaveController;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class onlyofficePlugin extends Plugin
{
    private const LOG_IDENTIFIER              = 'onlyoffice_syslog';
    private const DELAY_SAVE_TOKEN_EXPIRATION = 'PT15M';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-onlyoffice', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-onlyoffice', 'ONLYOFFICE integration'),
                    dgettext('tuleap-onlyoffice', 'Allow to open documents into ONLYOFFICE')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['docman'];
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function newItemAlternativeCollector(NewItemAlternativeCollector $collector): void
    {
        $only_office_availability_checker = new OnlyOfficeAvailabilityChecker(
            self::getLogger(),
            new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory())),
        );
        if (! $only_office_availability_checker->isOnlyOfficeIntegrationAvailableForProject($collector->getProject())) {
            return;
        }

        $filename_pattern_retriever = new FilenamePatternRetriever(new SettingsDAO());
        if ($filename_pattern_retriever->getPattern((int) $collector->getProject()->getID())->isEnforced()) {
            return;
        }

        $collector->addSection(
            new NewItemAlternativeSection(
                dgettext('tuleap-onlyoffice', 'Online office files'),
                [
                    new NewItemAlternative(
                        'application/word',
                        dgettext('tuleap-onlyoffice', 'Document')
                    ),
                    new NewItemAlternative(
                        'application/excel',
                        dgettext('tuleap-onlyoffice', 'Spreadsheet')
                    ),
                    new NewItemAlternative(
                        'application/powerpoint',
                        dgettext('tuleap-onlyoffice', 'Presentation')
                    ),
                ]
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function shouldDisplaySourceColumnForFileVersions(ShouldDisplaySourceColumnForFileVersions $event): void
    {
        $event->enableDisplayOfSourceColumn();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $route_collector = $routes->getRouteCollector();
        $route_collector->addGroup(
            '/onlyoffice',
            function (FastRoute\RouteCollector $r): void {
                $r->get('/document_download', $this->getRouteHandler('routeGetDocumentDownload'));
                $r->post('/document_save_refresh_token', $this->getRouteHandler('routePostRefreshCallbackURLToken'));
                $r->get('/open/{id:\d+}', $this->getRouteHandler('routeGetOpenOnlyOffice'));
                $r->get('/editor/{id:\d+}', $this->getRouteHandler('routeGetEditorOnlyOffice'));
            }
        );
        $route_collector->post(OnlyOfficeSaveCallbackURLGenerator::CALLBACK_SAVE_URL, $this->getRouteHandler('routePostDocumentSave'));
        $route_collector->get(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL . '[/{vue-routing:.*}]', $this->getRouteHandler('routeGetAdminSettings'));
        $route_collector->post(OnlyOfficeCreateAdminSettingsController::URL, $this->getRouteHandler('routeCreateAdminSettings'));
        $route_collector->post(OnlyOfficeUpdateAdminSettingsController::URL . '/{id:\d+}', $this->getRouteHandler('routeUpdateAdminSettings'));
        $route_collector->post(OnlyOfficeDeleteAdminSettingsController::URL . '/{id:\d+}', $this->getRouteHandler('routeDeleteAdminSettings'));
        $route_collector->post(OnlyOfficeRestrictAdminSettingsController::URL . '/{id:\d+}', $this->getRouteHandler('routeRestrictAdminSettings'));
    }

    public function routePostDocumentSave(): OnlyOfficeSaveController
    {
        $logger           = self::getLogger();
        $versions_factory = new Docman_VersionFactory();
        $event_manager    = EventManager::instance();
        $docman_plugin    = PluginManager::instance()->getPluginByName('docman');
        assert($docman_plugin instanceof DocmanPlugin);
        $docman_root_path = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');

        $encryption = new DocumentServerKeyEncryption(new KeyFactory());

        return new OnlyOfficeSaveController(
            new \Tuleap\OnlyOffice\Save\OnlyOfficeCallbackResponseJWTParser(
                new \Lcobucci\JWT\Token\Parser(new \Lcobucci\JWT\Encoding\JoseEncoder()),
                new \Lcobucci\JWT\Validation\Validator(),
                new \Lcobucci\JWT\Validation\Constraint\LooseValidAt(
                    \Lcobucci\Clock\SystemClock::fromSystemTimezone(),
                    new DateInterval('PT2M'),
                ),
                new \Lcobucci\JWT\Signer\Hmac\Sha256(),
                new DocumentServerForSaveDocumentTokenRetriever(new DocumentServerDao($encryption)),
                $encryption,
            ),
            new \Tuleap\OnlyOffice\Save\OnlyOfficeCallbackDocumentSaver(
                UserManager::instance(),
                new Docman_ItemFactory(),
                $versions_factory,
                new Docman_LockFactory(new Docman_LockDao(), new Docman_Log()),
                new Docman_FileStorage($docman_root_path),
                new CoAuthorDao(),
                new PostUpdateFileHandler(
                    $versions_factory,
                    new \Tuleap\Docman\REST\v1\DocmanItemsEventAdder($event_manager),
                    ProjectManager::instance(),
                    $event_manager,
                ),
                \Tuleap\Http\HttpClientFactory::createAsyncClient(),
                HTTPFactoryBuilder::requestFactory(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new JSONResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory(),
            ),
            $logger,
            new SapiEmitter(),
            new \Tuleap\OnlyOffice\Save\SaveDocumentWithTokenMiddleware(
                new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentSave()),
                new OnlyOfficeSaveDocumentTokenVerifier(
                    new OnlyOfficeSaveDocumentTokenDAO(),
                    new SplitTokenVerificationStringHasher(),
                    $logger,
                )
            ),
            new SessionWriteCloseMiddleware(),
        );
    }

    public function routePostRefreshCallbackURLToken(): OnlyOfficeRefreshCallbackURLTokenController
    {
        $logger         = self::getLogger();
        $save_token_dao = new OnlyOfficeSaveDocumentTokenDAO();

        return new OnlyOfficeRefreshCallbackURLTokenController(
            new OnlyOfficeSaveDocumentTokenRefresherDBStore(
                new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentSave()),
                new OnlyOfficeSaveDocumentTokenVerifier(
                    $save_token_dao,
                    new SplitTokenVerificationStringHasher(),
                    $logger,
                ),
                new DateInterval(self::DELAY_SAVE_TOKEN_EXPIRATION),
                $save_token_dao,
            ),
            new CallbackURLSaveTokenIdentifierExtractor(),
            HTTPFactoryBuilder::responseFactory(),
            $logger,
            new SapiEmitter(),
            new SessionWriteCloseMiddleware(),
        );
    }

    public function routeGetDocumentDownload(): DocmanFileDownloadController
    {
        $logger           = self::getLogger();
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $token_middleware = new DownloadDocumentWithTokenMiddleware(
            new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentDownload()),
            new OnlyOfficeDownloadDocumentTokenVerifier(
                new OnlyOfficeDownloadDocumentTokenDAO(),
                new DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
                new SplitTokenVerificationStringHasher(),
                $logger,
            ),
            UserManager::instance()
        );
        return new DocmanFileDownloadController(
            new SapiStreamEmitter(),
            new Docman_ItemFactory(),
            new DocmanFileDownloadResponseGenerator(
                new Docman_VersionFactory(),
                new BinaryFileResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory())
            ),
            $token_middleware,
            $logger,
            new SessionWriteCloseMiddleware(),
            $token_middleware,
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function openItemHref(OpenItemHref $open_item_href): void
    {
        $servers_retriever = new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory()));
        $transformer       = new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
            new OnlyOfficeAvailabilityChecker(self::getLogger(), $servers_retriever),
            ProjectManager::instance(),
            $servers_retriever,
        );

        $result = $transformer->transformToOnlyOfficeDocument(
            new \Tuleap\OnlyOffice\Open\DocmanFileLastVersion($open_item_href->getItem(), $open_item_href->getVersion(), false)
        );
        if (\Tuleap\NeverThrow\Result::isOk($result)) {
            $open_item_href->setHref(
                '/onlyoffice/open/' . urlencode((string) $open_item_href->getItem()->getId())
            );
        }
    }

    public function routeGetOpenOnlyOffice(): OpenInOnlyOfficeController
    {
        $logger = self::getLogger();

        $servers_retriever = new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory()));

        return new OpenInOnlyOfficeController(
            UserManager::instance(),
            new \Tuleap\OnlyOffice\Open\OnlyOfficeDocumentProvider(
                self::getDocmanFileLastVersionProvider(),
                new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
                    new OnlyOfficeAvailabilityChecker($logger, $servers_retriever),
                    ProjectManager::instance(),
                    $servers_retriever,
                ),
            ),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            $logger,
            new \Tuleap\Layout\JavascriptViteAsset(self::getOpenAssets(), 'src/open-in-onlyoffice.ts'),
            Prometheus::instance(),
            \Tuleap\ServerHostname::HTTPSUrl(),
        );
    }

    public function routeGetEditorOnlyOffice(): OnlyOfficeEditorController
    {
        $logger = self::getLogger();

        $encryption = new DocumentServerKeyEncryption(new KeyFactory());

        $servers_retriever = new DocumentServerDao($encryption);

        return new OnlyOfficeEditorController(
            $logger,
            new \Tuleap\OnlyOffice\Open\Editor\OnlyOfficeGlobalEditorJWTokenProvider(
                new \Tuleap\OnlyOffice\Open\Editor\OnlyOfficeDocumentConfigProvider(
                    new \Tuleap\OnlyOffice\Open\OnlyOfficeDocumentProvider(
                        self::getDocmanFileLastVersionProvider(),
                        new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
                            new OnlyOfficeAvailabilityChecker($logger, $servers_retriever),
                            ProjectManager::instance(),
                            $servers_retriever,
                        ),
                    ),
                    new \Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenGeneratorDBStore(
                        new OnlyOfficeDownloadDocumentTokenDAO(),
                        new SplitTokenVerificationStringHasher(),
                        new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentDownload()),
                        new DateInterval('PT30S')
                    )
                ),
                new \Tuleap\OnlyOffice\Save\OnlyOfficeSaveCallbackURLGenerator(
                    new OnlyOfficeSaveDocumentTokenGeneratorDBStore(
                        new OnlyOfficeSaveDocumentTokenDAO(),
                        new SplitTokenVerificationStringHasher(),
                        new PrefixedSplitTokenSerializer(new PrefixOnlyOfficeDocumentSave()),
                        new DateInterval(self::DELAY_SAVE_TOKEN_EXPIRATION),
                    ),
                ),
                new \Lcobucci\JWT\JwtFacade(),
                new \Lcobucci\JWT\Signer\Hmac\Sha256(),
                $encryption,
            ),
            UserManager::instance(),
            $servers_retriever,
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            self::getOpenAssets(),
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter()
        );
    }

    private static function getDocmanFileLastVersionProvider(): ProvideDocmanFileLastVersion
    {
        $version_factory = new Docman_VersionFactory();
        return new DocmanFileLastVersionProvider(
            new \Docman_ItemFactory(),
            $version_factory,
            new FilenamePatternRetriever(new SettingsDAO()),
            new ApprovalTableRetriever(new Docman_ApprovalTableFactoriesFactory(), $version_factory),
            new Docman_LockFactory(new Docman_LockDao(), new Docman_Log()),
        );
    }

    private static function getOpenAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../scripts/open-in-onlyoffice/frontend-assets',
            '/assets/onlyoffice/open-in-onlyoffice'
        );
    }

    public function routeGetAdminSettings(): OnlyOfficeAdminSettingsController
    {
        $builder = new \Tuleap\OnlyOffice\Administration\OnlyOfficeAdminSettingsPresenterBuilder(
            new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory()))
        );

        return new OnlyOfficeAdminSettingsController(
            new AdminPageRenderer(),
            UserManager::instance(),
            $builder->getPresenter(self::buildCSRFTokenAdmin()),
            new IncludeViteAssets(
                __DIR__ . '/../scripts/siteadmin/frontend-assets/',
                '/assets/onlyoffice/siteadmin'
            ),
        );
    }

    public function routeCreateAdminSettings(): \Tuleap\Request\DispatchableWithRequest
    {
        return new OnlyOfficeCreateAdminSettingsController(
            self::buildCSRFTokenAdmin(),
            new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory())),
            \Tuleap\OnlyOffice\Administration\OnlyOfficeServerUrlValidator::buildSelf(),
            \Tuleap\OnlyOffice\Administration\OnlyOfficeSecretKeyValidator::buildSelf(),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    public function routeUpdateAdminSettings(): \Tuleap\Request\DispatchableWithRequest
    {
        return new OnlyOfficeUpdateAdminSettingsController(
            self::buildCSRFTokenAdmin(),
            new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory())),
            \Tuleap\OnlyOffice\Administration\OnlyOfficeServerUrlValidator::buildSelf(),
            \Tuleap\OnlyOffice\Administration\OnlyOfficeSecretKeyValidator::buildSelf(),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    public function routeDeleteAdminSettings(): \Tuleap\Request\DispatchableWithRequest
    {
        return new OnlyOfficeDeleteAdminSettingsController(
            self::buildCSRFTokenAdmin(),
            new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory())),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    public function routeRestrictAdminSettings(): \Tuleap\Request\DispatchableWithRequest
    {
        $document_server_dao = new DocumentServerDao(new DocumentServerKeyEncryption(new KeyFactory()));

        return new OnlyOfficeRestrictAdminSettingsController(
            self::buildCSRFTokenAdmin(),
            $document_server_dao,
            $document_server_dao,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    private static function buildCSRFTokenAdmin(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-onlyoffice', 'ONLYOFFICE'),
                OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            (new DocumentServerProjectRestrictionDAO())->removeProjectFromRestriction($event->project);
        }
    }

    private static function getLogger(): \Psr\Log\LoggerInterface
    {
        return \BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
    }
}
