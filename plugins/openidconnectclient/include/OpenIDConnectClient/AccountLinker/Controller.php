<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use ForgeConfig;
use HTTPRequest;
use PFUser;
use TemplateRendererFactory;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use UserManager;

class Controller {
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

    public function __construct(
        UserManager $user_manager,
        ProviderManager $provider_manager,
        UserMappingManager $user_mapping_manager,
        UnlinkedAccountManager $unlinked_account_manager
    ) {
        $this->user_manager             = $user_manager;
        $this->provider_manager         = $provider_manager;
        $this->user_mapping_manager     = $user_mapping_manager;
        $this->unlinked_account_manager = $unlinked_account_manager;
    }

    public function showIndex($link_id, $return_to) {
        try {
            $unlinked_account = $this->unlinked_account_manager->getbyId($link_id);
            $provider         = $this->provider_manager->getById($unlinked_account->getProviderId());
        } catch (Exception $ex) {
            $this->redirectToLoginPageAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'invalid_request')
            );
        }
        $presenter = new Presenter($link_id, $return_to, $provider->getName());
        $renderer  = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);

        $GLOBALS['HTML']->header(
            array('title' => $GLOBALS['Language']->getText('plugin_openidconnectclient', 'link_account'))
        );
        $renderer->renderToPage('linker', $presenter);
        $GLOBALS['HTML']->footer(array());
    }

    public function linkExistingAccount(HTTPRequest $request) {
        try {
            $unlinked_account = $this->unlinked_account_manager->getbyId($request->get('link_id'));
            $provider         = $this->provider_manager->getById($unlinked_account->getProviderId());
        } catch (Exception $ex) {
            $this->redirectToLoginPageAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'invalid_request')
            );
        }

        $user = $this->user_manager->login($request->get('loginname'), $request->get('password'));
        if ($user->isAnonymous()) {
            $this->showIndex($unlinked_account->getId(), $request->get('return_to'));
        } else {
            $this->linkAccount($user, $provider, $unlinked_account);

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText(
                    'plugin_openidconnectclient',
                    'successfully_linked',
                    array($provider->getName())
                )
            );
            require_once('account.php');
            \account_redirect_after_login($request->get('return_to'));
        }
    }

    private function linkAccount(PFUser $user, Provider $provider, UnlinkedAccount $unlinked_account) {
        try {
            $this->user_mapping_manager->create(
                $user->getId(),
                $provider->getId(),
                $unlinked_account->getUserIdentifier()
            );
            $this->unlinked_account_manager->removeById($unlinked_account->getId());
        } catch (Exception $ex) {
            $this->redirectToLoginPageAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'unexpected_error')
            );
        }
    }

    private function redirectToLoginPageAfterFailure($message) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $message
        );
        $GLOBALS['Response']->redirect('https://' . ForgeConfig::get('sys_https_host') . '/account/login.php');
    }
}