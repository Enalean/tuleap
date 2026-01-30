<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use PFUser;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AcceptableTenantForAuthenticationConfiguration;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProvider;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProviderManager;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\UnknownAcceptableTenantForAuthenticationIdentifierException;
use Tuleap\OpenIDConnectClient\Provider\EnableUniqueAuthenticationEndpointVerifier;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProvider;
use Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderMalformedDataException;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException;

class Controller
{
    public function __construct(
        private readonly ProviderManager $provider_manager,
        private readonly GenericProviderManager $generic_provider_manager,
        private readonly AzureADProviderManager $azure_provider_manager,
        private readonly EnableUniqueAuthenticationEndpointVerifier $enable_unique_authentication_endpoint_verifier,
        private readonly IconPresenterFactory $icon_presenter_factory,
        private readonly ColorPresenterFactory $color_presenter_factory,
        private readonly AdminPageRenderer $admin_page_renderer,
        private readonly IncludeViteAssets $assets,
    ) {
    }

    public function showAdministration(CSRFSynchronizerToken $csrf_token, PFUser $user)
    {
        $providers            = $this->provider_manager->getProviders();
        $providers_presenters = [];

        foreach ($providers as $provider) {
            if ($provider instanceof AzureADProvider) {
                $providers_presenters[] = new AzureProviderPresenter(
                    $provider,
                    $this->enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user),
                    $this->icon_presenter_factory->getIconsPresentersForProvider($provider),
                    $this->color_presenter_factory->getColorsPresentersForProvider($provider)
                );
                continue;
            } elseif ($provider instanceof GenericProvider) {
                $providers_presenters[] = new GenericProviderPresenter(
                    $provider,
                    $this->enable_unique_authentication_endpoint_verifier->canBeEnabledBy($provider, $user),
                    $this->icon_presenter_factory->getIconsPresentersForProvider($provider),
                    $this->color_presenter_factory->getColorsPresentersForProvider($provider)
                );
            }
        }

        $presenter = new Presenter(
            $providers_presenters,
            $this->provider_manager->isAProviderConfiguredAsUniqueAuthenticationEndpoint(),
            $this->icon_presenter_factory->getIconsPresenters(),
            $this->color_presenter_factory->getColorsPresenters(),
            AzureADTenantSetupPresenter::fromAllAcceptableValues(AzureADTenantSetup::allPossibleSetups(), AzureADTenantSetup::tenantSpecific()),
            $csrf_token
        );

        $this->admin_page_renderer->addJavascriptAsset(new JavascriptViteAsset($this->assets, 'scripts/open-id-connect-client.js'));
        $this->admin_page_renderer->addCssAsset(CssViteAsset::fromFileName($this->assets, 'themes/BurningParrot/css/style.scss'));

        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-openidconnectclient', 'OpenID Connect'),
            OPENIDCONNECTCLIENT_TEMPLATE_DIR,
            $presenter::TEMPLATE,
            $presenter
        );
    }

    /**
     * @throws ProviderMalformedDataException
     */
    public function createGenericProvider(CSRFSynchronizerToken $csrf_token, \Tuleap\HTTPRequest $request): void
    {
        $csrf_token->check();

        try {
            $name                   = $request->get('name');
            $authorization_endpoint = $request->get('authorization_endpoint');
            $token_endpoint         = $request->get('token_endpoint');
            $jwks_endpoint          = $request->get('jwks_endpoint') ?: '';
            $userinfo_endpoint      = $request->get('userinfo_endpoint') ?: '';
            $client_id              = $request->get('client_id');
            $client_secret          = $request->get('client_secret');
            $icon                   = $request->get('icon');
            $color                  = $request->get('color');

            $provider = $this->generic_provider_manager->createGenericProvider(
                $name,
                $authorization_endpoint,
                $token_endpoint,
                $jwks_endpoint,
                $userinfo_endpoint,
                $client_id,
                $client_secret,
                $icon,
                $color
            );
        } catch (ProviderMalformedDataException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(
                dgettext('tuleap-openidconnectclient', 'The new provider %1$s have been successfully created.'),
                $provider->getName()
            )
        );
        $GLOBALS['Response']->redirect(OPENIDCONNECTCLIENT_BASE_URL . '/admin');
    }

    /**
     * @throws ProviderMalformedDataException
     */
    public function createAzureADProvider(CSRFSynchronizerToken $csrf_token, \Tuleap\HTTPRequest $request): void
    {
        $csrf_token->check();

        try {
            $name                    = $request->get('name');
            $client_id               = $request->get('client_id');
            $client_secret           = $request->get('client_secret');
            $icon                    = $request->get('icon');
            $color                   = $request->get('color');
            $tenant_id               = $request->get('tenant_id');
            $tenant_setup_identifier = (string) $request->get('tenant_setup_identifier');

            $provider = $this->azure_provider_manager->createAzureADProvider(
                $name,
                $client_id,
                $client_secret,
                $icon,
                $color,
                $tenant_id,
                $tenant_setup_identifier
            );
        } catch (ProviderMalformedDataException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(
                dgettext('tuleap-openidconnectclient', 'The new provider %1$s have been successfully created.'),
                $provider->getName()
            )
        );
        $GLOBALS['Response']->redirect(OPENIDCONNECTCLIENT_BASE_URL . '/admin');
    }

    public function updateGenericProvider(CSRFSynchronizerToken $csrf_token, \Tuleap\HTTPRequest $request)
    {
        $csrf_token->check();

        $id                                = $request->get('id');
        $is_unique_authentication_endpoint = $request->existAndNonEmpty('unique_authentication_endpoint');
        try {
            $provider = $this->provider_manager->getById($id);
        } catch (ProviderNotFoundException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        if (
            $is_unique_authentication_endpoint &&
            ! $this->enable_unique_authentication_endpoint_verifier->canBeEnabledBy(
                $provider,
                $request->getCurrentUser()
            )
        ) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        $name                   = $request->get('name');
        $authorization_endpoint = $request->get('authorization_endpoint');
        $token_endpoint         = $request->get('token_endpoint');
        $jwks_endpoint          = $request->get('jwks_endpoint') ?: '';
        $userinfo_endpoint      = $request->get('userinfo_endpoint') ?: '';
        $client_id              = $request->get('client_id');
        $client_secret          = $request->get('client_secret');
        $icon                   = $request->get('icon');
        $color                  = $request->get('color');

        $updated_provider = new GenericProvider(
            $id,
            $name,
            $authorization_endpoint,
            $token_endpoint,
            $jwks_endpoint,
            $userinfo_endpoint,
            $client_id,
            $client_secret,
            $is_unique_authentication_endpoint,
            $icon,
            $color
        );

        try {
            $this->generic_provider_manager->updateGenericProvider($updated_provider);
        } catch (ProviderMalformedDataException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(dgettext('tuleap-openidconnectclient', 'The provider %1$s have been successfully updated.'), $updated_provider->getName())
        );
        $this->showAdministration($csrf_token, $request->getCurrentUser());
    }

    public function updateAzureProvider(CSRFSynchronizerToken $csrf_token, \Tuleap\HTTPRequest $request)
    {
        $csrf_token->check();

        $id                                = $request->get('id');
        $is_unique_authentication_endpoint = $request->existAndNonEmpty('unique_authentication_endpoint');
        try {
            $provider = $this->provider_manager->getById($id);
        } catch (ProviderNotFoundException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        if (
            $is_unique_authentication_endpoint &&
            ! $this->enable_unique_authentication_endpoint_verifier->canBeEnabledBy(
                $provider,
                $request->getCurrentUser()
            )
        ) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        $name          = $request->get('name');
        $client_id     = $request->get('client_id');
        $client_secret = $request->get('client_secret');
        $icon          = $request->get('icon');
        $color         = $request->get('color');
        $tenant_id     = $request->get('tenant_id');

        try {
            $tenant_setup = AzureADTenantSetup::fromIdentifier((string) $request->get('tenant_setup_identifier'));
        } catch (UnknownAcceptableTenantForAuthenticationIdentifierException $exception) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        $acceptable_tenant_for_authentication = AcceptableTenantForAuthenticationConfiguration::fromTenantSetupAndTenantID(
            $tenant_setup,
            $tenant_id
        );

        $updated_provider = new AzureADProvider(
            $id,
            $name,
            $client_id,
            $client_secret,
            $is_unique_authentication_endpoint,
            $icon,
            $color,
            $tenant_id,
            $acceptable_tenant_for_authentication
        );

        try {
            $this->azure_provider_manager->updateAzureADProvider($updated_provider);
        } catch (ProviderMalformedDataException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(dgettext('tuleap-openidconnectclient', 'The provider %1$s have been successfully updated.'), $updated_provider->getName())
        );
        $this->showAdministration($csrf_token, $request->getCurrentUser());
    }

    public function removeProvider(CSRFSynchronizerToken $csrf_token, $provider_id, PFUser $user)
    {
        $csrf_token->check();

        try {
            $provider = $this->provider_manager->getById($provider_id);
        } catch (ProviderNotFoundException $ex) {
            $this->redirectAfterFailure(
                dgettext('tuleap-openidconnectclient', 'The data you provided are not valid.')
            );
        }
        $this->provider_manager->remove($provider);
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(dgettext('tuleap-openidconnectclient', 'The provider %1$s have been removed.'), $provider->getName())
        );
        $this->showAdministration($csrf_token, $user);
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
        $GLOBALS['Response']->redirect(OPENIDCONNECTCLIENT_BASE_URL . '/admin/');
        exit();
    }
}
