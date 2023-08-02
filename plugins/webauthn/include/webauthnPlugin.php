<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class WebAuthnPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-webauthn', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): Tuleap\WebAuthn\Plugin\PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\WebAuthn\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    private function getTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
    }

    private function getViteAssets(string $application): Tuleap\Layout\IncludeViteAssets
    {
        return new Tuleap\Layout\IncludeViteAssets(
            __DIR__ . "/../scripts/$application/frontend-assets",
            "/assets/webauthn/$application"
        );
    }

    private function getWebAuthnCredentialSourceDao(): Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao
    {
        return new Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao();
    }

    private function getGetUserAuthenticatorsEventHandler(): Tuleap\WebAuthn\GetUserAuthenticatorsEventHandler
    {
        return new Tuleap\WebAuthn\GetUserAuthenticatorsEventHandler(
            $this->getWebAuthnCredentialSourceDao()
        );
    }

    public function getAccountSettings(): Tuleap\Request\DispatchableWithRequest
    {
        return new Tuleap\WebAuthn\Controllers\AccountController(
            $this->getTemplateRenderer(),
            EventManager::instance(),
            $this->getViteAssets('account'),
            $this->getWebAuthnCredentialSourceDao(),
            UserManager::instance()
        );
    }

    public function getLogin(): Tuleap\Request\DispatchableWithRequest
    {
        $source_dao                    = new Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao();
        $attestation_statement_manager = new Webauthn\AttestationStatement\AttestationStatementSupportManager();
        $attestation_statement_manager->add(new Webauthn\AttestationStatement\NoneAttestationStatementSupport());
        $logger            = BackendLogger::getDefaultLogger();
        $credential_loader = new Webauthn\PublicKeyCredentialLoader(
            new Webauthn\AttestationStatement\AttestationObjectLoader($attestation_statement_manager)
        );
        $credential_loader->setLogger($logger);
        $assertion_validator = new Webauthn\AuthenticatorAssertionResponseValidator(
            $source_dao,
            null,
            new Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler(),
            Cose\Algorithm\Manager::create()
                ->add(
                    Cose\Algorithm\Signature\EdDSA\Ed25519::create(),
                    Cose\Algorithm\Signature\RSA\RS256::create(),
                    Cose\Algorithm\Signature\ECDSA\ES256::create()
                )
        );
        $assertion_validator->setLogger($logger);

        return new Tuleap\WebAuthn\Controllers\LoginController(
            $this->getTemplateRenderer(),
            $this->getViteAssets('login'),
            new CSRFSynchronizerToken(Tuleap\WebAuthn\Controllers\LoginController::URL),
            UserManager::instance(),
            new Tuleap\WebAuthn\Authentication\WebAuthnAuthentication(
                $source_dao,
                new Tuleap\WebAuthn\Challenge\WebAuthnChallengeDao(),
                new Webauthn\PublicKeyCredentialRpEntity(
                    ForgeConfig::get(Tuleap\Config\ConfigurationVariables::NAME),
                    Tuleap\ServerHostname::rawHostname()
                ),
                $credential_loader,
                $assertion_validator,
            )
        );
    }

    #[Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/account', $this->getRouteHandler('getAccountSettings'));
            $r->addRoute(['GET', 'POST'], '/login', $this->getRouteHandler('getLogin'));
        });
    }

    #[Tuleap\Plugin\ListeningToEventClass]
    public function accountTabPresenterCollection(Tuleap\User\Account\AccountTabPresenterCollection $collection): void
    {
        $collection->add(
            Tuleap\User\Account\AccountTabSecuritySection::NAME,
            new Tuleap\User\Account\AccountTabPresenter(
                dgettext('tuleap-webauthn', 'Passkeys'),
                $this->getPluginPath() . '/account',
                $collection->getCurrentHref()
            )
        );
    }

    #[Tuleap\Plugin\ListeningToEventClass]
    public function getUserAuthenticatorsEvent(Tuleap\User\Admin\GetUserAuthenticatorsEvent $event): void
    {
        $this->getGetUserAuthenticatorsEventHandler()->handle($event);
    }

    #[Tuleap\Plugin\ListeningToEventClass]
    public function additionalConnectorsCollector(Tuleap\User\AdditionalConnectorsCollector $collector): void
    {
        $collector->addConnector(Tuleap\WebAuthn\PasswordlessConnectorBuilder::build(
            $this->getPluginPath(),
            $collector->return_to
        ));
    }
}
