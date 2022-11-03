<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validator;
use org\bovigo\vfs\vfsStream;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeCallbackResponseJWTParserTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        $root = vfsStream::setup()->url();
        mkdir($root . '/conf/');
        \ForgeConfig::set('sys_custom_dir', $root);
        $secret = str_repeat('A', 32);
        \ForgeConfig::set('onlyoffice_document_server_secret', base64_encode(SymmetricCrypto::encrypt(new ConcealedString($secret), (new KeyFactory())->getEncryptionKey())));
    }

    public function testParsesSaveCallbackContent(): void
    {
        $res = self::buildParser(true)->parseCallbackResponseContent(
            json_encode(['token' => self::buildJWT(['status' => 2, 'url' => 'https://example.com/download'])], JSON_THROW_ON_ERROR)
        );

        self::assertEquals(
            OptionalValue::fromValue(
                new OnlyOfficeCallbackSaveResponseData('https://example.com/download')
            ),
            $res->unwrapOr(null)
        );
    }

    public function testParsesNotSaveRelatedCallbackContent(): void
    {
        $res = self::buildParser(true)->parseCallbackResponseContent(
            json_encode(['token' => self::buildJWT(['status' => 1])], JSON_THROW_ON_ERROR)
        );

        self::assertEquals(
            OptionalValue::nothing(OnlyOfficeCallbackSaveResponseData::class),
            $res->unwrapOr(null)
        );
    }

    public function testRejectsWhenCallbackContentIsNotJSON(): void
    {
        self::assertTrue(
            Result::isErr(
                self::buildParser(true)->parseCallbackResponseContent('Not JSON')
            )
        );
    }

    public function testRejectsWhenCallbackContentJSONDoesNotHaveATokenKey(): void
    {
        self::assertTrue(
            Result::isErr(
                self::buildParser(true)->parseCallbackResponseContent(
                    json_encode(['invalid_callback_json' => true], JSON_THROW_ON_ERROR)
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentJWTCannotBeParsed(): void
    {
        self::assertTrue(
            Result::isErr(
                self::buildParser(true)->parseCallbackResponseContent(
                    json_encode(['token' => 'not_a_jwt'], JSON_THROW_ON_ERROR)
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentJWTCannotBeValidated(): void
    {
        self::assertTrue(
            Result::isErr(
                self::buildParser(false)->parseCallbackResponseContent(
                    json_encode(['token' => self::buildJWT([])], JSON_THROW_ON_ERROR)
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentJWTDoesNotHaveAStatus(): void
    {
        self::assertTrue(
            Result::isErr(
                self::buildParser(true)->parseCallbackResponseContent(
                    json_encode(['token' => self::buildJWT([])], JSON_THROW_ON_ERROR)
                )
            )
        );
    }

    public function testRejectsWhenSaveCallbackContentJWTDoesNotHaveADownloadURL(): void
    {
        self::assertTrue(
            Result::isErr(
                self::buildParser(true)->parseCallbackResponseContent(
                    json_encode(['token' => self::buildJWT(['status' => 2])], JSON_THROW_ON_ERROR)
                )
            )
        );
    }

    private static function buildParser(bool $pass_jwt_validation): OnlyOfficeCallbackResponseJWTParser
    {
        return new OnlyOfficeCallbackResponseJWTParser(
            new Parser(new JoseEncoder()),
            new class ($pass_jwt_validation) implements Validator {
                public function __construct(private bool $is_valid)
                {
                }

                public function assert(Token $token, Constraint ...$constraints): void
                {
                    if ($this->validate($token, ...$constraints)) {
                        return;
                    }
                    throw RequiredConstraintsViolated::fromViolations(
                        ConstraintViolation::error(
                            'Not valid',
                            new class implements Constraint {
                                public function assert(Token $token): void
                                {
                                }
                            }
                        )
                    );
                }

                public function validate(Token $token, Constraint ...$constraints): bool
                {
                    return $this->is_valid;
                }
            },
            new Constraint\LooseValidAt(new FrozenClock(new \DateTimeImmutable('@10'))),
            new Signer\Hmac\Sha256(),
        );
    }

    /**
     * @param array<string,mixed> $claims
     */
    private static function buildJWT(array $claims): string
    {
        return (new JwtFacade())->issue(
            new Signer\Hmac\Sha256(),
            InMemory::plainText(str_repeat('a', 32)),
            function (Builder $builder) use ($claims): Builder {
                foreach ($claims as $name => $value) {
                    $builder = $builder->withClaim($name, $value);
                }
                return $builder;
            }
        )->toString();
    }
}