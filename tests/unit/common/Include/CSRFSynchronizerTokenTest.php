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
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\TemporaryTestDirectory;

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

    public function testItVerifiesIfATokenIsValid(): void
    {
        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token      = $csrf_token->getToken();

        self::assertTrue($csrf_token->isValid($token));
    }

    public function testItVerifiesIfATokenIsValidForASpecificUrl(): void
    {
        $csrf_token_creator = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token              = $csrf_token_creator->getToken();

        $csrf_token_verifier = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        self::assertTrue($csrf_token_verifier->isValid($token));
    }

    public function testItValidatesTheSameTokenMultipleTimes(): void
    {
        $csrf_token_1 = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        $token        = $csrf_token_1->getToken();

        self::assertTrue($csrf_token_1->isValid($token));

        $csrf_token_2 = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        self::assertTrue($csrf_token_2->isValid($token));
    }

    public function testItDoesNotValidateInvalidToken(): void
    {
        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        self::assertFalse($csrf_token->isValid('invalid_token'));
    }

    public function testItDoesNothingWhenAValidTokenIsChecked(): void
    {
        $GLOBALS['Response']->expects(self::never())->method('redirect');

        $csrf_token = new CSRFSynchronizerToken(
            '/path/to/uri',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = $this->createMock(\Codendi_Request::class);
        $request->method('get')->with(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->willReturn($csrf_token->getToken());

        $csrf_token->check('/path/to/url', $request);
    }

    public function testItRedirectsWhenAnInvalidTokenIsChecked(): void
    {
        $uri = '/path/to/uri';
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with($uri);

        $csrf_token = new CSRFSynchronizerToken(
            $uri,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = $this->createMock(\Codendi_Request::class);
        $request->method('get')->with(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->willReturn('invalid_token');

        $csrf_token->check($uri, $request);
    }

    public function testItRedirectsWhenNoTokenIsProvidedInTheRequest(): void
    {
        $uri = '/path/to/uri';
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with($uri);

        $csrf_token = new CSRFSynchronizerToken(
            $uri,
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        $request = $this->createMock(\Codendi_Request::class);
        $request->method('get')->with(CSRFSynchronizerToken::DEFAULT_TOKEN_NAME)->willReturn(false);

        $csrf_token->check($uri, $request);
    }

    public function testItGeneratesHTMLInput(): void
    {
        ForgeConfig::set('codendi_dir', __DIR__ . '/../../../../');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());

        $token1 = new CSRFSynchronizerToken(
            '/path/to/uri/1',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );

        self::assertEquals(
            '<input type="hidden" name="' . CSRFSynchronizerToken::DEFAULT_TOKEN_NAME . '" value="' . $token1->getToken() . '" />',
            $token1->fetchHTMLInput()
        );
    }

    public function testItLimitsTheNumberOfStoredCSRFTokens(): void
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

        self::assertCount(
            CSRFSynchronizerToken::MAX_TOKEN_PER_STORAGE,
            $this->storage[CSRFSynchronizerToken::STORAGE_PREFIX],
        );

        $token = new CSRFSynchronizerToken(
            'first_token_created',
            CSRFSynchronizerToken::DEFAULT_TOKEN_NAME,
            $this->storage
        );
        self::assertFalse($token->isValid($first_token_challenge));
    }
}
