<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\MessageFactoryBuilder;
use Tuleap\OpenIDConnectClient\AccountLinker\RegisterPresenter;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountDao;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountManager;
use Tuleap\OpenIDConnectClient\AccountLinker;
use Tuleap\OpenIDConnectClient\AdminRouter;
use Tuleap\OpenIDConnectClient\Administration\IconPresenterFactory;
use Tuleap\OpenIDConnectClient\Administration\ColorPresenterFactory;
use Tuleap\OpenIDConnectClient\Authentication\AuthorizationDispatcher;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\IDTokenVerifier;
use Tuleap\OpenIDConnectClient\Authentication\StateFactory;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Authentication\StateStorage;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestSender;
use Tuleap\OpenIDConnectClient\Authentication\Uri\Generator;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestSender;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\Login;
use Tuleap\OpenIDConnectClient\Login\IncoherentDataUniqueProviderException;
use Tuleap\OpenIDConnectClient\OpenIDConnectClientLogger;
use Tuleap\OpenIDConnectClient\OpenIDConnectClientPluginInfo;
use Tuleap\OpenIDConnectClient\Provider\EnableUniqueAuthenticationEndpointVerifier;
use Tuleap\OpenIDConnectClient\Provider\ProviderDao;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Router;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserPreferencesPresenter;
use Tuleap\OpenIDConnectClient\UserMapping;
use Tuleap\OpenIDConnectClient\Administration;
use Tuleap\Admin\AdminPageRenderer;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class openidconnectclientPlugin extends Plugin {
    public function __construct($id) {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook(Event::LOGIN_ADDITIONAL_CONNECTOR);
        $this->addHook('before_register');
        $this->addHook(Event::USER_REGISTER_ADDITIONAL_FIELD);
        $this->addHook(Event::AFTER_USER_REGISTRATION);
        $this->addHook('anonymous_access_to_script_allowed');
        $this->addHook('javascript_file');
        $this->addHook('cssfile');
        $this->addHook(Event::MANAGE_THIRD_PARTY_APPS);
        $this->addHook('site_admin_option_hook');
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(Event::IS_OLD_PASSWORD_REQUIRED_FOR_PASSWORD_CHANGE);
        $this->addHook(Event::GET_LOGIN_URL);
        $this->addHook('display_newaccount');
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

    public function anonymous_access_to_script_allowed($params) {
        if (strpos($params['script_name'], $this->getPluginPath()) === 0) {
            $params['anonymous_allowed'] = true;
        }
    }

    public function javascript_file($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/open-id-connect-client.js"></script>';
        }
    }

    public function cssfile() {
        if (strpos($_SERVER['REQUEST_URI'], '/account') === 0 || strpos($_SERVER['REQUEST_URI'], '/plugins/openidconnectclient') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if ($this->isInBurningParrotCompatiblePage()) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['javascript_files'][] = $this->getPluginPath().'/scripts/open-id-connect-client.js';
        }
    }

    private function isInBurningParrotCompatiblePage()
    {
        $uri = $_SERVER['REQUEST_URI'];

        return (
            strpos($uri, '/account') === 0
            || strpos($uri, '/plugins/openidconnectclient') === 0
            || $uri === '/'
        );
    }

    /**
     * @return ProviderManager
     */
    private function getProviderManager()
    {
        return new ProviderManager(new ProviderDao());
    }

    /**
     * @return bool
     */
    private function isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint(ProviderManager $provider_manager)
    {
        return $this->canPluginAuthenticateUser() &&
        $provider_manager->isAProviderConfiguredAsUniqueAuthenticationEndpoint();
    }

    public function old_password_required_for_password_change($params)
    {
        $provider_manager                = $this->getProviderManager();
        $params['old_password_required'] = !$this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint(
            $provider_manager
        );
    }

    public function get_login_url($params)
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
            $this->getFlow($provider_manager)
        );

        try {
            $params['login_url'] = $url_generator->getURL(urldecode($params['return_to']));
        } catch (IncoherentDataUniqueProviderException $exception) {
        }
    }

    public function display_newaccount($params)
    {
        $provider_manager = $this->getProviderManager();
        if ($this->isLoginConfiguredToUseAProviderAsUniqueAuthenticationEndpoint($provider_manager)) {
            $params['allow'] = false;
        }
    }

    /**
     * @return Flow
     */
    private function getFlow(ProviderManager $provider_manager)
    {
        $state_manager     = new StateManager(
            new StateStorage(),
            new StateFactory(new RandomNumberGenerator())
        );
        $id_token_verifier = new IDTokenVerifier();
        $uri_generator     = new Generator();
        $request_factory   = MessageFactoryBuilder::build();
        $http_client       = HttpClientFactory::createClient();
        $flow              = new Flow(
            $state_manager,
            new AuthorizationDispatcher($state_manager, $uri_generator),
            $provider_manager,
            new TokenRequestCreator($request_factory),
            new TokenRequestSender($http_client),
            $id_token_verifier,
            new UserInfoRequestCreator($request_factory),
            new UserInfoRequestSender($http_client)
        );
        return $flow;
    }

    /**
     * @return bool
     */
    private function canPluginAuthenticateUser() {
        return ForgeConfig::get('sys_auth_type') !== 'ldap';
    }

    public function login_additional_connector(array $params) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
        }
        if(! $params['is_secure']) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'only_https_possible')
            );
            return;
        }

        $provider_manager                  = $this->getProviderManager();
        $flow                              = $this->getFlow($provider_manager);
        $login_connector_presenter_builder = new ConnectorPresenterBuilder($provider_manager, $flow);
        $login_connector_presenter         = $login_connector_presenter_builder->getLoginConnectorPresenter(
            $params['return_to']
        );

        $renderer                        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $params['additional_connector'] .= $renderer->renderToString('login_connector', $login_connector_presenter);
    }

    public function before_register(array $params) {
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
                    $GLOBALS['Language']->getText(
                        'plugin_openidconnectclient',
                        'info_registration',
                        array($provider->getName(), ForgeConfig::get('sys_name'))
                    )
                );
            } catch (Exception $ex) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('plugin_openidconnectclient', 'unexpected_error')
                );
                $GLOBALS['Response']->redirect('/');
            }
        }
    }

    /**
     * @return bool
     */
    private function isUserRegistrationWithOpenIDConnectPossible($is_registration_confirmation, $link_id) {
        return ! $is_registration_confirmation && $link_id && $this->canPluginAuthenticateUser();
    }

    public function user_register_additional_field(array $params) {
        $request = $params['request'];
        $link_id = $request->get('openidconnect_link_id');

        if ($link_id && $this->canPluginAuthenticateUser()) {
            $register_presenter       = new RegisterPresenter($link_id);
            $renderer                 = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
            $params['field']         .= $renderer->renderToString('register_field', $register_presenter);
        }
    }

    public function after_user_registration(array $params) {
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

    public function manage_third_party_apps(array $params) {
        $user                 = $params['user'];
        $user_mapping_manager = new UserMappingManager(new UserMappingDao());
        $user_mappings_usage  = $user_mapping_manager->getUsageByUser($user);

        if (count($user_mappings_usage) > 0 && $this->canPluginAuthenticateUser()) {
            $renderer        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
            $csrf_token      = new CSRFSynchronizerToken('openid-connect-user-preferences');
            $presenter       = new UserPreferencesPresenter($user_mappings_usage, $csrf_token);
            $params['html'] .= $renderer->renderToString('user_preference', $presenter);
        }
    }

    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => $GLOBALS['Language']->getText('plugin_openidconnectclient', 'descriptor_name'),
            'href'  => $this->getPluginPath() . '/admin/index.php'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath().'/admin/') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function process(HTTPRequest $request) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
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
        $flow                        = $this->getFlow($provider_manager);

        $login_controller          = new Login\Controller(
            $user_manager,
            $user_mapping_manager,
            $unlinked_account_manager,
            $automatic_user_registration,
            $flow,
            new OpenIDConnectClientLogger()
        );
        $account_linker_controller = new AccountLinker\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager
        );
        $user_mapping_controller   = new UserMapping\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager
        );
        $router                    = new Router(
            $login_controller,
            $account_linker_controller,
            $user_mapping_controller);
        $router->route($request);
    }

    public function processAdmin(HTTPRequest $request)
    {
        $provider_manager                               = $this->getProviderManager();
        $user_mapping_manager                           = new UserMappingManager(new UserMappingDao());
        $enable_unique_authentication_endpoint_verifier = new EnableUniqueAuthenticationEndpointVerifier(
            $user_mapping_manager
        );
        $icon_presenter_factory                         = new IconPresenterFactory();
        $color_presenter_factory                        = new ColorPresenterFactory();
        $admin_page_renderer                            = new AdminPageRenderer();
        $controller                                     = new Administration\Controller(
            $provider_manager,
            $enable_unique_authentication_endpoint_verifier,
            $icon_presenter_factory,
            $color_presenter_factory,
            $admin_page_renderer
        );
        $csrf_token                                     = new CSRFSynchronizerToken(
            OPENIDCONNECTCLIENT_BASE_URL . '/admin'
        );

        $router = new AdminRouter($controller, $csrf_token);
        $router->route($request);
    }

    public function processSpecificLoginPage(HTTPRequest $request)
    {
        if (!$this->canPluginAuthenticateUser()) {
            $GLOBALS['Response']->redirect('/');
        }

        $provider_manager        = $this->getProviderManager();
        $flow                    = $this->getFlow($provider_manager);
        $login_presenter_builder = new ConnectorPresenterBuilder($provider_manager, $flow);

        $return_to = $request->get('return_to');
        $presenter = $login_presenter_builder->getLoginSpecificPageConnectorPresenter($return_to);

        $GLOBALS['HTML']->header(array(
            'title' => $GLOBALS['Language']->getText('account_login', 'page_title',
                array(ForgeConfig::get('sys_name'))),
            'body_class' => array('login-page')
        ));
        $renderer = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $renderer->renderToPage('login-page', $presenter);
        $GLOBALS['HTML']->footer(array('without_content' => true));
    }
}
