<?php
/*
 * Copyright (c) Enalean, 2011 - 2016. All Rights Reserved.
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


class CSRFSynchronizerTokenTest extends TuleapTestCase
{
    /**
     * @var array
     */
    private $storage;


    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        $this->storage = array();
    }

    public function tearDown()
    {
        parent::tearDown();
        ForgeConfig::restore();
    }

    public function itVerifiesIfATokenIsValid()
    {
        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token = $csrf_token->getToken();

        $this->assertTrue($csrf_token->isValid($token));
    }

    public function itVerifiesIfATokenIsValidForASpecificUrl()
    {
        $csrf_token_creator = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token = $csrf_token_creator->getToken();

        $csrf_token_verifier = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $this->assertTrue($csrf_token_verifier->isValid($token));
    }

    public function itValidatesTheSameTokenMultipleTimes()
    {
        $csrf_token_1 = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token = $csrf_token_1->getToken();

        $this->assertTrue($csrf_token_1->isValid($token));

        $csrf_token_2 = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $this->assertTrue($csrf_token_2->isValid($token));
    }

    public function itDoesNotValidateInvalidToken()
    {
        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $this->assertFalse($csrf_token->isValid('invalid_token'));
    }

    public function itDoesNothingWhenAValidTokenIsChecked()
    {
        $GLOBALS['Response']->expectCallCount('redirect', 0);

        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = mock('Codendi_Request');
        stub($request)->get(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->returns($csrf_token->getToken());

        $csrf_token->check('/path/to/url', $request);
    }

    public function itRedirectsWhenAnInvalidTokenIsChecked()
    {
        $uri = '/path/to/uri';
        $GLOBALS['Response']->expectCallCount('redirect', 1);
        $GLOBALS['Response']->expectAt(0, 'redirect', array($uri));

        $csrf_token = new CSRFSynchronizerToken(
            $uri,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = mock('Codendi_Request');
        stub($request)->get(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->returns('invalid_token');

        $csrf_token->check($uri, $request);
    }

    public function itRedirectsWhenNoTokenIsProvidedInTheRequest()
    {
        $uri = '/path/to/uri';
        $GLOBALS['Response']->expectCallCount('redirect', 1);
        $GLOBALS['Response']->expectAt(0, 'redirect', array($uri));

        $csrf_token = new CSRFSynchronizerToken(
            $uri,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = mock('Codendi_Request');
        stub($request)->get(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->returns(false);

        $csrf_token->check($uri, $request);
    }

    public function itGeneratesHTMLInput()
    {
        ForgeConfig::set('codendi_dir', '/usr/share/tuleap');

        $token1  = new CSRFSynchronizerToken(
            '/path/to/uri/1',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $this->assertEqual(
            '<input type="hidden" name="' . CSRFSynchronizerToken::DEFAULT_TOKEN_NAME . '" value="' . $token1->getToken() . '" />',
            $token1->fetchHTMLInput()
        );
    }

    public function itLimitsTheNumberOfStoredCSRFTokens()
    {
        $first_token           = new CSRFSynchronizerToken(
            'first_token_created',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $first_token_challenge = $first_token->getToken();

        for ($i = 0; $i < CSRFSynchronizerToken::MAX_TOKEN_PER_STORAGE * 2; $i++) {
            new CSRFSynchronizerToken('/' . $i, CSRFSynchronizerToken::DEFAULT_TOKEN_NAME, $this->storage);
        }

        $this->assertEqual(
            CSRFSynchronizerToken::MAX_TOKEN_PER_STORAGE,
            count($this->storage[CSRFSynchronizerToken::STORAGE_PREFIX])
        );

        $token = new CSRFSynchronizerToken(
            'first_token_created',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $this->assertFalse($token->isValid($first_token_challenge));
    }
}
