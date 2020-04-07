<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\REST;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Rest_Token;
use Rest_TokenDao;
use Rest_TokenFactory;
use Rest_TokenManager;

final class TokenManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var  Rest_TokenManager */
    private $token_manager;

    /** @var  Rest_TokenDao */
    private $token_dao;

    /** @var  Rest_TokenFactory */
    private $token_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var string
     */
    private $user_id;
    /**
     * @var string
     */
    private $token_value;
    /**
     * @var Rest_Token
     */
    private $token;
    /**
     * @var \PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user_manager  = \Mockery::spy(\UserManager::class);
        $this->token_dao     = \Mockery::spy(\Rest_TokenDao::class);
        $this->token_factory = new Rest_TokenFactory($this->token_dao);
        $this->token_manager = new Rest_TokenManager($this->token_dao, $this->token_factory, $this->user_manager);

        $this->user_id     = 'whatever';
        $this->token_value = 'whateverSha1Token';
        $this->token       = new Rest_Token($this->user_id, $this->token_value);
        $this->user        = new \PFUser(['user_id' => $this->user_id, 'language_id' => 'en']);
    }

    public function testItReturnsTheUserIfTokenIsValid(): void
    {
        $this->user_manager->shouldReceive('getUserById')->with($this->user_id)->andReturns($this->user);

        $this->token_dao->shouldReceive('checkTokenExistenceForUserId')->with($this->user_id, $this->token_value)->andReturns(\TestHelper::arrayToDar(array()));
        $this->assertEquals($this->user, $this->token_manager->checkToken($this->token));
    }

    public function testItThrowsAnExceptionIfTokenIsInvalid(): void
    {
        $this->token_dao->shouldReceive('checkTokenExistenceForUserId')->with($this->user_id, $this->token_value)->andReturns(\TestHelper::emptyDar());
        $this->expectException(\Rest_Exception_InvalidTokenException::class);

        $this->token_manager->checkToken($this->token);
    }

    public function testItExpiresATokenIfItBelongsToUser(): void
    {
        $this->user_manager->shouldReceive('getUserById')->with($this->user_id)->andReturns($this->user);

        $this->token_dao->shouldReceive('checkTokenExistenceForUserId')->with($this->user_id, $this->token_value)->andReturns(\TestHelper::arrayToDar(array()));
        $this->token_dao->shouldReceive('deleteToken')->with($this->token_value)->andReturns(true)->once();

        $this->token_manager->expireToken($this->token);
    }

    public function testItExpiresAllTokensForAUser(): void
    {
        $this->token_dao->shouldReceive('deleteAllTokensForUser')->with($this->user_id)->once();

        $this->token_manager->expireAllTokensForUser($this->user);
    }

    public function testItDoesNotExpireATokenIfItDoesNotBelongToUser(): void
    {
        $this->token_dao->shouldReceive('checkTokenExistenceForUserId')->with($this->user_id, $this->token_value)->andReturns(\TestHelper::emptyDar());

        $this->token_dao->shouldReceive('deleteToken')->with($this->token_value)->never();
        $this->expectException(\Rest_Exception_InvalidTokenException::class);

        $this->token_manager->expireToken($this->token);
    }

    public function testItAddsATokenToDatabase(): void
    {
        $this->token_dao->shouldReceive('addTokenForUserId')->with($this->user_id, \Mockery::any(), \Mockery::any())->andReturns(true)->once();

        $this->assertNotNull($this->token_manager->generateTokenForUser($this->user));
    }
}
