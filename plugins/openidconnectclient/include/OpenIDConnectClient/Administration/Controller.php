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

namespace Tuleap\OpenIDConnectClient\Administration;


use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\OpenIDConnectClient\Provider\ProviderDataAccessException;
use Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;

class Controller {
    /**
     * @var ProviderManager
     */
    private $provider_manager;

    public function __construct(ProviderManager $provider_manager) {
        $this->provider_manager = $provider_manager;
    }

    public function showAdministration(CSRFSynchronizerToken $csrf_token) {
        $presenter = new Presenter(
            $this->provider_manager->getProviders(),
            $csrf_token->fetchHTMLInput()
        );
        $renderer  = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $GLOBALS['HTML']->header(
            array(
                'title' => $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'title'),
                'selected_top_tab' => 'admin'
            )
        );
        $renderer->renderToPage('administration-providers', $presenter);
        $GLOBALS['HTML']->footer(array());
    }

    public function createProvider(CSRFSynchronizerToken $csrf_token, HTTPRequest $request) {
        $csrf_token->check();

        $name                   = $request->get('name');
        $authorization_endpoint = $request->get('authorization_endpoint');
        $token_endpoint         = $request->get('token_endpoint');
        $userinfo_endpoint      = $request->get('userinfo_endpoint');
        $client_id              = $request->get('client_id');
        $client_secret          = $request->get('client_secret');

        try {
            $provider = $this->provider_manager->create(
                $name,
                $authorization_endpoint,
                $token_endpoint,
                $userinfo_endpoint,
                $client_id,
                $client_secret
            );
        } catch (ProviderDataAccessException $ex) {
            $this->redirectAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'add_new_provider_error')
            );
        } catch (ProviderMalformedDataException $ex) {
            $this->redirectAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'malformed_data_error')
            );
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText(
                'plugin_openidconnectclient_admin',
                'add_new_provider_success',
                array($provider->getName())
            )
        );
        $this->showAdministration($csrf_token);
    }

    public function removeProvider(CSRFSynchronizerToken $csrf_token, $provider_id) {
        $csrf_token->check();

        try {
            $provider = $this->provider_manager->getById($provider_id);
            $this->provider_manager->remove($provider);
        } catch (ProviderNotFoundException $ex) {
            $this->redirectAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'malformed_data_error')
            );
        } catch (ProviderDataAccessException $ex) {
            $this->redirectAfterFailure(
                $GLOBALS['Language']->getText(
                    'plugin_openidconnectclient_admin',
                    'remove_provider_error',
                    array($provider->getName())
                )
            );
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText(
                'plugin_openidconnectclient_admin',
                'remove_provider_success',
                array($provider->getName())
            )
        );
        $this->showAdministration($csrf_token);
    }

    private function redirectAfterFailure($message) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $message
        );
        $GLOBALS['Response']->redirect(OPENIDCONNECTCLIENT_BASE_URL . '/admin/');
    }

}