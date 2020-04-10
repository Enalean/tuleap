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

namespace Tuleap\OAuth2Server\AccessToken;

use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\NewOAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;

final class OAuth2AccessTokenDAOTest extends TestCase
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
     * @var int
     */
    private static $active_project_auth_code_id;
    /**
     * @var int
     */
    private static $deleted_project_auth_code_id;
    /**
     * @var OAuth2AccessTokenDAO
     */
    private $dao;

    public static function setUpBeforeClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$active_project_id = (int) $db->insertReturnId(
            'groups',
            ['group_name' => 'access_token_dao_active_test', 'status' => Project::STATUS_ACTIVE]
        );
        self::$deleted_project_id = (int) $db->insertReturnId(
            'groups',
            ['group_name' => 'access_token_dao_deleted_test', 'status' => Project::STATUS_DELETED]
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
        $auth_code_dao = new OAuth2AuthorizationCodeDAO();
        self::$active_project_auth_code_id = $auth_code_dao->create(
            self::$active_project_app_id,
            102,
            'hashed_verification_string',
            20,
            'pkce_code_chall',
            'oidc_nonce'
        );
        self::$deleted_project_auth_code_id = $auth_code_dao->create(
            self::$deleted_project_id,
            102,
            'hashed_verification_string',
            20,
            'pkce_code_chall',
            'oidc_nonce'
        );
    }

    protected function setUp(): void
    {
        $this->dao = new OAuth2AccessTokenDAO();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_oauth2_access_token');
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->delete('groups', ['group_id' => self::$active_project_id]);
        $db->delete('groups', ['group_id' => self::$deleted_project_id]);
        $app_dao = new AppDao();
        $app_dao->delete(self::$active_project_app_id);
        $app_dao->delete(self::$deleted_project_app_id);
        $auth_code_dao = new OAuth2AuthorizationCodeDAO();
        $auth_code_dao->deleteAuthorizationCodeByID(self::$active_project_auth_code_id);
        $auth_code_dao->deleteAuthorizationCodeByID(self::$deleted_project_auth_code_id);
    }

    public function testCanFindAnAccessTokenInAnActiveProject(): void
    {
        $access_token_id = $this->dao->create(self::$active_project_auth_code_id, 'hashed_verification_string', 30);

        $row = $this->dao->searchAccessToken($access_token_id);

        $this->assertEquals(['user_id' => 102, 'verifier' => 'hashed_verification_string', 'expiration_date' => 30], $row);
    }

    public function testAccessTokenInADeletedProjectCannotBeFound(): void
    {
        $access_token_id = $this->dao->create(self::$deleted_project_auth_code_id, 'hashed_verification_string', 30);

        $this->assertNull($this->dao->searchAccessToken($access_token_id));
    }

    public function testCanFindAnAccessTokenByApp(): void
    {
        $access_token_id = $this->dao->create(self::$active_project_auth_code_id, 'hashed_verification_string', 30);

        $row = $this->dao->searchAccessTokenByApp($access_token_id, self::$active_project_app_id);

        $this->assertEquals(['authorization_code_id' => self::$active_project_auth_code_id, 'verifier' => 'hashed_verification_string'], $row);
    }

    public function testAccessTokenInADeletedProjectCannotBeFoundByApp(): void
    {
        $access_token_id = $this->dao->create(self::$deleted_project_auth_code_id, 'hashed_verification_string', 30);

        $this->assertNull($this->dao->searchAccessTokenByApp($access_token_id, self::$deleted_project_app_id));
    }

    public function testRemovesExpiredAccessTokens(): void
    {
        $current_time    = 60;
        $expired_access_token_id = $this->dao->create(self::$active_project_auth_code_id, 'hashed_verification_string', 30);
        $active_access_token_id  = $this->dao->create(self::$active_project_auth_code_id, 'hashed_verification_string', 120);

        $this->dao->deleteByExpirationDate($current_time);

        $this->assertNull($this->dao->searchAccessToken($expired_access_token_id));
        $this->assertNotNull($this->dao->searchAccessToken($active_access_token_id));
    }
}
