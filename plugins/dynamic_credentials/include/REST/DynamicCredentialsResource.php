<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\REST;

use Luracast\Restler\RestException;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DynamicCredentials\Credential\CredentialCreator;
use Tuleap\DynamicCredentials\Credential\CredentialDAO;
use Tuleap\DynamicCredentials\Credential\CredentialIdentifierExtractor;
use Tuleap\DynamicCredentials\Credential\CredentialInvalidUsernameException;
use Tuleap\DynamicCredentials\Credential\CredentialRemover;
use Tuleap\DynamicCredentials\Credential\DuplicateCredentialException;
use Tuleap\DynamicCredentials\Plugin\DynamicCredentialsSettings;
use Tuleap\REST\Header;

class DynamicCredentialsResource
{
    /**
     * @url OPTIONS
     */
    public function options()
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a new set of credential
     *
     * @param string $username {@from body} Username must be formatted as forge__dynamic_credential-&lt;identifier&gt; where &lt;identifier&gt; is a chosen value
     * @param string $password {@from body}
     * @param string $expiration {@from body} Expiration date ISO8601 formatted
     * @param string $signature {@from body} Base64 encoded signature associated with the request
     */
    public function post($username, $password, $expiration, $signature)
    {
        $this->options();
        $request_signature_verifier = $this->getRequestSignatureVerifier();
        if (! $request_signature_verifier->isSignatureValid($signature, $username, $password, $expiration)) {
            throw new RestException(403, 'Invalid signature');
        }

        $account_creator = new CredentialCreator(
            new CredentialDAO(),
            \PasswordHandlerFactory::getPasswordHandler(),
            new CredentialIdentifierExtractor()
        );
        $expiration_date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $expiration);
        if ($expiration_date === false) {
            throw new RestException(400, 'Invalid value specified for `expiration`. Expecting ISO8601 date.');
        }

        try {
            $concealed_password = new ConcealedString($password);
            sodium_memzero($password);
            $account_creator->create($username, $concealed_password, $expiration_date);
        } catch (CredentialInvalidUsernameException $ex) {
            throw new RestException(400, $ex->getMessage());
        } catch (DuplicateCredentialException $ex) {
            throw new RestException(400, $ex->getMessage());
        }
    }

    /**
     * @url OPTIONS {username}
     */
    public function optionsUsername($username)
    {
        Header::allowOptionsDelete();
    }

    /**
     * Revoke a set of credential
     *
     * @url DELETE {username}
     *
     * @param string $username Username must be formatted as forge__dynamic_credential-&lt;identifier&gt; where &lt;identifier&gt; is a chosen value
     * @param string $signature Base64 encoded signature associated with the request
     */
    public function deleteUsername($username, $signature)
    {
        $this->optionsUsername($username);
        $request_signature_verifier = $this->getRequestSignatureVerifier();
        if (! $request_signature_verifier->isSignatureValid($signature, $username)) {
            throw new RestException(403, 'Invalid signature');
        }

        $credential_remover = new CredentialRemover(new CredentialDAO(), new CredentialIdentifierExtractor());

        try {
            $has_credential_been_revoked = $credential_remover->revokeByUsername($username);
        } catch (CredentialInvalidUsernameException $ex) {
            throw new RestException(400, $ex->getMessage());
        }

        if (! $has_credential_been_revoked) {
            throw new RestException(404, 'Credential not found');
        }
    }

    private function getRequestSignatureVerifier(): RequestSignatureVerifier
    {
        $plugin = \PluginFactory::instance()->getPluginByName(\dynamic_credentialsPlugin::NAME);
        assert($plugin instanceof \dynamic_credentialsPlugin);
        $settings = new DynamicCredentialsSettings($plugin->getPluginInfo());
        return new RequestSignatureVerifier($settings->getSignaturePublicKey());
    }
}
