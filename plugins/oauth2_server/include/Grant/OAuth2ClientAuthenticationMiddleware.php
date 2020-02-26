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

namespace Tuleap\OAuth2Server\Grant;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\OAuth2Server\App\ClientIdentifier;
use Tuleap\OAuth2Server\App\InvalidClientIdentifierKey;
use Tuleap\OAuth2Server\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2Server\OAuth2ServerException;

/**
 * @see https://tools.ietf.org/html/rfc6749#section-2.3.1
 */
final class OAuth2ClientAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $client_secret_unserializer;
    /**
     * @var OAuth2AppCredentialVerifier
     */
    private $app_credential_verifier;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var BasicAuthLoginExtractor
     */
    private $basic_auth_login_extractor;

    public function __construct(
        SplitTokenIdentifierTranslator $client_secret_unserializer,
        OAuth2AppCredentialVerifier $app_credential_verifier,
        BasicAuthLoginExtractor $basic_auth_login_extractor,
        LoggerInterface $logger
    ) {
        $this->client_secret_unserializer = $client_secret_unserializer;
        $this->app_credential_verifier    = $app_credential_verifier;
        $this->basic_auth_login_extractor = $basic_auth_login_extractor;
        $this->logger                     = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $credentials_set = $this->basic_auth_login_extractor->extract($request);
        if ($credentials_set === null) {
            $this->logger->debug('No valid authorization header found for basic auth');
            return $handler->handle($request);
        }

        try {
            $client_id = ClientIdentifier::fromClientId($credentials_set->getUsername());
        } catch (InvalidClientIdentifierKey $exception) {
            $this->logger->debug(
                'Username found in the authorization header is not a valid client identifier',
                ['exception' => $exception]
            );
            return $handler->handle($request);
        }

        try {
            $client_secret = $this->client_secret_unserializer->getSplitToken($credentials_set->getPassword());
        } catch (SplitTokenException $exception) {
            $this->logger->debug(
                'Secret found in the authorization cannot be a valid client secret',
                ['exception' => $exception]
            );
            return $handler->handle($request);
        }

        try {
            $app = $this->app_credential_verifier->getApp($client_id, $client_secret);
        } catch (LogicException $exception) {
            throw $exception;
        } catch (OAuth2ServerException $exception) {
            $this->logger->debug('Could not authenticate app', ['exception' => $exception]);
            return $handler->handle($request);
        }

        return $handler->handle($request->withAttribute(self::class, $app));
    }
}
