<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\User\SSHKey;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Request\ForbiddenException;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnChallengeDaoStub;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\WebAuthn\Authentication\WebAuthnAuthentication;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;

final class SSHKeyCreateControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \CSRFSynchronizerToken&MockObject $csrf_token;
    private \UserManager&MockObject $user_manager;
    private WebAuthnCredentialSourceDaoStub $source_dao;
    private SSHKeyCreateController $controller;

    protected function setUp(): void
    {
        $this->csrf_token              = $this->createMock(\CSRFSynchronizerToken::class);
        $this->user_manager            = $this->createMock(\UserManager::class);
        $this->source_dao              = WebAuthnCredentialSourceDaoStub::withoutCredentialSources();
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $this->controller = new SSHKeyCreateController(
            $this->csrf_token,
            $this->user_manager,
            new WebAuthnAuthentication(
                $this->source_dao,
                new WebAuthnChallengeDaoStub(),
                new PublicKeyCredentialRpEntity(
                    \ForgeConfig::get(ConfigurationVariables::NAME),
                    ServerHostname::rawHostname()
                ),
                new PublicKeyCredentialLoader(
                    new AttestationObjectLoader($attestation_statement_manager)
                ),
                new AuthenticatorAssertionResponseValidator(
                    $this->source_dao,
                    null,
                    new ExtensionOutputCheckerHandler(),
                    Manager::create()
                        ->add(
                            Ed25519::create(),
                            RS256::create(),
                            ES256::create()
                        )
                ),
            ),
        );
    }

    public function testItForbidsAnonymousUsers(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesUserSSHKey(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->csrf_token->expects(self::once())->method('check')->with('/account/keys-tokens');

        $this->user_manager->expects(self::once())->method('addSSHKeys')->with($user, 'ssh-rsa blabla');

        $this->expectExceptionObject(new LayoutInspectorRedirection('/account/keys-tokens'));
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)
                ->withParam('ssh-key', 'ssh-rsa blabla')
                ->withParam('webauthn_result', '{}')->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
