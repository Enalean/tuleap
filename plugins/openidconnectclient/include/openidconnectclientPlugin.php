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

use FastRoute\RouteCollector;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Validator;
use Psr\Log\LoggerInterface;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\Client\Authentication\BasicAuth;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OpenIDConnectClient\AccountLinker;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountDao;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountManager;
use Tuleap\OpenIDConnectClient\Administration;
use Tuleap\OpenIDConnectClient\Administration\ColorPresenterFactory;
use Tuleap\OpenIDConnectClient\Administration\IconPresenterFactory;
use Tuleap\OpenIDConnectClient\AdminRouter;
use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\AzureADUserLinkController;
use Tuleap\OpenIDConnectClient\Authentication\AzureProviderIssuerClaimValidator;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\GenericProviderIssuerClaimValidator;
use Tuleap\OpenIDConnectClient\Authentication\IDTokenVerifier;
use Tuleap\OpenIDConnectClient\Authentication\IssuerClaimValidator;
use Tuleap\OpenIDConnectClient\Authentication\JWKSKeyFetcher;
use Tuleap\OpenIDConnectClient\Authentication\StateFactory;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Authentication\StateStorage;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestSender;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestSender;
use Tuleap\OpenIDConnectClient\Login;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\Login\IncoherentDataUniqueProviderException;
use Tuleap\OpenIDConnectClient\LoginController;
use Tuleap\OpenIDConnectClient\OpenIDConnectClientPluginInfo;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderDao;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderManager;
use Tuleap\OpenIDConnectClient\Provider\EnableUniqueAuthenticationEndpointVerifier;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderDao;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderDao;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Router;
use Tuleap\OpenIDConnectClient\UserAccount\AccountTabsBuilder;
use Tuleap\OpenIDConnectClient\UserAccount\OIDCProvidersController;
use Tuleap\OpenIDConnectClient\UserAccount\UnlinkController;
use Tuleap\OpenIDConnectClient\UserMapping\CanRemoveUserMappingChecker;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\Register\AfterUserRegistrationEvent;
use Tuleap\User\Account\Register\BeforeUserRegistrationEvent;
use Tuleap\User\Account\RegistrationGuardEvent;
use Tuleap\User\AdditionalConnector;
use Tuleap\User\AdditionalConnectorsCollector;
use Tuleap\User\UserAuthenticationSucceeded;
use Tuleap\User\UserNameNormalizer;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @psalm-import-type AcceptableIssuerClaimValidator from IDTokenVerifier
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class openidconnectclientPlugin extends Plugin implements PluginWithConfigKeys
{
    public const SESSION_LINK_ID_KEY = 'tuleap_oidc_link_id';

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-openidconnectclient', __DIR__ . '/../site-content');

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook(BeforeUserRegistrationEvent::NAME);
        $this->addHook(AfterUserRegistrationEvent::NAME);
        $this->addHook('anonymous_access_to_script_allowed');
        $this->addHook('cssfile');
        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::GET_LOGIN_URL);
        $this->addHook(RegistrationGuardEvent::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);
        $this->addHook(UserAuthenticationSucceeded::NAME);
    }

    /**
     * @return OpenIDConnectClientPluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof OpenIDConnectClientPluginInfo) {
            $this->pluginInfo = new OpenIDConnectClientPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function anonymous_access_to_script_allowed($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($params['script_name'], $this->getPluginPath()) === 0) {
            $params['anonymous_allowed'] = true;
        }
    }

    public function cssfile()
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/openidconnectclient') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('fp-style.css') . '" />';
        }
    }

    public function burning_parrot_get_stylesheets($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($_SERVER['REQUEST_URI'] === '/') {
            $params['stylesheets'][] = $this->getAssets()->getFileURL('bp-style.css');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/openidconnectclient'
        );
    }

    /**
     * @return ProviderManager
     */
    private function getProviderManager()
    {
        return new ProviderManager(
            new ProviderDao(),
            new GenericProviderManager(new GenericProviderDao()),
            new AzureADProviderManager(new AzureADProviderDao())
        );
    }

    /**
     * @return bool
     */
    private function isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint(ProviderManager $provider_manager)
    {
        return $provider_manager->isAProviderConfiguredAsUniqueAuthenticationEndpoint();
    }

    public function get_login_url($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $provider_manager = $this->getProviderManager();
        if (! $this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint($provider_manager)) {
            return;
        }
        if (ForgeConfig::get(ForgeAccess::CONFIG) !== ForgeAccess::ANONYMOUS && ! ForgeConfig::get(ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE)) {
            $params['login_url'] = OPENIDCONNECTCLIENT_BASE_URL . '/login.php?' . http_build_query(
                ['return_to' => $params['return_to']]
            );
            return;
        }

        $url_generator = new Login\LoginUniqueAuthenticationUrlGenerator(
            $provider_manager,
            new Login\LoginURLGenerator($this->getPluginPath())
        );

        try {
            $params['login_url'] = $url_generator->getURL(urldecode($params['return_to']));
        } catch (IncoherentDataUniqueProviderException $exception) {
        }
    }

    public function registrationGuardEvent(RegistrationGuardEvent $event): void
    {
        $provider_manager = $this->getProviderManager();
        if ($this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint($provider_manager)) {
            $event->disableRegistration();
        }
    }

    /**
     * @param AcceptableIssuerClaimValidator $issuer_claim_validator
     */
    private function getFlow(ProviderManager $provider_manager, IssuerClaimValidator $issuer_claim_validator): Flow
    {
        $storage           =& $this->getSessionStorage();
        $state_manager     = new StateManager(
            new StateStorage($storage),
            new StateFactory(new RandomNumberGenerator())
        );
        $request_factory   = HTTPFactoryBuilder::requestFactory();
        $stream_factory    = HTTPFactoryBuilder::streamFactory();
        $http_client       = HttpClientFactory::createClient();
        $id_token_verifier = new IDTokenVerifier(new Parser(new JoseEncoder()), $issuer_claim_validator, new JWKSKeyFetcher($http_client, $request_factory), new Sha256(), new Validator());
        return new Flow(
            $state_manager,
            $provider_manager,
            new TokenRequestCreator($request_factory, $stream_factory, new BasicAuth()),
            new TokenRequestSender($http_client),
            $id_token_verifier,
            new UserInfoRequestCreator($request_factory),
            new UserInfoRequestSender($http_client)
        );
    }

    /**
     * @return AuthorizationRequestCreator
     */
    private function getAuthorizationRequestCreator()
    {
        $storage =& $this->getSessionStorage();
        return new AuthorizationRequestCreator(
            new StateManager(
                new StateStorage($storage),
                new StateFactory(new RandomNumberGenerator())
            )
        );
    }

    #[ListeningToEventClass]
    public function additionalConnectorsCollector(AdditionalConnectorsCollector $collector): void
    {
        $provider_manager    = $this->getProviderManager();
        $login_url_generator = new Login\LoginURLGenerator($this->getPluginPath());

        foreach ($provider_manager->getProvidersUsableToLogIn() as $provider) {
            $collector->addConnector(new AdditionalConnector(
                $provider->getName(),
                $login_url_generator->getLoginURL($provider, $collector->return_to),
                $provider->getIcon(),
                $provider->getColor()
            ));
        }
    }

    public function beforeUserRegistrationEvent(BeforeUserRegistrationEvent $event): void
    {
        $link_id = $event->getRequest()->get('openidconnect_link_id');

        if ($link_id) {
            $provider_manager         = $this->getProviderManager();
            $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
            try {
                $unlinked_account = $unlinked_account_manager->getbyId($link_id);
                $provider         = $provider_manager->getById($unlinked_account->getProviderId());

                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    sprintf(dgettext('tuleap-openidconnectclient', 'Your new user account will be linked with %1$s.'), $provider->getName())
                );
            } catch (Exception $ex) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-openidconnectclient', 'An error occurred, please retry')
                );
                $GLOBALS['Response']->redirect('/');
            }
            $event->noNeedForPassword();
        }
    }

    public function afterUserRegistrationEvent(AfterUserRegistrationEvent $event): void
    {
        $storage =& $this->getSessionStorage();
        $link_id = $storage[self::SESSION_LINK_ID_KEY] ?? '';

        if ($link_id) {
            $user_manager             = UserManager::instance();
            $provider_manager         = $this->getProviderManager();
            $user_mapping_manager     = $this->getUserMappingManager();
            $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
            $account_linker_controler = new AccountLinker\Controller(
                $user_manager,
                $provider_manager,
                $user_mapping_manager,
                $unlinked_account_manager,
                new ConnectorPresenterBuilder($provider_manager, new Login\LoginURLGenerator($this->getPluginPath())),
                EventManager::instance(),
                $storage,
            );

            $account_linker_controler->linkRegisteringAccount($event->getUser(), $link_id, $event->getRequest()->getTime());
        }
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-openidconnectclient', 'OpenID Connect Client'),
                $this->getPluginPath() . '/admin'
            )
        );
    }

    private function getLogger(): LoggerInterface
    {
        return BackendLogger::getDefaultLogger('openid_connect_client.log');
    }

    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        (new AccountTabsBuilder())->addTabs($collection);
    }

    public function routeGetUserAccount(): DispatchableWithRequest
    {
        $can_remove_user_mapping_checker = new CanRemoveUserMappingChecker();

        return new OIDCProvidersController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            OIDCProvidersController::getCSRFToken(),
            new UserMappingManager(
                new UserMappingDao(),
                new UserDao(),
                $can_remove_user_mapping_checker,
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            $this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint(
                $this->getProviderManager()
            ),
            new CanRemoveUserMappingChecker(),
            $this->getAssets(),
        );
    }

    public function routePostUserAccount(): DispatchableWithRequest
    {
        return new UnlinkController(
            OIDCProvidersController::getCSRFToken(),
            $this->getProviderManager(),
            $this->getUserMappingManager()
        );
    }

    public function routeAzureIndex(): DispatchableWithRequest
    {
        $user_manager             = UserManager::instance();
        $user_mapping_manager     = $this->getUserMappingManager();
        $unlinked_account_manager = new UnlinkedAccountManager(
            new UnlinkedAccountDao(),
            new RandomNumberGenerator()
        );

        $username_generator = new Login\Registration\UsernameGenerator(new UserNameNormalizer(new Rule_UserName(), new Cocur\Slugify\Slugify()));
        $provider_manager   = $this->getProviderManager();

        $automatic_user_registration = new Login\Registration\AutomaticUserRegistration(
            $user_manager,
            $username_generator,
        );

        $flow    = $this->getFlow($provider_manager, new AzureProviderIssuerClaimValidator());
        $storage =& $this->getSessionStorage();

        return new AzureADUserLinkController(
            new Login\Controller(
                $user_manager,
                $user_mapping_manager,
                $unlinked_account_manager,
                $automatic_user_registration,
                $flow,
                $this->getLogger(),
                $storage,
            )
        );
    }

    public function routeIndex(): DispatchableWithRequest
    {
        $user_manager                = UserManager::instance();
        $provider_manager            = $this->getProviderManager();
        $user_mapping_manager        = $this->getUserMappingManager();
        $unlinked_account_manager    = new UnlinkedAccountManager(
            new UnlinkedAccountDao(),
            new RandomNumberGenerator()
        );
        $username_generator          = new Login\Registration\UsernameGenerator(new UserNameNormalizer(new Rule_UserName(), new Cocur\Slugify\Slugify()));
        $automatic_user_registration = new Login\Registration\AutomaticUserRegistration(
            $user_manager,
            $username_generator,
        );

        $flow    = $this->getFlow($provider_manager, new GenericProviderIssuerClaimValidator());
        $storage =& $this->getSessionStorage();

        $login_controller          = new Login\Controller(
            $user_manager,
            $user_mapping_manager,
            $unlinked_account_manager,
            $automatic_user_registration,
            $flow,
            $this->getLogger(),
            $storage,
        );
        $account_linker_controller = new AccountLinker\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager,
            new ConnectorPresenterBuilder($provider_manager, new Login\LoginURLGenerator($this->getPluginPath())),
            EventManager::instance(),
            $storage,
        );
        return new Router(
            $login_controller,
            $account_linker_controller,
        );
    }

    public function routeAdminIndex(): DispatchableWithRequest
    {
        $provider_manager                               = $this->getProviderManager();
        $generic_provider_manager                       = new GenericProviderManager(new GenericProviderDao());
        $azure_provider_manager                         = new AzureADProviderManager(new AzureADProviderDao());
        $user_mapping_manager                           = $this->getUserMappingManager();
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $icon_presenter_factory                         = new IconPresenterFactory();
        $color_presenter_factory                        = new ColorPresenterFactory();
        $admin_page_renderer                            = new AdminPageRenderer();
        $controller                                     = new Administration\Controller(
            $provider_manager,
            $generic_provider_manager,
            $azure_provider_manager,
            $enable_unique_authentication_endpoint_verifier,
            $icon_presenter_factory,
            $color_presenter_factory,
            $admin_page_renderer,
            $this->getAssets(),
        );
        $csrf_token                                     = new CSRFSynchronizerToken(
            OPENIDCONNECTCLIENT_BASE_URL . '/admin'
        );

        return new AdminRouter($controller, $csrf_token);
    }

    public function routeLogin(): DispatchableWithRequest
    {
        return new LoginController(
            new ConnectorPresenterBuilder(
                $this->getProviderManager(),
                new Login\LoginURLGenerator($this->getPluginPath())
            )
        );
    }

    public function routeStartLoginToProvider(): Login\RedirectToProviderForAuthorizationController
    {
        return new Login\RedirectToProviderForAuthorizationController(
            HTTPFactoryBuilder::responseFactory(),
            $this->getProviderManager(),
            $this->getAuthorizationRequestCreator(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware($this->getName()),
            new DisableCacheMiddleware()
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeIndex'));
            $r->addRoute(['GET', 'POST'], '/admin[/[index.php]]', $this->getRouteHandler('routeAdminIndex'));
            $r->addRoute(['GET', 'POST'], '/login.php', $this->getRouteHandler('routeLogin'));
            $r->get('/azure/', $this->getRouteHandler('routeAzureIndex'));

            $r->get('/account', $this->getRouteHandler('routeGetUserAccount'));
            $r->post('/account', $this->getRouteHandler('routePostUserAccount'));

            $r->get('/login_to/{provider_id:\d+}', $this->getRouteHandler('routeStartLoginToProvider'));
        });
    }

    private function &getSessionStorage(): array
    {
        if (isset($_SESSION)) {
            return $_SESSION;
        }
        $storage = [];
        return $storage;
    }

    public function getConfigKeys(ConfigClassProvider $config_keys): void
    {
        $config_keys->addConfigClass(Login\Registration\AutomaticUserRegistration::class);
    }

    public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
    {
        if ($this->getUserMappingManager()->userHasProvider($event->user)) {
            $event->refuseLogin(dgettext('tuleap-openidconnectclient', 'Your account is linked to an OpenID Connect provider, you must use it to authenticate'));
        }
    }

    private function getUserMappingManager(): UserMappingManager
    {
        return new UserMappingManager(
            new UserMappingDao(),
            new UserDao(),
            new CanRemoveUserMappingChecker(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }
}
