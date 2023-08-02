<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore;

use DateInterval;
use EventManager;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use ProjectManager;
use Psr\Log\LoggerInterface;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\OAuth2ServerCore\AccessToken\Scope\OAuth2AccessTokenScopeDAO;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2ServerCore\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantController;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2ServerCore\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeVerifier;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2GrantAccessTokenFromAuthorizationCode;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PKCE\PKCECodeVerifier;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2ServerCore\Grant\OAuth2ClientAuthenticationMiddleware;
use Tuleap\OAuth2ServerCore\Grant\RefreshToken\OAuth2GrantAccessTokenFromRefreshToken;
use Tuleap\OAuth2ServerCore\Grant\TokenRevocationController;
use Tuleap\OAuth2ServerCore\OpenIDConnect\IDToken\OpenIDConnectIDTokenCreator;
use Tuleap\OAuth2ServerCore\OpenIDConnect\JWK\JWKSDocumentEndpointController;
use Tuleap\OAuth2ServerCore\OpenIDConnect\JWTBuilderFactory;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectSigningKeyDAO;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectSigningKeyFactoryDBPersistent;
use Tuleap\OAuth2ServerCore\OpenIDConnect\OpenIDConnectTokenBuilder;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenCreator;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenDAO;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenVerifier;
use Tuleap\OAuth2ServerCore\RefreshToken\PrefixOAuth2RefreshToken;
use Tuleap\OAuth2ServerCore\RefreshToken\Scope\OAuth2RefreshTokenScopeDAO;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeRetriever;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\User\OAuth2\AccessToken\PrefixOAuth2AccessToken;
use Tuleap\User\OAuth2\BearerTokenHeaderParser;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;
use User_LoginManager;
use User_PasswordExpirationChecker;
use UserManager;

final class OAuth2ServerRoutes
{
    private const INSTRUMENTATION_NAME        = 'oauth2_server_core';
    public const ID_TOKEN_EXPIRATION_DELAY    = 'PT2M';
    public const SIGNING_KEY_EXPIRATION_DELAY = 'PT1H';

    private function __construct()
    {
    }

    public static function routeOAuth2UserInfoEndpoint(): \Tuleap\OAuth2ServerCore\User\UserInfoController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        $password_handler = \PasswordHandlerFactory::getPasswordHandler();
        $event_manager    = EventManager::instance();
        $user_manager     = UserManager::instance();

        return new \Tuleap\OAuth2ServerCore\User\UserInfoController(
            new JSONResponseBuilder($response_factory, $stream_factory),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::INSTRUMENTATION_NAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new \Tuleap\User\OAuth2\ResourceServer\OAuth2ResourceServerMiddleware(
                $response_factory,
                new BearerTokenHeaderParser(),
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                new OAuth2AccessTokenVerifier(
                    new OAuth2AccessTokenDAO(),
                    new OAuth2ScopeRetriever(
                        new OAuth2AccessTokenScopeDAO(),
                        AggregateAuthenticationScopeBuilder::fromBuildersList(
                            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
                            AggregateAuthenticationScopeBuilder::fromEventDispatcher($event_manager, new OAuth2ScopeBuilderCollector())
                        )
                    ),
                    $user_manager,
                    new SplitTokenVerificationStringHasher()
                ),
                OAuth2SignInScope::fromItself(),
                new User_LoginManager(
                    $event_manager,
                    $user_manager,
                    $user_manager,
                    new \Tuleap\User\PasswordVerifier($password_handler),
                    new User_PasswordExpirationChecker(),
                    $password_handler
                )
            )
        );
    }

    public static function routeJWKSDocument(): JWKSDocumentEndpointController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        return new JWKSDocumentEndpointController(
            new OpenIDConnectSigningKeyFactoryDBPersistent(
                new KeyFactory(),
                new OpenIDConnectSigningKeyDAO(),
                new DateInterval(self::SIGNING_KEY_EXPIRATION_DELAY),
                new DateInterval(self::ID_TOKEN_EXPIRATION_DELAY),
            ),
            new DateInterval(self::ID_TOKEN_EXPIRATION_DELAY),
            new JSONResponseBuilder($response_factory, $stream_factory),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::INSTRUMENTATION_NAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
        );
    }

    public static function routeAccessTokenCreation(): AccessTokenGrantController
    {
        $response_factory                          = HTTPFactoryBuilder::responseFactory();
        $stream_factory                            = HTTPFactoryBuilder::streamFactory();
        $app_dao                                   = new AppDao();
        $access_token_grant_error_response_builder = new AccessTokenGrantErrorResponseBuilder(
            $response_factory,
            $stream_factory
        );
        $scope_builder                             = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(
                \EventManager::instance(),
                new OAuth2ScopeBuilderCollector()
            )
        );
        $logger                                    = self::getOAuth2ServerLogger();
        $access_token_grant_representation_builder = new AccessTokenGrantRepresentationBuilder(
            new OAuth2AccessTokenCreator(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                new SplitTokenVerificationStringHasher(),
                new OAuth2AccessTokenDAO(),
                new OAuth2ScopeSaver(new OAuth2AccessTokenScopeDAO()),
                new DateInterval('PT1H'),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new OAuth2RefreshTokenCreator(
                OAuth2OfflineAccessScope::fromItself(),
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                new SplitTokenVerificationStringHasher(),
                new OAuth2RefreshTokenDAO(),
                new OAuth2ScopeSaver(new OAuth2RefreshTokenScopeDAO()),
                new DateInterval('PT6H'),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new OpenIDConnectIDTokenCreator(
                OAuth2SignInScope::fromItself(),
                new OpenIDConnectTokenBuilder(
                    new JWTBuilderFactory(),
                    new OpenIDConnectSigningKeyFactoryDBPersistent(
                        new KeyFactory(),
                        new OpenIDConnectSigningKeyDAO(),
                        new DateInterval(self::SIGNING_KEY_EXPIRATION_DELAY),
                        new DateInterval(self::ID_TOKEN_EXPIRATION_DELAY),
                    ),
                    new DateInterval(self::ID_TOKEN_EXPIRATION_DELAY),
                    new Sha256(),
                ),
                UserManager::instance()
            )
        );
        return new AccessTokenGrantController(
            $access_token_grant_error_response_builder,
            new OAuth2GrantAccessTokenFromAuthorizationCode(
                $response_factory,
                $stream_factory,
                $access_token_grant_error_response_builder,
                $access_token_grant_representation_builder,
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
                new OAuth2AuthorizationCodeVerifier(
                    new SplitTokenVerificationStringHasher(),
                    UserManager::instance(),
                    new OAuth2AuthorizationCodeDAO(),
                    new OAuth2ScopeRetriever(new OAuth2AuthorizationCodeScopeDAO(), $scope_builder),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                new PKCECodeVerifier(),
                $logger
            ),
            new OAuth2GrantAccessTokenFromRefreshToken(
                $response_factory,
                $stream_factory,
                $access_token_grant_error_response_builder,
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                new OAuth2RefreshTokenVerifier(
                    new SplitTokenVerificationStringHasher(),
                    new OAuth2RefreshTokenDAO(),
                    new OAuth2ScopeRetriever(new OAuth2RefreshTokenScopeDAO(), $scope_builder),
                    new OAuth2AuthorizationCodeRevoker(new OAuth2AuthorizationCodeDAO()),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                $access_token_grant_representation_builder,
                new ScopeExtractor($scope_builder),
                $logger
            ),
            $logger,
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::INSTRUMENTATION_NAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware(),
            new OAuth2ClientAuthenticationMiddleware(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                new OAuth2AppCredentialVerifier(
                    new \Tuleap\OAuth2ServerCore\App\AppFactory($app_dao, ProjectManager::instance()),
                    $app_dao,
                    new SplitTokenVerificationStringHasher()
                ),
                new BasicAuthLoginExtractor(),
                $logger
            )
        );
    }

    public static function routeTokenRevocation(): TokenRevocationController
    {
        $response_factory           = HTTPFactoryBuilder::responseFactory();
        $stream_factory             = HTTPFactoryBuilder::streamFactory();
        $app_dao                    = new AppDao();
        $authorization_code_revoker = new OAuth2AuthorizationCodeRevoker(
            new OAuth2AuthorizationCodeDAO()
        );
        $split_token_hasher         = new SplitTokenVerificationStringHasher();
        return new \Tuleap\OAuth2ServerCore\Grant\TokenRevocationController(
            $response_factory,
            $stream_factory,
            new \Tuleap\OAuth2ServerCore\RefreshToken\OAuth2RefreshTokenRevoker(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                $authorization_code_revoker,
                new OAuth2RefreshTokenDAO(),
                $split_token_hasher
            ),
            new \Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenRevoker(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                $authorization_code_revoker,
                new OAuth2AccessTokenDAO(),
                $split_token_hasher
            ),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::INSTRUMENTATION_NAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware(),
            new OAuth2ClientAuthenticationMiddleware(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                new OAuth2AppCredentialVerifier(
                    new \Tuleap\OAuth2ServerCore\App\AppFactory($app_dao, ProjectManager::instance()),
                    $app_dao,
                    new SplitTokenVerificationStringHasher()
                ),
                new BasicAuthLoginExtractor(),
                self::getOAuth2ServerLogger()
            )
        );
    }

    public static function getOAuth2ServerLogger(): LoggerInterface
    {
        return \BackendLogger::getDefaultLogger('oauth2_server.log');
    }
}
