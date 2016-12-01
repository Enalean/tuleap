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
use PFUser;
use Tuleap\OpenIDConnectClient\Provider\EnableUniqueAuthenticationEndpointVerifier;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderDataAccessException;
use Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;
use Tuleap\Admin\AdminPageRenderer;

class Controller {

    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var EnableUniqueAuthenticationEndpointVerifier
     */
    private $enable_unique_authentication_endpoint_verifier;
    /**
     * @var IconPresenterFactory
     */
    private $icon_presenter_factory;
    /**
     * @var ColorPresenterFactory
     */
    private $color_presenter_factory;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(
        ProviderManager $provider_manager,
        EnableUniqueAuthenticationEndpointVerifier $enable_unique_authentication_endpoint_verifier,
        IconPresenterFactory $icon_presenter_factory,
        ColorPresenterFactory $color_presenter_factory,
        AdminPageRenderer $admin_page_renderer
    ) {
        $this->provider_manager                               = $provider_manager;
        $this->enable_unique_authentication_endpoint_verifier = $enable_unique_authentication_endpoint_verifier;
        $this->icon_presenter_factory                         = $icon_presenter_factory;
        $this->color_presenter_factory                        = $color_presenter_factory;
        $this->admin_page_renderer                            = $admin_page_renderer;
    }

    public function showAdministration(CSRFSynchronizerToken $csrf_token, PFUser $user)
    {
        $providers            = $this->provider_manager->getProviders();
        $providers_presenters = array();

        foreach ($providers as $provider) {
            $providers_presenters[] = new ProviderPresenter(
                $provider,
                $this->enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user),
                $this->icon_presenter_factory->getIconsPresentersForProvider($provider),
                $this->color_presenter_factory->getColorsPresentersForProvider($provider)
            );
        }

        $presenter = new Presenter(
            $providers_presenters,
            $this->icon_presenter_factory->getIconsPresenters(),
            $this->color_presenter_factory->getColorsPresenters(),
            $csrf_token
        );

        $this->admin_page_renderer->renderAPresenter(
            $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'title'),
            OPENIDCONNECTCLIENT_TEMPLATE_DIR,
            $presenter::TEMPLATE,
            $presenter
        );
    }

    public function createProvider(CSRFSynchronizerToken $csrf_token, HTTPRequest $request) {
        $csrf_token->check();

        $name                   = $request->get('name');
        $authorization_endpoint = $request->get('authorization_endpoint');
        $token_endpoint         = $request->get('token_endpoint');
        $userinfo_endpoint      = $request->get('userinfo_endpoint') ? $request->get('userinfo_endpoint') : '';
        $client_id              = $request->get('client_id');
        $client_secret          = $request->get('client_secret');
        $icon                   = $request->get('icon');
        $color                  = $request->get('color');

        try {
            $provider = $this->provider_manager->create(
                $name,
                $authorization_endpoint,
                $token_endpoint,
                $userinfo_endpoint,
                $client_id,
                $client_secret,
                $icon,
                $color
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

        $GLOBALS['Response']->redirect(OPENIDCONNECTCLIENT_BASE_URL . '/admin');
    }

    public function updateProvider(CSRFSynchronizerToken $csrf_token, HTTPRequest $request) {
        $csrf_token->check();

        $id                                = $request->get('id');
        $name                              = $request->get('name');
        $authorization_endpoint            = $request->get('authorization_endpoint');
        $token_endpoint                    = $request->get('token_endpoint');
        $userinfo_endpoint                 = $request->get('userinfo_endpoint') ? $request->get('userinfo_endpoint') : '';
        $is_unique_authentication_endpoint = $request->existAndNonEmpty('unique_authentication_endpoint');
        $client_id                         = $request->get('client_id');
        $client_secret                     = $request->get('client_secret');
        $icon                              = $request->get('icon');
        $color                             = $request->get('color');

        $provider = new Provider(
            $id,
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $userinfo_endpoint,
            $client_id,
            $client_secret,
            $is_unique_authentication_endpoint,
            $icon,
            $color
        );

        if (! $this->enable_unique_authentication_endpoint_verifier->canBeEnabledBy(
            $provider,
            $request->getCurrentUser()
        )) {
            $this->redirectAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'malformed_data_error')
            );
        }

        try {
            $this->provider_manager->update($provider);
        } catch (ProviderDataAccessException $ex) {
            $this->redirectAfterFailure(
                $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'update_provider_error')
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
                'update_provider_success',
                array($provider->getName())
            )
        );
        $this->showAdministration($csrf_token, $request->getCurrentUser());
    }

    public function removeProvider(CSRFSynchronizerToken $csrf_token, $provider_id, PFUser $user)
    {
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
        $this->showAdministration($csrf_token, $user);
    }

    private function redirectAfterFailure($message) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $message
        );
        $GLOBALS['Response']->redirect(OPENIDCONNECTCLIENT_BASE_URL . '/admin/');
    }

}
