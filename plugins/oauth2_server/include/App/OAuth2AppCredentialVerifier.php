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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\App;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class OAuth2AppCredentialVerifier
{
    /**
     * @var AppFactory
     */
    private $app_factory;
    /**
     * @var AppDao
     */
    private $app_dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;

    public function __construct(AppFactory $app_factory, AppDao $app_dao, SplitTokenVerificationStringHasher $hasher)
    {
        $this->app_factory = $app_factory;
        $this->app_dao     = $app_dao;
        $this->hasher      = $hasher;
    }

    /**
     * @throws OAuth2AppNotFoundException
     * @throws OAuth2ClientIdentifierAndSecretMismatchException
     * @throws InvalidOAuth2AppSecretException
     */
    public function getApp(ClientIdentifier $client_identifier, SplitToken $client_secret): OAuth2App
    {
        $app = $this->app_factory->getAppMatchingClientId($client_identifier);

        if ($app->getId() !== $client_secret->getID()) {
            throw new OAuth2ClientIdentifierAndSecretMismatchException($client_identifier);
        }

        $client_verification_expected_string = $this->app_dao->searchClientSecretByClientID($app->getId());
        if ($client_verification_expected_string === null) {
            throw new OAuth2MissingVerifierStringException($app);
        }

        $is_valid_client_secret = $this->hasher->verifyHash($client_secret->getVerificationString(), $client_verification_expected_string);
        if (! $is_valid_client_secret) {
            throw new InvalidOAuth2AppSecretException($app);
        }

        return $app;
    }
}
