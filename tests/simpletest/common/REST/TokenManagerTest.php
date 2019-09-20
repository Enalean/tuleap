<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Rest_TokenManagerTest extends TuleapTestCase
{

    /** @var  Rest_TokenManager */
    private $token_manager;

    /** @var  Rest_TokenDao */
    private $token_dao;

    /** @var  Rest_TokenFactory */
    private $token_factory;

    public function setUp()
    {
        parent::setUp();

        $this->user_manager  = mock('UserManager');
        $this->token_dao     = mock('Rest_TokenDao');
        $this->token_factory = new Rest_TokenFactory($this->token_dao);
        $this->token_manager = new Rest_TokenManager($this->token_dao, $this->token_factory, $this->user_manager);

        $this->user_id     = 'whatever';
        $this->token_value = 'whateverSha1Token';
        $this->token       = new Rest_Token($this->user_id, $this->token_value);
        $this->user        = aUser()->withId($this->user_id)->build();
    }

    public function itReturnsTheUserIfTokenIsValid()
    {
        stub($this->user_manager)->getUserById($this->user_id)->returns($this->user);

        stub($this->token_dao)->checkTokenExistenceForUserId($this->user_id, $this->token_value)->returnsDar(array());
        $this->assertEqual($this->user, $this->token_manager->checkToken($this->token));
    }

    public function itThrowsAnExceptionIfTokenIsInvalid()
    {
        stub($this->token_dao)->checkTokenExistenceForUserId($this->user_id, $this->token_value)->returnsEmptyDar();
        $this->expectException("Rest_Exception_InvalidTokenException");

        $this->token_manager->checkToken($this->token);
    }

    public function itExpiresATokenIfItBelongsToUser()
    {
        stub($this->user_manager)->getUserById($this->user_id)->returns($this->user);

        stub($this->token_dao)->checkTokenExistenceForUserId($this->user_id, $this->token_value)->returnsDar(array());
        stub($this->token_dao)->deleteToken($this->token_value)->returns(true);

        expect($this->token_dao)->deleteToken($this->token_value)->once();

        $this->token_manager->expireToken($this->token);
    }

    public function itExpiresAllTokensForAUser()
    {
        expect($this->token_dao)->deleteAllTokensForUser($this->user_id)->once();

        $this->token_manager->expireAllTokensForUser($this->user);
    }

    public function itDoesNotExpireATokenIfItDoesNotBelongToUser()
    {
        stub($this->token_dao)->checkTokenExistenceForUserId($this->user_id, $this->token_value)->returnsEmptyDar();

        expect($this->token_dao)->deleteToken($this->token_value)->never();
        $this->expectException("Rest_Exception_InvalidTokenException");

        $this->token_manager->expireToken($this->token);
    }

    public function itAddsATokenToDatabase()
    {
        stub($this->token_dao)->addTokenForUserId()->returns(true);

        expect($this->token_dao)->addTokenForUserId($this->user_id, '*', '*')->once();

        $this->token_manager->generateTokenForUser($this->user);
    }

    public function _itGeneratesAProperToken()
    {
        stub($this->token_dao)->addTokenForUserId()->returns(true);
        $result = $this->token_manager->generateTokenForUser($this->user);
        var_dump($result);
    }
}
