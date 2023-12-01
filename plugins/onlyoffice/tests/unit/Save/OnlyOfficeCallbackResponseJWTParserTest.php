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
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerKeyEncryption;
use Tuleap\OnlyOffice\Stubs\IRetrieveDocumentServersStub;
use Tuleap\Option\Option;
use Tuleap\Test\PHPUnit\TestCase;

final class OnlyOfficeCallbackResponseJWTParserTest extends TestCase
{
    use ForgeConfigSandbox;

    private string $encrypted_secret_server_1;
    private string $encrypted_secret_server_2;

    protected function setUp(): void
    {
        $root = vfsStream::setup()->url();
        mkdir($root . '/conf/');
        \ForgeConfig::set('sys_custom_dir', $root);
        $secret_key                      = (new KeyFactory())->getEncryptionKey();
        $this->encrypted_secret_server_1 = base64_encode(
            SymmetricCrypto::encrypt(
                new ConcealedString(str_repeat('A', 32)),
                $secret_key
            )
        );
        $this->encrypted_secret_server_2 = base64_encode(
            SymmetricCrypto::encrypt(
                new ConcealedString(str_repeat('B', 32)),
                $secret_key
            )
        );
    }

    public function testParsesSaveCallbackContent(): void
    {
        $res = $this->buildParser(true)->parseCallbackResponseContent(
            json_encode(
                ['token' => self::buildJWT(
                    ['status'  => 2,
                        'url'     => 'https://example.com/download',
                        'history' => ['serverVersion' => '7.2.0', 'changes' => [['user' => ['id' => '102']]]],
                    ]
                ),
                ],
                JSON_THROW_ON_ERROR
            ),
            new SaveDocumentTokenData(123, 101, 102, 1),
        );

        self::assertEquals(
            Option::fromValue(
                new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])
            ),
            $res->unwrapOr(null)
        );
    }

    public function testParsesNotSaveRelatedCallbackContent(): void
    {
        $res = $this->buildParser(true)->parseCallbackResponseContent(
            json_encode(['token' => self::buildJWT(['status' => 1])], JSON_THROW_ON_ERROR),
            new SaveDocumentTokenData(123, 101, 102, 1),
        );

        self::assertEquals(
            Option::nothing(OnlyOfficeCallbackSaveResponseData::class),
            $res->unwrapOr(null)
        );
    }

    public function testRejectsWhenCallbackContentIsNotJSON(): void
    {
        self::assertTrue(
            Result::isErr(
                $this->buildParser(true)->parseCallbackResponseContent(
                    'Not JSON',
                    new SaveDocumentTokenData(123, 101, 102, 1),
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentIsNotAnArray(): void
    {
        self::assertTrue(
            Result::isErr(
                $this->buildParser(true)->parseCallbackResponseContent(
                    json_encode('Not an array', JSON_THROW_ON_ERROR),
                    new SaveDocumentTokenData(123, 101, 102, 1),
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentJSONDoesNotHaveATokenKey(): void
    {
        self::assertTrue(
            Result::isErr(
                $this->buildParser(true)->parseCallbackResponseContent(
                    json_encode(['invalid_callback_json' => true], JSON_THROW_ON_ERROR),
                    new SaveDocumentTokenData(123, 101, 102, 1),
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentJWTCannotBeParsed(): void
    {
        self::assertTrue(
            Result::isErr(
                $this->buildParser(true)->parseCallbackResponseContent(
                    json_encode(['token' => 'not_a_jwt'], JSON_THROW_ON_ERROR),
                    new SaveDocumentTokenData(123, 101, 102, 1),
                )
            )
        );
    }

    public function testRejectsWhenCallbackContentJWTCannotBeValidated(): void
    {
        self::assertTrue(
            Result::isErr(
                $this->buildParser(false)->parseCallbackResponseContent(
                    json_encode(['token' => self::buildJWT([])], JSON_THROW_ON_ERROR),
                    new SaveDocumentTokenData(123, 101, 102, 1),
                )
            )
        );
    }

    /**
     * @dataProvider dataProviderJWtWithUnexpectedClaims
     */
    public function testRejectsWhenCallbackContentJWTDoesNotTheExpectedClaims(string $jwt): void
    {
        self::assertTrue(
            Result::isErr(
                $this->buildParser(true)->parseCallbackResponseContent(
                    json_encode(['token' => $jwt], JSON_THROW_ON_ERROR),
                    new SaveDocumentTokenData(123, 101, 102, 1),
                )
            )
        );
    }

    public static function dataProviderJWtWithUnexpectedClaims(): array
    {
        return [
            'Missing status'                      => [self::buildJWT([])],
            'Missing download URL'                => [self::buildJWT(['status' => 2])],
            'Malformed download URL'              => [self::buildJWT(['status' => 2, 'url' => true])],
            'Missing history key'                 => [self::buildJWT(
                ['status' => 2, 'url' => 'https://example.com/example']
            ),
            ],
            'Malformed history key'               => [self::buildJWT(
                ['status' => 2, 'url' => 'https://example.com/example', 'history' => 'foo']
            ),
            ],
            'Missing history.serverVersion key'   => [self::buildJWT(
                ['status' => 2, 'url' => 'https://example.com/example', 'history' => []]
            ),
            ],
            'Malformed history.serverVersion key' => [self::buildJWT(
                ['status' => 2, 'url' => 'https://example.com/example', 'history' => ['serverVersion' => true]]
            ),
            ],
            'Malformed history.changes.user key'  => [self::buildJWT(
                ['status'  => 2,
                    'url'     => 'https://example.com/example',
                    'history' => ['serverVersion' => '7.2.0', 'changes' => [['user' => ['id' => 'wrong']]]],
                ]
            ),
            ],
        ];
    }

    private function buildParser(bool $pass_jwt_validation): OnlyOfficeCallbackResponseJWTParser
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
            new DocumentServerForSaveDocumentTokenRetriever(
                IRetrieveDocumentServersStub::buildWith(
                    DocumentServer::withoutProjectRestrictions(
                        1,
                        'https://example.com/1',
                        new ConcealedString($this->encrypted_secret_server_1)
                    ),
                    DocumentServer::withoutProjectRestrictions(
                        2,
                        'https://example.com/2',
                        new ConcealedString($this->encrypted_secret_server_2)
                    ),
                ),
            ),
            new DocumentServerKeyEncryption(new KeyFactory()),
        );
    }

    /**
     * @param array<non-empty-string,mixed> $claims
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
