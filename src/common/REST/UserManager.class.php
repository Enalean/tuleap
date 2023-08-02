<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

use Luracast\Restler\Data\ApiMethodInfo;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\OAuth2ServerCore\AccessToken\Scope\OAuth2AccessTokenScopeDAO;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeRetriever;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyVerifier;
use Tuleap\User\AccessKey\PrefixAccessKey;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\User\AccessKey\Scope\CoreAccessKeyScopeBuilderFactory;
use Tuleap\User\AccessKey\Scope\RESTAccessKeyScope;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminUserBuilder;
use Tuleap\User\OAuth2\AccessToken\PrefixOAuth2AccessToken;
use Tuleap\User\OAuth2\BearerTokenHeaderParser;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeExtractorRESTEndpoint;
use Tuleap\User\PasswordVerifier;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use User_LoginManager;
use User_PasswordExpirationChecker;
use EventManager;
use Tuleap\REST\Exceptions\NoAuthenticationHeadersException;
use Rest_TokenDao;
use Rest_TokenManager;
use Rest_TokenFactory;
use Rest_Token;
use PasswordHandlerFactory;

class UserManager
{
    /** @var \UserManager */
    private $user_manager;

    /** @var User_LoginManager */
    private $login_manager;

    /**
     * @var AccessKeyHeaderExtractor
     */
    private $access_key_header_extractor;

    /**
     * @var AccessKeyVerifier
     */
    private $access_key_verifier;

    public const HTTP_TOKEN_HEADER     = 'X-Auth-Token';
    public const PHP_HTTP_TOKEN_HEADER = 'HTTP_X_AUTH_TOKEN';

    public const HTTP_USER_HEADER     = 'X-Auth-UserId';
    public const PHP_HTTP_USER_HEADER = 'HTTP_X_AUTH_USERID';

    public const HTTP_ACCESS_KEY_HEADER = 'X-Auth-AccessKey';

    /**
     * @var BearerTokenHeaderParser
     */
    private $bearer_token_header_parser;
    /**
     * @var RestReadOnlyAdminUserBuilder
     */
    private $read_only_admin_user_builder;
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_token_identifier_unserializer;
    /**
     * @var OAuth2ScopeExtractorRESTEndpoint
     */
    private $oauth2_scope_extractor_endpoint;

    public function __construct(
        \UserManager $user_manager,
        User_LoginManager $login_manager,
        AccessKeyHeaderExtractor $access_key_header_extractor,
        AccessKeyVerifier $access_key_verifier,
        BearerTokenHeaderParser $bearer_token_header_parser,
        SplitTokenIdentifierTranslator $access_token_identifier_unserializer,
        OAuth2ScopeExtractorRESTEndpoint $oauth2_scope_extractor_endpoint,
        private OAuth2AccessTokenVerifier $oauth2_access_token_verifier,
        RestReadOnlyAdminUserBuilder $read_only_admin_user_builder,
    ) {
        $this->user_manager                         = $user_manager;
        $this->login_manager                        = $login_manager;
        $this->access_key_header_extractor          = $access_key_header_extractor;
        $this->access_key_verifier                  = $access_key_verifier;
        $this->bearer_token_header_parser           = $bearer_token_header_parser;
        $this->access_token_identifier_unserializer = $access_token_identifier_unserializer;
        $this->oauth2_scope_extractor_endpoint      = $oauth2_scope_extractor_endpoint;
        $this->read_only_admin_user_builder         = $read_only_admin_user_builder;
    }

    public static function build(): self
    {
        $event_manager    = EventManager::instance();
        $user_manager     = \UserManager::instance();
        $password_handler = PasswordHandlerFactory::getPasswordHandler();

        $oauth2_scope_builder = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new OAuth2ScopeBuilderCollector())
        );

        return new self(
            $user_manager,
            new User_LoginManager(
                $event_manager,
                $user_manager,
                $user_manager,
                new PasswordVerifier($password_handler),
                new User_PasswordExpirationChecker(),
                $password_handler
            ),
            new AccessKeyHeaderExtractor(new PrefixedSplitTokenSerializer(new PrefixAccessKey()), $_SERVER),
            new AccessKeyVerifier(
                new AccessKeyDAO(),
                new SplitTokenVerificationStringHasher(),
                $user_manager,
                new AccessKeyScopeRetriever(
                    new AccessKeyScopeDAO(),
                    CoreAccessKeyScopeBuilderFactory::buildCoreAccessKeyScopeBuilder()
                )
            ),
            new BearerTokenHeaderParser(),
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
            new OAuth2ScopeExtractorRESTEndpoint($oauth2_scope_builder),
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
            new RestReadOnlyAdminUserBuilder(
                new User_ForgeUserGroupPermissionsManager(
                    new User_ForgeUserGroupPermissionsDao()
                )
            )
        );
    }

    /**
     * Return user of current request in REST context
     *
     * Tries to get authentication scheme from cookie if any, fallback on token
     * or access key authentication or OAuth2 access token
     *
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusSuspendedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_PasswordExpiredException
     */
    public function getCurrentUser(?ApiMethodInfo $api_method_info): \PFUser
    {
        $user = $this->getUserFromCookie();
        if (! $user->isAnonymous()) {
            $user = $this->read_only_admin_user_builder->buildReadOnlyAdminUser($user);
            $this->user_manager->setCurrentUser(CurrentUserWithLoggedInInformation::fromLoggedInUser($user));
            return $user;
        }
        try {
            $user = $this->getUserFromTuleapRESTAuthenticationFlows($api_method_info);
        } catch (NoAuthenticationHeadersException $exception) {
            return $this->user_manager->getUserAnonymous();
        }
        if ($user === null) {
            return $this->user_manager->getUserAnonymous();
        } else {
            $user = $this->read_only_admin_user_builder->buildReadOnlyAdminUser($user);
        }
        $this->login_manager->validateAndSetCurrentUser($user);
        return $user;
    }

    /**
     * We need it to browse the API as we are logged in through the Web UI
     * @throws \User_PasswordExpiredException
     */
    private function getUserFromCookie()
    {
        $current_user = $this->user_manager->getCurrentUser();
        if (! $current_user->isAnonymous()) {
            $password_expiration_checker = new User_PasswordExpirationChecker();
            $password_expiration_checker->checkPasswordLifetime($current_user);
        }
        return $current_user;
    }

    /**
     * @throws NoAuthenticationHeadersException
     * @throws \Rest_Exception_InvalidTokenException
     */
    private function getUserFromTuleapRESTAuthenticationFlows(?ApiMethodInfo $api_method_info): ?\PFUser
    {
        if ($this->isTryingToUseAccessKeyAuthentication()) {
            return $this->getUserFromAccessKey();
        }
        if ($this->isTryingToUseOAuth2AccessToken($api_method_info)) {
            return $this->getUserFromOAuth2AccessToken($api_method_info);
        }
        if ($this->isTryingToUseTokenAuthentication()) {
            return $this->getUserFromToken();
        }
        return null;
    }

    private function isTryingToUseAccessKeyAuthentication(): bool
    {
        return $this->access_key_header_extractor->isAccessKeyHeaderPresent();
    }

    /**
     * @return bool
     */
    private function isTryingToUseTokenAuthentication()
    {
        return isset($_SERVER[self::PHP_HTTP_TOKEN_HEADER]);
    }

    private function getUserFromAccessKey(): \PFUser
    {
        $access_key = $this->access_key_header_extractor->extractAccessKey();
        if ($access_key === null) {
            throw new NoAuthenticationHeadersException(self::HTTP_ACCESS_KEY_HEADER);
        }

        $request = \HTTPRequest::instance();
        return $this->access_key_verifier->getUser($access_key, RESTAccessKeyScope::fromItself(), $request->getIPAddress());
    }

    /**
     * @psalm-assert-if-true !null $api_method_info
     */
    private function isTryingToUseOAuth2AccessToken(?ApiMethodInfo $api_method_info): bool
    {
        return $api_method_info !== null &&
            $this->bearer_token_header_parser->doesHeaderLineContainsBearerTokenInformation($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    }

    private function getUserFromOAuth2AccessToken(ApiMethodInfo $api_method_info): \PFUser
    {
        $authorization_header_line = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        $access_token = $this->bearer_token_header_parser->parseHeaderLine($authorization_header_line);

        if ($access_token === null) {
            throw new NoAuthenticationHeadersException('Authorization');
        }

        $required_scope = $this->oauth2_scope_extractor_endpoint->extractRequiredScope($api_method_info);

        $granted_authorization = $this->oauth2_access_token_verifier->getGrantedAuthorization(
            $this->access_token_identifier_unserializer->getSplitToken($access_token),
            $required_scope
        );

        return $granted_authorization->getUser();
    }

    /**
     * @return \PFUser
     * @throws NoAuthenticationHeadersException
     * @throws \Rest_Exception_InvalidTokenException
     */
    private function getUserFromToken()
    {
        if (! isset($_SERVER[self::PHP_HTTP_TOKEN_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_TOKEN_HEADER);
        }

        if (! isset($_SERVER[self::PHP_HTTP_USER_HEADER])) {
            throw new NoAuthenticationHeadersException(self::HTTP_TOKEN_HEADER);
        }

        $token = new Rest_Token(
            $_SERVER[self::PHP_HTTP_USER_HEADER],
            $_SERVER[self::PHP_HTTP_TOKEN_HEADER]
        );

        $token_dao     = new Rest_TokenDao();
        $token_manager = new Rest_TokenManager(
            $token_dao,
            new Rest_TokenFactory($token_dao),
            $this->user_manager
        );
        return $token_manager->checkToken($token);
    }
}
