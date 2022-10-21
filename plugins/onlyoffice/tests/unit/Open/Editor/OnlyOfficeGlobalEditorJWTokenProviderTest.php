<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open\Editor;

use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ValidAt;
use org\bovigo\vfs\vfsStream;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeGlobalEditorJWTokenProviderTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testProvidesJWToken(): void
    {
        $token_provider = self::buildTokenProvider(
            Result::ok(new OnlyOfficeDocumentConfig('docx', 'key', 'Doc.docx', 'https://example.com/download', true))
        );

        $root = vfsStream::setup()->url();
        mkdir($root . '/conf/');
        \ForgeConfig::set('sys_custom_dir', $root);
        $secret = str_repeat('A', 32);
        \ForgeConfig::set('onlyoffice_document_server_secret', base64_encode(SymmetricCrypto::encrypt(new ConcealedString($secret), (new KeyFactory())->getEncryptionKey())));

        $result = $token_provider->getGlobalEditorJWToken(UserTestBuilder::buildWithDefaults(), 12, new \DateTimeImmutable('@20'));

        $token = $result->unwrapOr('');

        $parsed_token = (new JwtFacade())->parse(
            $token,
            new SignedWith(new Sha256(), InMemory::plainText($secret)),
            new class implements ValidAt
            {
                public function assert(Token $token): void
                {
                    // Do nothing, we expect it to be valid
                }
            }
        );

        self::assertEquals(
            ['fileType' => 'docx', 'key' => 'key', 'title' => 'Doc.docx', 'url' => 'https://example.com/download', 'permissions' => ['chat' => false, 'print' => false, 'edit' => true]],
            $parsed_token->claims()->get('document')
        );
        self::assertEquals(
            [
                'lang'        => 'en',
                'region'      => 'en-US',
                'user'        => ['id' => '110', 'name' => 'User #110'],
                'callbackUrl' => 'https:///onlyoffice/document_save',
            ],
            $parsed_token->claims()->get('editorConfig')
        );
    }

    public function testReturnsFaultWhenDocumentConfigCannotBeProvided(): void
    {
        $expected_fault = Fault::fromMessage('Failure');
        $token_provider = self::buildTokenProvider(Result::err($expected_fault));

        $result = $token_provider->getGlobalEditorJWToken(UserTestBuilder::buildWithDefaults(), 12, new \DateTimeImmutable('@20'));

        self::assertTrue(Result::isErr($result));
        $result->mapErr(function (Fault $fault) use ($expected_fault): void {
            self::assertSame($expected_fault, $fault);
        });
    }

    private static function buildTokenProvider(Ok|Err $config_document_provider_result): OnlyOfficeGlobalEditorJWTokenProvider
    {
        return new OnlyOfficeGlobalEditorJWTokenProvider(
            new class ($config_document_provider_result) implements ProvideOnlyOfficeConfigDocument
            {
                /**
                 * @psalm-param Ok<OnlyOfficeDocumentConfig>|Err<Fault> $result
                 */
                public function __construct(private Ok|Err $result)
                {
                }

                public function getDocumentConfig(\PFUser $user, int $item_id, \DateTimeImmutable $now): Ok|Err
                {
                    return $this->result;
                }
            },
            new JwtFacade(),
            new Sha256(),
        );
    }
}
