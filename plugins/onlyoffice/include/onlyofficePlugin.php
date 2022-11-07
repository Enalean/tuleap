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
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\Download\DocmanFileDownloadController;
use Tuleap\Docman\Download\DocmanFileDownloadResponseGenerator;
use Tuleap\Docman\FilenamePattern\FilenamePatternRetriever;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\REST\v1\OpenItemHref;
use Tuleap\Docman\Settings\SettingsDAO;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\Document\Tree\ShouldDisplaySourceColumnForFileVersions;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\OnlyOffice\Administration\OnlyOfficeAdminSettingsController;
use Tuleap\OnlyOffice\Administration\OnlyOfficeAdminSettingsPresenter;
use Tuleap\OnlyOffice\Administration\OnlyOfficeDocumentServerSettings;
use Tuleap\OnlyOffice\Download\DownloadDocumentWithTokenMiddleware;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenDAO;
use Tuleap\OnlyOffice\Download\OnlyOfficeDownloadDocumentTokenVerifier;
use Tuleap\OnlyOffice\Save\CallbackURLSaveTokenIdentifierExtractor;
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
final class onlyofficePlugin extends Plugin implements PluginWithConfigKeys
{
    private const LOG_IDENTIFIER              = 'onlyoffice_syslog';
    private const DELAY_SAVE_TOKEN_EXPIRATION = 'PT15M';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
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

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(OpenItemHref::NAME);
        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook(ShouldDisplaySourceColumnForFileVersions::NAME);
        return parent::getHooksAndCallbacks();
    }

    public function shouldDisplaySourceColumnForFileVersions(ShouldDisplaySourceColumnForFileVersions $event): void
    {
        $event->enableDisplayOfSourceColumn();
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(OnlyOfficeDocumentServerSettings::class);
        $event->addConfigClass(ProvideDocmanFileLastVersion::class);
    }

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
        $route_collector->get(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL, $this->getRouteHandler('routeGetAdminSettings'));
        $route_collector->post(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL, $this->getRouteHandler('routePostAdminSettings'));
    }

    public function routePostDocumentSave(): OnlyOfficeSaveController
    {
        $logger           = self::getLogger();
        $versions_factory = new Docman_VersionFactory();
        $event_manager    = EventManager::instance();
        $docman_plugin    = PluginManager::instance()->getPluginByName('docman');
        assert($docman_plugin instanceof DocmanPlugin);
        $docman_root_path = $docman_plugin->getPluginInfo()->getPropertyValueForName('docman_root');

        return new OnlyOfficeSaveController(
            new \Tuleap\OnlyOffice\Save\OnlyOfficeCallbackResponseJWTParser(
                new \Lcobucci\JWT\Token\Parser(new \Lcobucci\JWT\Encoding\JoseEncoder()),
                new \Lcobucci\JWT\Validation\Validator(),
                new \Lcobucci\JWT\Validation\Constraint\LooseValidAt(
                    \Lcobucci\Clock\SystemClock::fromSystemTimezone(),
                    new DateInterval('PT2M'),
                ),
                new \Lcobucci\JWT\Signer\Hmac\Sha256(),
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

    public function openItemHref(OpenItemHref $open_item_href): void
    {
        $transformer = new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
            new \Tuleap\OnlyOffice\Administration\OnlyOfficeAvailabilityChecker(PluginManager::instance(), $this, self::getLogger()),
            ProjectManager::instance(),
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

        return new OpenInOnlyOfficeController(
            UserManager::instance(),
            new \Tuleap\OnlyOffice\Open\OnlyOfficeDocumentProvider(
                self::getDocmanFileLastVersionProvider(),
                new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
                    new \Tuleap\OnlyOffice\Administration\OnlyOfficeAvailabilityChecker(PluginManager::instance(), $this, $logger),
                    ProjectManager::instance(),
                ),
            ),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            $logger,
            new \Tuleap\Layout\JavascriptViteAsset(self::getAssets(), 'scripts/open-in-onlyoffice.ts'),
            Prometheus::instance(),
            \Tuleap\ServerHostname::HTTPSUrl(),
        );
    }

    public function routeGetEditorOnlyOffice(): OnlyOfficeEditorController
    {
        $logger = self::getLogger();

        return new OnlyOfficeEditorController(
            $logger,
            new \Tuleap\OnlyOffice\Open\Editor\OnlyOfficeGlobalEditorJWTokenProvider(
                new \Tuleap\OnlyOffice\Open\Editor\OnlyOfficeDocumentConfigProvider(
                    new \Tuleap\OnlyOffice\Open\OnlyOfficeDocumentProvider(
                        self::getDocmanFileLastVersionProvider(),
                        new DocmanFileLastVersionToOnlyOfficeDocumentTransformer(
                            new \Tuleap\OnlyOffice\Administration\OnlyOfficeAvailabilityChecker(PluginManager::instance(), $this, $logger),
                            ProjectManager::instance(),
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
            ),
            UserManager::instance(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            self::getAssets(),
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

    private static function getAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/onlyoffice'
        );
    }

    public function routeGetAdminSettings(): OnlyOfficeAdminSettingsController
    {
        return new OnlyOfficeAdminSettingsController(
            new AdminPageRenderer(),
            UserManager::instance(),
            new OnlyOfficeAdminSettingsPresenter(
                ForgeConfig::get(OnlyOfficeDocumentServerSettings::URL, ''),
                ForgeConfig::exists(OnlyOfficeDocumentServerSettings::SECRET),
                CSRFSynchronizerTokenPresenter::fromToken(self::buildCSRFTokenAdmin()),
            )
        );
    }

    public function routePostAdminSettings(): \Tuleap\OnlyOffice\Administration\OnlyOfficeSaveAdminSettingsController
    {
        return new \Tuleap\OnlyOffice\Administration\OnlyOfficeSaveAdminSettingsController(
            self::buildCSRFTokenAdmin(),
            new \Tuleap\Config\ConfigSet(EventManager::instance(), new \Tuleap\Config\ConfigDao()),
            \Tuleap\OnlyOffice\Administration\OnlyOfficeServerUrlValidator::buildSelf(),
            \Tuleap\OnlyOffice\Administration\OnlyOfficeSecretKeyValidator::buildSelf(),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())),
            new SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    private static function buildCSRFTokenAdmin(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL);
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-onlyoffice', 'ONLYOFFICE'),
                OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL
            )
        );
    }

    private static function getLogger(): \Psr\Log\LoggerInterface
    {
        return \BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
    }
}
