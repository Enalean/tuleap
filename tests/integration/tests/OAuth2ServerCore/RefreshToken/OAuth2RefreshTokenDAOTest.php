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

namespace Tuleap\OAuth2ServerCore\RefreshToken;

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\NewOAuth2App;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class OAuth2RefreshTokenDAOTest extends TestIntegrationTestCase
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
     * @var OAuth2RefreshTokenDAO
     */
    private $dao;

    public static function setUpBeforeClass(): void
    {
        $db                                 = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$active_project_id            = (int) $db->insertReturnId(
            'groups',
            ['group_name' => 'access_token_dao_active_test', 'status' => \Project::STATUS_ACTIVE, 'unix_group_name' => "access_token_dao_active_test"]
        );
        self::$deleted_project_id           = (int) $db->insertReturnId(
            'groups',
            ['group_name' => 'access_token_dao_deleted_test', 'status' => \Project::STATUS_DELETED, 'unix_group_name' => "access_token_dao_deleted_test"]
        );
        $app_dao                            = new AppDao();
        self::$active_project_app_id        = $app_dao->create(
            NewOAuth2App::fromProjectAdministrationAppData(
                'Name',
                'https://example.com',
                true,
                new \Project(['group_id' => self::$active_project_id, 'unix_group_name' => "auth_refresh_dao_active_test"]),
                new SplitTokenVerificationStringHasher(),
                'plugin_oauth2'
            )
        );
        self::$deleted_project_app_id       = $app_dao->create(
            NewOAuth2App::fromProjectAdministrationAppData(
                'Name',
                'https://example.com',
                true,
                new \Project(['group_id' => self::$deleted_project_id, 'unix_group_name' => "auth_refresh_dao_deleted_test"]),
                new SplitTokenVerificationStringHasher(),
                'plugin_oauth2'
            )
        );
        $auth_code_dao                      = new OAuth2AuthorizationCodeDAO();
        self::$active_project_auth_code_id  = $auth_code_dao->create(
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
        $this->dao = new OAuth2RefreshTokenDAO();
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

    public function testCanFindARefreshTokenByApp(): void
    {
        $refresh_token_id = $this->dao->create(self::$active_project_auth_code_id, 'hashed_verification_string', 30);

        $row = $this->dao->searchRefreshTokenByApp($refresh_token_id, self::$active_project_app_id);

        $this->assertEquals(
            ['authorization_code_id' => self::$active_project_auth_code_id, 'verifier' => 'hashed_verification_string'],
            $row
        );
    }

    public function testRefreshTokenInADeletedProjectCannotBeFoundByApp(): void
    {
        $refresh_token_id = $this->dao->create(self::$deleted_project_auth_code_id, 'hashed_verification_string', 30);

        $this->assertNull($this->dao->searchRefreshTokenByApp($refresh_token_id, self::$deleted_project_app_id));
    }
}
