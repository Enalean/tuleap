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
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OpenIDConnectClient\AccountLinker;
use Tuleap\OpenIDConnectClient\AccountLinker\RegisterPresenter;
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
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchTemporaryRedirect;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\PasswordPreUpdateEvent;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class openidconnectclientPlugin extends Plugin // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-openidconnectclient', __DIR__ . '/../site-content');

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook(Event::LOGIN_ADDITIONAL_CONNECTOR);
        $this->addHook('before_register');
        $this->addHook(Event::USER_REGISTER_ADDITIONAL_FIELD);
        $this->addHook(Event::AFTER_USER_REGISTRATION);
        $this->addHook('anonymous_access_to_script_allowed');
        $this->addHook('javascript_file');
        $this->addHook('cssfile');
        $this->addHook('site_admin_option_hook');
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(PasswordPreUpdateEvent::NAME);
        $this->addHook(Event::GET_LOGIN_URL);
        $this->addHook('display_newaccount');
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);
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

    public function javascript_file($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="' . $this->getAssets()->getFileURL('open-id-connect-client.js') . '"></script>';
        }
    }

    public function cssfile()
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account') === 0 || strpos($_SERVER['REQUEST_URI'], '/plugins/openidconnectclient') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('fp-style.css') . '" />';
        }
    }

    public function burning_parrot_get_stylesheets($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($_SERVER['REQUEST_URI'] === '/') {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getAssets()->getFileURL('bp-style-' . $variant->getName() . '.css');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/openidconnectclient',
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
        return $this->canPluginAuthenticateUser() &&
        $provider_manager->isAProviderConfiguredAsUniqueAuthenticationEndpoint();
    }

    public function passwordPreUpdateEvent(PasswordPreUpdateEvent $event)
    {
        if ($this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint($this->getProviderManager())) {
            $event->oldPasswordIsNotRequiredToUpdatePassword();
        }
    }

    public function get_login_url($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $provider_manager = $this->getProviderManager();
        if (! $this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint($provider_manager)) {
            return;
        }
        if (ForgeConfig::get(ForgeAccess::CONFIG) !== ForgeAccess::ANONYMOUS) {
            $params['login_url'] = OPENIDCONNECTCLIENT_BASE_URL . '/login.php?' . http_build_query(
                array('return_to' => $params['return_to'])
            );
            return;
        }

        $url_generator = new Login\LoginUniqueAuthenticationUrlGenerator(
            $provider_manager,
            $this->getAuthorizationRequestCreator()
        );

        try {
            $params['login_url'] = $url_generator->getURL(urldecode($params['return_to']));
        } catch (IncoherentDataUniqueProviderException $exception) {
        }
    }

    public function display_newaccount($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $provider_manager = $this->getProviderManager();
        if ($this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint($provider_manager)) {
            $params['allow'] = false;
        }
    }

    /**
     * @return Flow
     */
    private function getFlow(ProviderManager $provider_manager, $audience_claim_validator)
    {
        $state_manager     = new StateManager(
            new StateStorage($_SESSION),
            new StateFactory(new RandomNumberGenerator())
        );
        $id_token_verifier = new IDTokenVerifier($audience_claim_validator);
        $request_factory   = HTTPFactoryBuilder::requestFactory();
        $stream_factory    = HTTPFactoryBuilder::streamFactory();
        $http_client       = HttpClientFactory::createClient();
        $flow              = new Flow(
            $state_manager,
            $provider_manager,
            new TokenRequestCreator($request_factory, $stream_factory),
            new TokenRequestSender($http_client),
            $id_token_verifier,
            new UserInfoRequestCreator($request_factory),
            new UserInfoRequestSender($http_client)
        );
        return $flow;
    }

    /**
     * @return AuthorizationRequestCreator
     */
    private function getAuthorizationRequestCreator()
    {
        return new AuthorizationRequestCreator(
            new StateManager(
                new StateStorage($_SESSION),
                new StateFactory(new RandomNumberGenerator())
            )
        );
    }

    /**
     * @return bool
     */
    private function canPluginAuthenticateUser()
    {
        return ForgeConfig::get('sys_auth_type') !== 'ldap';
    }

    public function login_additional_connector(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $this->canPluginAuthenticateUser()) {
            return;
        }
        if (! $params['is_secure']) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-openidconnectclient', 'The OpenID Connect plugin can only be used if the platform is accessible with HTTPS')
            );
            return;
        }

        $provider_manager                  = $this->getProviderManager();
        $login_connector_presenter_builder = new ConnectorPresenterBuilder(
            $provider_manager,
            $this->getAuthorizationRequestCreator()
        );
        $login_connector_presenter         = $login_connector_presenter_builder->getLoginConnectorPresenter(
            $params['return_to']
        );

        $renderer                        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $params['additional_connector'] .= $renderer->renderToString('login_connector', $login_connector_presenter);
    }

    public function before_register(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($this->isUserRegistrationWithOpenIDConnectPossible($params['is_registration_confirmation'], $link_id)) {
            $provider_manager         = $this->getProviderManager();
            $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
            try {
                $unlinked_account     = $unlinked_account_manager->getbyId($link_id);
                $provider             = $provider_manager->getById($unlinked_account->getProviderId());

                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    sprintf(dgettext('tuleap-openidconnectclient', 'Your new user account will be linked with %1$s. You must provide a password to use %2$s services (CVS, SVN, Git, FTP, ...).'), $provider->getName(), ForgeConfig::get('sys_name'))
                );
            } catch (Exception $ex) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-openidconnectclient', 'An error occurred, please retry')
                );
                $GLOBALS['Response']->redirect('/');
            }
        }
    }

    /**
     * @return bool
     */
    private function isUserRegistrationWithOpenIDConnectPossible($is_registration_confirmation, $link_id)
    {
        return ! $is_registration_confirmation && $link_id && $this->canPluginAuthenticateUser();
    }

    public function user_register_additional_field(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($link_id && $this->canPluginAuthenticateUser()) {
            $register_presenter       = new RegisterPresenter($link_id);
            $renderer                 = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
            $params['field']         .= $renderer->renderToString('register_field', $register_presenter);
        }
    }

    public function after_user_registration(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($link_id) {
            $user_manager             = UserManager::instance();
            $provider_manager         = $this->getProviderManager();
            $user_mapping_manager     = new UserMappingManager(new UserMappingDao());
            $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
            $account_linker_controler = new AccountLinker\Controller(
                $user_manager,
                $provider_manager,
                $user_mapping_manager,
                $unlinked_account_manager
            );

            $account_linker_controler->linkRegisteringAccount($params['user_id'], $link_id, $request->getTime());
        }
    }

    public function site_admin_option_hook($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['plugins'][] = array(
            'label' => dgettext('tuleap-openidconnectclient', 'OpenID Connect Client'),
            'href'  => $this->getPluginPath() . '/admin'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/admin') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    private function getLogger(): \Psr\Log\LoggerInterface
    {
        return \BackendLogger::getDefaultLogger('openid_connect_client.log');
    }

    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        if ($this->canPluginAuthenticateUser()) {
            (new AccountTabsBuilder())->addTabs($collection);
        }
    }

    public function routeGetUserAccount(): DispatchableWithRequest
    {
        if (! $this->canPluginAuthenticateUser()) {
            return new DispatchTemporaryRedirect('/account');
        }

        return new OIDCProvidersController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            OIDCProvidersController::getCSRFToken(),
            new UserMappingManager(
                new UserMappingDao()
            ),
            $this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint(
                $this->getProviderManager()
            ),
            $this->getAssets(),
        );
    }

    public function routePostUserAccount(): DispatchableWithRequest
    {
        if (! $this->canPluginAuthenticateUser()) {
            return new DispatchTemporaryRedirect('/account');
        }

        return new UnlinkController(
            OIDCProvidersController::getCSRFToken(),
            $this->getProviderManager(),
            new UserMappingManager(
                new UserMappingDao()
            )
        );
    }

    public function routeAzureIndex() : DispatchableWithRequest
    {
        $user_manager             = UserManager::instance();
        $user_mapping_manager     = new UserMappingManager(new UserMappingDao());
        $unlinked_account_manager = new UnlinkedAccountManager(
            new UnlinkedAccountDao(),
            new RandomNumberGenerator()
        );

        $username_generator = new Login\Registration\UsernameGenerator(new Rule_UserName());
        $provider_manager   = $this->getProviderManager();

        $automatic_user_registration = new Login\Registration\AutomaticUserRegistration(
            $user_manager,
            $username_generator
        );

        $flow = $this->getFlow($provider_manager, new AzureProviderIssuerClaimValidator());

        return new AzureADUserLinkController(
            new Login\Controller(
                $user_manager,
                $user_mapping_manager,
                $unlinked_account_manager,
                $automatic_user_registration,
                $flow,
                $this->getLogger(),
            )
        );
    }

    public function routeIndex() : DispatchableWithRequest
    {
        if (! $this->canPluginAuthenticateUser()) {
            return new DispatchTemporaryRedirect('/');
        }

        $user_manager                = UserManager::instance();
        $provider_manager            = $this->getProviderManager();
        $user_mapping_manager        = new UserMappingManager(new UserMappingDao());
        $unlinked_account_manager    = new UnlinkedAccountManager(
            new UnlinkedAccountDao(),
            new RandomNumberGenerator()
        );
        $username_generator          = new Login\Registration\UsernameGenerator(new Rule_UserName());
        $automatic_user_registration = new Login\Registration\AutomaticUserRegistration(
            $user_manager,
            $username_generator
        );

        $flow = $this->getFlow($provider_manager, new GenericProviderIssuerClaimValidator());

        $login_controller          = new Login\Controller(
            $user_manager,
            $user_mapping_manager,
            $unlinked_account_manager,
            $automatic_user_registration,
            $flow,
            $this->getLogger(),
        );
        $account_linker_controller = new AccountLinker\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager
        );
        return new Router(
            $login_controller,
            $account_linker_controller,
        );
    }

    public function routeAdminIndex() : DispatchableWithRequest
    {
        $provider_manager                               = $this->getProviderManager();
        $generic_provider_manager                       = new GenericProviderManager(new GenericProviderDao());
        $azure_provider_manager                         = new AzureADProviderManager(new AzureADProviderDao());
        $user_mapping_manager                           = new UserMappingManager(new UserMappingDao());
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

    public function routeLogin() : DispatchableWithRequest
    {
        if (! $this->canPluginAuthenticateUser()) {
            return new DispatchTemporaryRedirect('/');
        }

        return new LoginController(
            new ConnectorPresenterBuilder(
                $this->getProviderManager(),
                $this->getAuthorizationRequestCreator()
            )
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event) : void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeIndex'));
            $r->addRoute(['GET', 'POST'], '/admin[/[index.php]]', $this->getRouteHandler('routeAdminIndex'));
            $r->addRoute(['GET', 'POST'], '/login.php', $this->getRouteHandler('routeLogin'));
            $r->get('/azure/', $this->getRouteHandler('routeAzureIndex'));

            $r->get('/account', $this->getRouteHandler('routeGetUserAccount'));
            $r->post('/account', $this->getRouteHandler('routePostUserAccount'));
        });
    }
}
