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

namespace Tuleap\OpenIDConnectClient\Login;

use Feedback;
use Psr\Log\LoggerInterface;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountDataAccessException;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountManager;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\FlowResponse;
use Tuleap\OpenIDConnectClient\Login\Registration\AutomaticUserRegistration;
use Tuleap\OpenIDConnectClient\UserMapping\UserMapping;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDataAccessException;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;
use Exception;
use Tuleap\User\SessionNotCreatedException;
use User_LoginException;
use UserNotActiveException;
use UserManager;

class Controller
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;

    /**
     * @var UnlinkedAccountManager
     */
    private $unlinked_account_manager;

    /**
     * @var AutomaticUserRegistration
     */
    private $automatic_user_registration;

    /**
     * @var Flow
     */
    private $flow;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        UserManager $user_manager,
        UserMappingManager $user_mapping_manager,
        UnlinkedAccountManager $unlinked_account_manager,
        AutomaticUserRegistration $automatic_user_registration,
        Flow $flow,
        LoggerInterface $logger
    ) {
        $this->user_manager                = $user_manager;
        $this->user_mapping_manager        = $user_mapping_manager;
        $this->unlinked_account_manager    = $unlinked_account_manager;
        $this->automatic_user_registration = $automatic_user_registration;
        $this->flow                        = $flow;
        $this->logger                      = $logger;
    }

    public function login(\HTTPRequest $request, $return_to, $login_time)
    {
        require_once __DIR__ . '/../../../../../src/www/include/account.php';
        $this->checkIfUserAlreadyLogged($return_to);

        try {
            $flow_response = $this->flow->process($request);
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->logger->debug($ex->getTraceAsString());
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'Request seems invalid, please retry')
            );
        }

        $provider          = $flow_response->getProvider();
        $user_identifier   = $flow_response->getUserIdentifier();

        try {
            $user_mapping = $this->user_mapping_manager->getByProviderAndIdentifier(
                $provider,
                $user_identifier
            );
            $this->openSession($user_mapping, $flow_response->getReturnTo(), $login_time);
        } catch (UserMappingNotFoundException $ex) {
            $this->dealWithUnregisteredUser($flow_response, $login_time);
        }
    }

    private function checkIfUserAlreadyLogged($return_to)
    {
        $user = $this->user_manager->getCurrentUser();
        if ($user->isLoggedIn()) {
            \account_redirect_after_login($return_to);
        }
    }

    private function openSession(UserMapping $user_mapping, $return_to, $login_time)
    {
        $user = $this->user_manager->getUserById($user_mapping->getUserId());
        try {
            $this->user_manager->openSessionForUser($user);
        } catch (User_LoginException $ex) {
            $this->redirectAfterFailure($ex->getMessage());
        } catch (UserNotActiveException $ex) {
            $this->redirectAfterFailure($ex->getMessage());
        } catch (SessionNotCreatedException $ex) {
            $this->redirectAfterFailure($ex->getMessage());
        }
        try {
            $this->user_mapping_manager->updateLastUsed($user_mapping, $login_time);
        } catch (UserMappingDataAccessException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-openidconnectclient', 'An error occurred, please retry')
            );
        }
        \account_redirect_after_login($return_to);
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

    private function dealWithUnregisteredUser(FlowResponse $flow_response, $login_time)
    {
        $provider = $flow_response->getProvider();
        if (! $provider->isUniqueAuthenticationEndpoint()) {
            $this->redirectToLinkAnUnknowAccount($flow_response);
        }

        $user_information = $flow_response->getUserInformations();
        if (count($this->user_manager->getAllUsersByEmail($user_information['email'])) > 0) {
            $this->redirectToLinkAnUnknowAccount($flow_response);
        }

        $user_identifier = $flow_response->getUserIdentifier();
        try {
            $user = $this->automatic_user_registration->register($user_information);
            $this->user_mapping_manager->create($user->getId(), $provider->getId(), $user_identifier, $login_time);
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->logger->debug($ex->getTraceAsString());
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'An error occurred, please retry')
            );
        }

        if ($user->isAlive()) {
            $user_mapping = $this->user_mapping_manager->getByProviderAndIdentifier($provider, $user_identifier);
            $this->openSession($user_mapping, $flow_response->getReturnTo(), $login_time);
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-openidconnectclient', 'Your account have been created but needs to be approved by an administrator')
        );
        $GLOBALS['Response']->redirect('/');
    }

    private function redirectToLinkAnUnknowAccount(FlowResponse $flow_response)
    {
        $provider          = $flow_response->getProvider();
        $user_identifier   = $flow_response->getUserIdentifier();
        try {
            $unlinked_account  = $this->unlinked_account_manager->create($provider->getId(), $user_identifier);
        } catch (UnlinkedAccountDataAccessException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'An error occurred, please retry')
            );
        }

        $query_parameters = array(
            'action'    => 'link',
            'link_id'   => $unlinked_account->getId(),
            'return_to' => $flow_response->getReturnTo(),
        );
        $user_informations = $flow_response->getUserInformations();
        foreach (array('name', 'nickname', 'email', 'zoneinfo') as $query_parameter) {
            if (isset($user_informations[$query_parameter])) {
                $query_parameters[$query_parameter] = $user_informations[$query_parameter];
            }
        }

        $GLOBALS['Response']->redirect(
            OPENIDCONNECTCLIENT_BASE_URL . '/?' . http_build_query($query_parameters)
        );
    }
}
