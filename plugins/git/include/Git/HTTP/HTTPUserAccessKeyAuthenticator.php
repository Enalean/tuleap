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

namespace Tuleap\Git\HTTP;

use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Git\User\AccessKey\Scope\GitRepositoryAccessKeyScope;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\AccessKey\AccessKeyVerifier;

class HTTPUserAccessKeyAuthenticator
{
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_key_identifier_unserializer;
    /**
     * @var AccessKeyVerifier
     */
    private $access_key_verifier;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        SplitTokenIdentifierTranslator $access_key_identifier_unserializer,
        AccessKeyVerifier $access_key_verifier,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->access_key_identifier_unserializer = $access_key_identifier_unserializer;
        $this->access_key_verifier                = $access_key_verifier;
        $this->logger                             = $logger;
    }

    public function getUser(string $login, ConcealedString $potential_access_key_identifier, string $ip_address): ?\PFUser
    {
        try {
            $access_key = $this->access_key_identifier_unserializer->getSplitToken($potential_access_key_identifier);
        } catch (InvalidIdentifierFormatException | IncorrectSizeVerificationStringException $ex) {
            $this->logger->debug('Given password does not look like an access key, skipping');
            return null;
        }

        try {
            $user = $this->access_key_verifier->getUser(
                $access_key,
                GitRepositoryAccessKeyScope::fromItself(),
                $ip_address
            );
        } catch (AccessKeyException $ex) {
            $this->logger->debug(
                sprintf('Access key is not valid (%s), skipping', $ex->getMessage())
            );
            return null;
        }

        if (! \hash_equals($user->getUserName(), $login)) {
            throw new HTTPUserAccessKeyMisusageException($login, $user);
        }

        return $user;
    }
}
