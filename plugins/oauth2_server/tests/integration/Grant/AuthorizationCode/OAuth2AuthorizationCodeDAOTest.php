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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\OAuth2Server\AccessToken\Scope\OAuth2AccessTokenScopeDAO;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\NewOAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenDAO;
use Tuleap\OAuth2Server\RefreshToken\Scope\OAuth2RefreshTokenScopeDAO;

final class OAuth2AuthorizationCodeDAOTest extends TestCase
{
    /**
     * @var int
     */
    private static $active_project_id;
    /**
     * @var int
     */
    private static $deleted_project_id;
    /**
     * @var int
     */
    private static $active_project_app_id;
    /**
     * @var int
     */
    private static $deleted_project_app_id;
    /**
     * @var OAuth2AuthorizationCodeDAO
     */
    private $dao;

    public static function setUpBeforeClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$active_project_id = (int) $db->insertReturnId(
            'groups',
            ['group_name' => 'auth_code_dao_active_test', 'status' => Project::STATUS_ACTIVE]
        );
        self::$deleted_project_id = (int) $db->insertReturnId(
            'groups',
            ['group_name' => 'auth_code_dao_deleted_test', 'status' => Project::STATUS_DELETED]
        );
        $app_dao = new AppDao();
        self::$active_project_app_id = $app_dao->create(
            NewOAuth2App::fromAppData(
                'Name',
                'https://example.com',
                true,
                new \Project(['group_id' => self::$active_project_id]),
                new SplitTokenVerificationStringHasher()
            )
        );
        self::$deleted_project_app_id = $app_dao->create(
            NewOAuth2App::fromAppData(
                'Name',
                'https://example.com',
                true,
                new \Project(['group_id' => self::$deleted_project_id]),
                new SplitTokenVerificationStringHasher()
            )
        );
    }

    protected function setUp(): void
    {
        $this->dao = new OAuth2AuthorizationCodeDAO();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->delete('plugin_oauth2_authorization_code', []);
        $db->delete('plugin_oauth2_authorization_code_scope', []);
        $db->delete('plugin_oauth2_access_token', []);
        $db->delete('plugin_oauth2_access_token_scope', []);
        $db->delete('plugin_oauth2_refresh_token', []);
        $db->delete('plugin_oauth2_refresh_token_scope', []);
    }

    public static function tearDownAfterClass() : void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->delete('groups', ['group_id' => self::$active_project_id]);
        $db->delete('groups', ['group_id' => self::$deleted_project_id]);
        $app_dao = new AppDao();
        $app_dao->delete(self::$active_project_app_id);
        $app_dao->delete(self::$deleted_project_app_id);
    }

    public function testAnAuthorizationCodeCanBeCreatedAndRemoved(): void
    {
        $user_id              = 102;
        $verification_string  = 'hashed_verification_string';
        $expiration_timestamp = 20;

        $auth_code_id = $this->dao->create(
            self::$active_project_app_id,
            $user_id,
            $verification_string,
            $expiration_timestamp,
            null
        );

        $authorization_code_row = $this->dao->searchAuthorizationCode($auth_code_id);
        $this->assertEquals(
            [
                'user_id'               => $user_id,
                'verifier'              => $verification_string,
                'expiration_date'       => $expiration_timestamp,
                'has_already_been_used' => 0,
                'pkce_code_challenge'   => null
            ],
            $authorization_code_row
        );

        $this->dao->deleteAuthorizationCodeByID($auth_code_id);

        $this->assertNull($this->dao->searchAuthorizationCode($auth_code_id));
    }

    public function testDeletingAnAuthorizationCodeDeletesTheAssociatedTokens(): void
    {
        $auth_code_id = $this->dao->create(self::$active_project_app_id, 102, 'hashed_verification_string_auth', 20, null);

        $auth_code_scope_dao    = new OAuth2AuthorizationCodeScopeDAO();
        $auth_code_scope_dao->saveScopeKeysByID($auth_code_id, 'scope:A', 'scope:B');
        $access_token_dao       = new OAuth2AccessTokenDAO();
        $access_token_id_1      = $access_token_dao->create($auth_code_id, 'hashed_verification_string_access', 30);
        $access_token_id_2      = $access_token_dao->create($auth_code_id, 'hashed_verification_string_access', 30);
        $access_token_scope_dao = new OAuth2AccessTokenScopeDAO();
        $access_token_scope_dao->saveScopeKeysByID($access_token_id_1, 'scope:A', 'scope:B');
        $refresh_token_dao       = new OAuth2RefreshTokenDAO();
        $refresh_token_id        = $refresh_token_dao->create($auth_code_id, 'hashed_verification_string_refresh', 30);
        $refresh_token_scope_dao = new OAuth2RefreshTokenScopeDAO();
        $refresh_token_scope_dao->saveScopeKeysByID($refresh_token_id, 'scope:A', 'scope:B');

        $this->assertNotEmpty($auth_code_scope_dao->searchScopeIdentifiersByOAuth2SplitTokenID($auth_code_id));
        $this->assertNotNull($access_token_dao->searchAccessToken($access_token_id_1));
        $this->assertNotNull($access_token_dao->searchAccessToken($access_token_id_2));
        $this->assertNotEmpty($access_token_scope_dao->searchScopeIdentifiersByOAuth2SplitTokenID($access_token_id_1));
        $this->assertNotNull($refresh_token_dao->searchRefreshTokenByID($refresh_token_id));
        $this->assertNotEmpty($refresh_token_scope_dao->searchScopeIdentifiersByOAuth2SplitTokenID($refresh_token_id));

        $this->dao->deleteAuthorizationCodeByID($auth_code_id);

        $this->assertEmpty($auth_code_scope_dao->searchScopeIdentifiersByOAuth2SplitTokenID($auth_code_id));
        $this->assertNull($this->dao->searchAuthorizationCode($auth_code_id));
        $this->assertNull($access_token_dao->searchAccessToken($access_token_id_1));
        $this->assertNull($access_token_dao->searchAccessToken($access_token_id_2));
        $this->assertEmpty($access_token_scope_dao->searchScopeIdentifiersByOAuth2SplitTokenID($access_token_id_1));
        $this->assertNull($refresh_token_dao->searchRefreshTokenByID($refresh_token_id));
        $this->assertEmpty($refresh_token_scope_dao->searchScopeIdentifiersByOAuth2SplitTokenID($refresh_token_id));
    }

    public function testAnAuthorizationCodeOfDeletedProjectCannotBeFound(): void
    {
        $user_id              = 102;
        $verification_string  = 'hashed_verification_string';
        $expiration_timestamp = 20;

        $auth_code_id = $this->dao->create(
            self::$deleted_project_app_id,
            $user_id,
            $verification_string,
            $expiration_timestamp,
            null
        );

        $this->assertNull($this->dao->searchAuthorizationCode($auth_code_id));
    }

    public function testDeletesExpiredAuthorizationCodeAndTokens(): void
    {
        $auth_code_id = $this->dao->create(self::$active_project_app_id, 102, 'hashed_verification_string_auth', 30, null);
        $access_token_dao  = new OAuth2AccessTokenDAO();
        $access_token_id   = $access_token_dao->create($auth_code_id, 'hashed_verification_string_access', 30);
        $refresh_token_dao = new OAuth2RefreshTokenDAO();
        $refresh_token_id  = $refresh_token_dao->create($auth_code_id, 'hashed_verification_string_refresh', 30);

        $this->dao->deleteAuthorizationCodeByExpirationDate(60);

        $this->assertNull($this->dao->searchAuthorizationCode($auth_code_id));
        $this->assertNull($access_token_dao->searchAccessToken($access_token_id));
        $this->assertNull($refresh_token_dao->searchRefreshTokenByID($refresh_token_id));
    }

    public function testDoesNotDeletedExpiredAuthorizationCodeWhenThereIsStillAnActiveTokenAssociatedToIt(): void
    {
        $auth_code_id = $this->dao->create(self::$active_project_app_id, 102, 'hashed_verification_string_auth', 5, null);
        $access_token_dao  = new OAuth2AccessTokenDAO();
        $access_token_id   = $access_token_dao->create($auth_code_id, 'hashed_verification_string_access', 30);

        $this->dao->deleteAuthorizationCodeByExpirationDate(10);

        $this->assertNotNull($this->dao->searchAuthorizationCode($auth_code_id));
        $this->assertNotNull($access_token_dao->searchAccessToken($access_token_id));
    }

    public function testDeletesAuthorizationCodeOfDeletedProject(): void
    {
        $auth_code_id = $this->dao->create(self::$deleted_project_app_id, 102, 'hashed_verification_string', 357, null);

        $this->dao->deleteAuthorizationCodeInNonExistingOrDeletedProject();

        $this->assertNull(
            DBFactory::getMainTuleapDBConnection()->getDB()->row(
                'SELECT id FROM plugin_oauth2_authorization_code WHERE id = ?',
                $auth_code_id
            )
        );
    }
}
