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

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\ValidAt;
use Lcobucci\JWT\Validator;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerKeyEncryption;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerNotFoundException;
use Tuleap\Option\Option;

final class OnlyOfficeCallbackResponseJWTParser implements OnlyOfficeCallbackResponseParser
{
    public function __construct(
        private Parser $jwt_parser,
        private Validator $jwt_validator,
        private ValidAt $jwt_valid_at_constraint,
        private Signer $jwt_signer,
        private DocumentServerForSaveDocumentTokenRetriever $server_retriever,
        private DocumentServerKeyEncryption $encryption,
    ) {
    }

    /**
     * @psalm-return Ok<Option<OnlyOfficeCallbackSaveResponseData>>|Err<Fault>
     */
    public function parseCallbackResponseContent(
        string $response_content,
        SaveDocumentTokenData $save_token_information,
    ): Ok|Err {
        return $this->retrieveServer($response_content, $save_token_information)
            ->andThen(\Closure::fromCallable([$this, 'decodeJSON']))
            ->andThen(\Closure::fromCallable([$this, 'extractTokenFromJSONContent']))
            ->andThen(\Closure::fromCallable([$this, 'parseJWT']))
            ->andThen(\Closure::fromCallable([$this, 'validateJWT']))
            ->andThen(\Closure::fromCallable([$this, 'parseJWTClaims']));
    }

    /**
     * @return Ok<array{json: string, server: DocumentServer}>|Err
     */
    private function retrieveServer(string $response_content, SaveDocumentTokenData $save_token_information): Ok|Err
    {
        try {
            return Result::ok([
                'json'   => $response_content,
                'server' => $this->server_retriever->getServerFromSaveToken($save_token_information),
            ]);
        } catch (DocumentServerNotFoundException $e) {
            return Result::err(
                Fault::fromMessage(
                    'Unable to find the document server information for document, maybe it has been removed?'
                )
            );
        } catch (DocumentServerHasNoExistingSecretException $e) {
            return Result::err(
                Fault::fromMessage(
                    'Document server does not have a JWT secret key'
                )
            );
        } catch (NoDocumentServerException $e) {
            return Result::err(
                Fault::fromMessage('No document server is configured')
            );
        }
    }

    /**
     * @param array{json: string, server: DocumentServer} $content
     *
     * @psalm-return Ok<array{json_content: array, server: DocumentServer}>|Err<Fault>
     */
    private function decodeJSON(array $content): Ok|Err
    {
        try {
            $json_content = json_decode($content['json'], true, 128, JSON_THROW_ON_ERROR);
            if (! is_array($json_content)) {
                return Result::err(
                    Fault::fromMessage('ONLYOFFICE JSON content should be an array')
                );
            }

            return Result::ok([
                'json_content' => $json_content,
                'server'       => $content['server'],
            ]);
        } catch (\JsonException $e) {
            return Result::err(
                Fault::fromThrowableWithMessage($e, 'Cannot parse ONLYOFFICE callback content response as JSON')
            );
        }
    }

    /**
     * @param array{json_content: array, server: DocumentServer} $content
     *
     * @psalm-return Ok<array{jwt: non-empty-string, server: DocumentServer}>|Err<Fault>
     */
    private function extractTokenFromJSONContent(array $content): Ok|Err
    {
        if (isset($content['json_content']['token']) && is_string($content['json_content']['token']) && $content['json_content']['token'] !== '') {
            return Result::ok([
                'jwt'    => $content['json_content']['token'],
                'server' => $content['server'],
            ]);
        }

        return Result::err(Fault::fromMessage('Cannot find the `token` key in the ONLYOFFICE callback JSON'));
    }

    /**
     * @param array{jwt: non-empty-string, server: DocumentServer} $jwt
     *
     * @psalm-return Ok<array{unencrypted_token: UnencryptedToken, server: DocumentServer}>|Err<Fault>
     */
    private function parseJWT(array $jwt): Ok|Err
    {
        try {
            $token = $this->jwt_parser->parse($jwt['jwt']);
        } catch (InvalidTokenStructure | UnsupportedHeaderFound $ex) {
            return Result::err(
                Fault::fromThrowableWithMessage($ex, 'Cannot parse the JWT found in the ONLYOFFICE callback')
            );
        }

        assert($token instanceof UnencryptedToken);

        return Result::ok([
            'unencrypted_token' => $token,
            'server'            => $jwt['server'],
        ]);
    }

    /**
     * @param array{unencrypted_token: UnencryptedToken, server: DocumentServer} $token
     *
     * @psalm-return Ok<Token\DataSet>|Err<Fault>
     */
    private function validateJWT(array $token): Ok|Err
    {
        $key = Signer\Key\InMemory::plainText(
            $this->encryption->decryptValue($token['server']->encrypted_secret_key->getString())->getString()
        );
        try {
            $this->jwt_validator->assert(
                $token['unencrypted_token'],
                new SignedWith($this->jwt_signer, $key),
                $this->jwt_valid_at_constraint,
            );
        } catch (RequiredConstraintsViolated $ex) {
            return Result::err(
                Fault::fromThrowableWithMessage($ex, 'Cannot validate the JWT found in the ONLYOFFICE callback')
            );
        }

        return Result::ok($token['unencrypted_token']->claims());
    }

    /**
     * @psalm-return Ok<Option<OnlyOfficeCallbackSaveResponseData>>|Err<Fault>
     */
    private function parseJWTClaims(Token\DataSet $claims): Ok|Err
    {
        return match ($claims->get('status')) {
            null => Result::err(Fault::fromMessage('No `status` key found in the ONLYOFFICE JWT callback')),
            OnlyOfficeDocumentStatusCallback::STATUS_SAVE, OnlyOfficeDocumentStatusCallback::STATUS_SAVE_CORRUPTED => $this->parseJWTClaimsOfSaveRequest(
                $claims
            ),
            default => Result::ok(Option::nothing(OnlyOfficeCallbackSaveResponseData::class)),
        };
    }

    /**
     * @psalm-return Ok<Option<OnlyOfficeCallbackSaveResponseData>>|Err<Fault>
     */
    private function parseJWTClaimsOfSaveRequest(Token\DataSet $claims): Ok|Err
    {
        $download_url = $claims->get('url');
        if (! is_string($download_url)) {
            return Result::err(
                Fault::fromMessage(
                    sprintf(
                        'Invalid or missing `url` key (got "%s") in the ONLYOFFICE JWT callback claims',
                        var_export($download_url, true)
                    )
                )
            );
        }
        $history = $claims->get('history');
        if (! is_array($history) || ! isset($history['serverVersion']) || ! is_string($history['serverVersion'])) {
            return Result::err(
                Fault::fromMessage(
                    sprintf(
                        'Invalid or missing `history` key (got "%s") in the ONLYOFFICE JWT callback claims',
                        var_export($history, true)
                    )
                )
            );
        }
        $author_identifiers = [];
        if (isset($history['changes']) && is_array($history['changes'])) {
            foreach ($history['changes'] as $change) {
                if (! isset($change['user']['id']) || ! ctype_digit($change['user']['id'])) {
                    return Result::err(
                        Fault::fromMessage(
                            sprintf(
                                'Invalid `history.changes` key (got "%s") in the ONLYOFFICE JWT callback claims',
                                var_export($history['changes'], true)
                            )
                        )
                    );
                }
                $author_identifiers[] = (int) $change['user']['id'];
            }
        }

        return Result::ok(
            Option::fromValue(
                new OnlyOfficeCallbackSaveResponseData($download_url, $history['serverVersion'], $author_identifiers)
            )
        );
    }
}
