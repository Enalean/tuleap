<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

class CSRFSynchronizerTokenTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\ForgeConfigSandbox, \Tuleap\GlobalResponseMock, \Tuleap\GlobalLanguageMock, \Tuleap\TemporaryTestDirectory;

    /**
     * @var array
     */
    private $storage;

    protected function setUp(): void
    {
        $this->storage = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    public function testItVerifiesIfATokenIsValid()
    {
        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token = $csrf_token->getToken();

        $this->assertTrue($csrf_token->isValid($token));
    }

    public function testItVerifiesIfATokenIsValidForASpecificUrl()
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

    public function testItValidatesTheSameTokenMultipleTimes()
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

    public function testItDoesNotValidateInvalidToken()
    {
        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $this->assertFalse($csrf_token->isValid('invalid_token'));
    }

    public function testItDoesNothingWhenAValidTokenIsChecked()
    {
        $GLOBALS['Response']->shouldReceive('redirect')->never();

        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = \Mockery::spy(\Codendi_Request::class);
        $request->shouldReceive('get')->with(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->andReturns($csrf_token->getToken());

        $csrf_token->check('/path/to/url', $request);
    }

    public function testItRedirectsWhenAnInvalidTokenIsChecked()
    {
        $uri = '/path/to/uri';
        $GLOBALS['Response']->shouldReceive('redirect')->with($uri)->once();

        $csrf_token = new CSRFSynchronizerToken(
            $uri,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = \Mockery::spy(\Codendi_Request::class);
        $request->shouldReceive('get')->with(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->andReturns('invalid_token');

        $csrf_token->check($uri, $request);
    }

    public function testItRedirectsWhenNoTokenIsProvidedInTheRequest()
    {
        $uri = '/path/to/uri';
        $GLOBALS['Response']->shouldReceive('redirect')->with($uri)->once();

        $csrf_token = new CSRFSynchronizerToken(
            $uri,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = \Mockery::spy(\Codendi_Request::class);
        $request->shouldReceive('get')->with(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->andReturns(false);

        $csrf_token->check($uri, $request);
    }

    public function testItGeneratesHTMLInput()
    {
        ForgeConfig::set('codendi_dir', __DIR__ . '/../../../../');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $token1  = new CSRFSynchronizerToken(
            '/path/to/uri/1',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $this->assertEquals(
            '<input type="hidden" name="' . CSRFSynchronizerToken::DEFAULT_TOKEN_NAME . '" value="' . $token1->getToken() . '" />',
            $token1->fetchHTMLInput()
        );
    }

    public function testItLimitsTheNumberOfStoredCSRFTokens()
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

        $this->assertCount(
            CSRFSynchronizerToken::MAX_TOKEN_PER_STORAGE,
            $this->storage[CSRFSynchronizerToken::STORAGE_PREFIX],
        );

        $token = new CSRFSynchronizerToken(
            'first_token_created',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $this->assertFalse($token->isValid($first_token_challenge));
    }
}
