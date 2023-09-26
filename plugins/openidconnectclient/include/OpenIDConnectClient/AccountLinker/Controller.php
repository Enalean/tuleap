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

namespace Tuleap\OpenIDConnectClient\AccountLinker;

use Exception;
use Feedback;
use HTTPRequest;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\User\Account\AuthenticationMeanName;
use Tuleap\User\Account\RegistrationGuardEvent;
use UserManager;

class Controller
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProviderManager
     */
    private $provider_manager;

    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;

    /**
     * @var UnlinkedAccountManager
     */
    private $unlinked_account_manager;
    /**
     * @var ConnectorPresenterBuilder
     */
    private $connector_presenter_builder;
    /**
     * @var array
     */
    private $session_storage;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        UserManager $user_manager,
        ProviderManager $provider_manager,
        UserMappingManager $user_mapping_manager,
        UnlinkedAccountManager $unlinked_account_manager,
        ConnectorPresenterBuilder $connector_presenter_builder,
        EventDispatcherInterface $event_dispatcher,
        array &$session_storage,
    ) {
        $this->user_manager                = $user_manager;
        $this->provider_manager            = $provider_manager;
        $this->user_mapping_manager        = $user_mapping_manager;
        $this->unlinked_account_manager    = $unlinked_account_manager;
        $this->connector_presenter_builder = $connector_presenter_builder;
        $this->session_storage             =& $session_storage;
        $this->event_dispatcher            = $event_dispatcher;
    }

    public function showIndex(HTTPRequest $request): void
    {
        try {
            $unlinked_account = $this->unlinked_account_manager->getbyId($this->session_storage[\openidconnectclientPlugin::SESSION_LINK_ID_KEY] ?? '');
            $provider         = $this->provider_manager->getById($unlinked_account->getProviderId());
        } catch (Exception $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'Request seems invalid, please retry')
            );
        }
        $return_to                = $request->get('return_to');
        $link_to_register_page    = $this->generateLinkToRegisterPage($request);
        $registration_guard       = $this->event_dispatcher->dispatch(new RegistrationGuardEvent());
        $authentication_mean_name = $this->event_dispatcher->dispatch(new AuthenticationMeanName());
        $presenter                = new Presenter(
            $return_to,
            $provider->getName(),
            $link_to_register_page,
            $registration_guard->isRegistrationPossible(),
            $this->connector_presenter_builder->getLoginConnectorPresenter(
                OPENIDCONNECTCLIENT_BASE_URL . '/?' . http_build_query(['action' => 'link-existing', 'return_to' => $return_to]),
            ),
            $authentication_mean_name,
        );
        $renderer                 = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);

        $GLOBALS['HTML']->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-openidconnectclient', 'Link an account'))
                ->withBodyClass(['openid-connect-link'])
                ->build()
        );
        $renderer->renderToPage('linker', $presenter);
        $GLOBALS['HTML']->footer(FooterConfiguration::withoutContent());
    }

    private function generateLinkToRegisterPage(HTTPRequest $request)
    {
        $openid_connect_to_register_page = [
            'name'     => 'form_realname',
            'nickname' => 'form_loginname',
            'email'    => 'form_email',
            'zoneinfo' => 'timezone',
        ];

        $query_parameters = [];
        foreach ($openid_connect_to_register_page as $openid_connect_param => $register_page_param) {
            if ($request->existAndNonEmpty($openid_connect_param)) {
                $query_parameters[$register_page_param] = $request->get($openid_connect_param);
            }
        }

        return '/account/register.php?' . http_build_query($query_parameters);
    }

    public function linkExistingAccount(HTTPRequest $request): void
    {
        try {
            $unlinked_account = $this->unlinked_account_manager->getbyId($this->session_storage[\openidconnectclientPlugin::SESSION_LINK_ID_KEY] ?? '');
            $provider         = $this->provider_manager->getById($unlinked_account->getProviderId());
        } catch (Exception $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'Request seems invalid, please retry')
            );
        }

        $user = $this->user_manager->getCurrentUser();
        if ($user->isAnonymous()) {
            $user = $this->user_manager->login($request->get('loginname'), new ConcealedString($request->get('password')));
        }
        if ($user->isAnonymous()) {
            $this->showIndex($request);
        } else {
            $request_time = $request->getTime();
            $this->linkAccount($user, $provider, $unlinked_account, $request_time);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                sprintf(dgettext('tuleap-openidconnectclient', 'Your account has been successfully linked to %1$s'), $provider->getName())
            );
            $this->redirectAfterLogin($user, $request);
        }
    }

    protected function redirectAfterLogin(PFUser $user, HTTPRequest $request): void
    {
        require_once __DIR__ . '/../../../../../src/www/include/account.php';
        \account_redirect_after_login($user, $request->get('return_to'));
    }

    public function linkRegisteringAccount(PFUser $user, $link_id, $request_time)
    {
        try {
            $unlinked_account = $this->unlinked_account_manager->getbyId($link_id);
            $provider         = $this->provider_manager->getById($unlinked_account->getProviderId());

            $this->linkAccount($user, $provider, $unlinked_account, $request_time);
        } catch (Exception $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-openidconnectclient', 'Request seems invalid, please retry')
            );
        }
    }

    private function linkAccount(PFUser $user, Provider $provider, UnlinkedAccount $unlinked_account, $request_time): void
    {
        try {
            $this->user_mapping_manager->create(
                $user->getId(),
                $provider->getId(),
                $unlinked_account->getUserIdentifier(),
                $request_time
            );
            $this->unlinked_account_manager->removeById($unlinked_account->getId());
            unset($this->session_storage[\openidconnectclientPlugin::SESSION_LINK_ID_KEY]);
        } catch (Exception $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'An error occurred, please retry')
            );
        }
    }

    /**
     * @psalm-return never-return
     */
    private function redirectAfterFailure($message): void
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $message
        );
        $GLOBALS['Response']->redirect('/');
        exit();
    }
}
