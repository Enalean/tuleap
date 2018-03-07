<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tuleap\DynamicCredentials\Credential\CredentialCreator;
use Tuleap\DynamicCredentials\Credential\CredentialDAO;
use Tuleap\DynamicCredentials\Credential\CredentialIdentifierExtractor;
use Tuleap\DynamicCredentials\Credential\CredentialInvalidUsernameException;
use Tuleap\DynamicCredentials\Credential\DuplicateCredentialException;
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
     */
    public function post($username, $password, $expiration)
    {
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
            $account_creator->create($username, $password, $expiration_date);
        } catch (CredentialInvalidUsernameException $ex) {
            throw new RestException(400, $ex->getMessage());
        } catch (DuplicateCredentialException $ex) {
            throw new RestException(400, $ex->getMessage());
        }
    }
}
