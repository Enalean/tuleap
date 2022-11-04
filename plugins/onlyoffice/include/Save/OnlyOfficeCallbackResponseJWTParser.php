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
use Tuleap\OnlyOffice\Administration\OnlyOfficeDocumentServerSettings;

final class OnlyOfficeCallbackResponseJWTParser implements OnlyOfficeCallbackResponseParser
{
    public function __construct(
        private Parser $jwt_parser,
        private Validator $jwt_validator,
        private ValidAt $jwt_valid_at_constraint,
        private Signer $jwt_signer,
    ) {
    }

    /**
     * @psalm-return Ok<OptionalValue<OnlyOfficeCallbackSaveResponseData>>|Err<Fault>
     */
    public function parseCallbackResponseContent(string $response_content): Ok|Err
    {
        return $this->decodeJSON($response_content)
            ->andThen(\Closure::fromCallable([$this, 'extractTokenFromJSONContent']))
            ->andThen(\Closure::fromCallable([$this, 'parseJWT']))
            ->andThen(\Closure::fromCallable([$this, 'validateJWT']))
            ->andThen(\Closure::fromCallable([$this, 'parseJWTClaims']));
    }

    /**
     * @psalm-return Ok<array>|Err<Fault>
     */
    private function decodeJSON(string $response_content): Ok|Err
    {
        try {
            return Result::ok(json_decode($response_content, true, 128, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            return Result::err(Fault::fromThrowableWithMessage($e, 'Cannot parse ONLYOFFICE callback content response as JSON'));
        }
    }

    /**
     * @psalm-return Ok<string>|Err<Fault>
     */
    private function extractTokenFromJSONContent(array $json_content): Ok|Err
    {
        if (isset($json_content['token']) && is_string($json_content['token'])) {
            return Result::ok($json_content['token']);
        }

        return Result::err(Fault::fromMessage('Cannot find the `token` key in the ONLYOFFICE callback JSON'));
    }

    /**
     * @psalm-return Ok<UnencryptedToken>|Err<Fault>
     */
    private function parseJWT(string $jwt): Ok|Err
    {
        try {
            $token = $this->jwt_parser->parse($jwt);
        } catch (InvalidTokenStructure | UnsupportedHeaderFound $ex) {
            return Result::err(
                Fault::fromThrowableWithMessage($ex, 'Cannot parse the JWT found in the ONLYOFFICE callback')
            );
        }

        assert($token instanceof UnencryptedToken);

        return Result::ok($token);
    }

    /**
     * @psalm-return Ok<Token\DataSet>|Err<Fault>
     */
    private function validateJWT(UnencryptedToken $token): Ok|Err
    {
        $key = Signer\Key\InMemory::plainText(\ForgeConfig::getSecretAsClearText(OnlyOfficeDocumentServerSettings::SECRET)->getString());
        try {
            $this->jwt_validator->assert(
                $token,
                new SignedWith($this->jwt_signer, $key),
                $this->jwt_valid_at_constraint,
            );
        } catch (RequiredConstraintsViolated $ex) {
            return Result::err(
                Fault::fromThrowableWithMessage($ex, 'Cannot validate the JWT found in the ONLYOFFICE callback')
            );
        }
        return Result::ok($token->claims());
    }

    /**
     * @psalm-return Ok<OptionalValue<OnlyOfficeCallbackSaveResponseData>>|Err<Fault>
     */
    private function parseJWTClaims(Token\DataSet $claims): Ok|Err
    {
        return match ($claims->get('status')) {
            null => Result::err(Fault::fromMessage('No `status` key found in the ONLYOFFICE JWT callback')),
            OnlyOfficeDocumentStatusCallback::STATUS_SAVE, OnlyOfficeDocumentStatusCallback::STATUS_SAVE_CORRUPTED => $this->parseJWTClaimsOfSaveRequest($claims),
            default => Result::ok(OptionalValue::nothing(OnlyOfficeCallbackSaveResponseData::class)),
        };
    }

    /**
     * @psalm-return Ok<OptionalValue<OnlyOfficeCallbackSaveResponseData>>|Err<Fault>
     */
    private function parseJWTClaimsOfSaveRequest(Token\DataSet $claims): Ok|Err
    {
        $download_url = $claims->get('url');
        if (! is_string($download_url)) {
            return Result::err(
                Fault::fromMessage(
                    sprintf('Invalid or missing `url` key (got "%s") in the ONLYOFFICE JWT callback claims', var_export($download_url, true))
                )
            );
        }
        $history = $claims->get('history');
        if (! is_array($history) || ! isset($history['serverVersion']) || ! is_string($history['serverVersion'])) {
            return Result::err(
                Fault::fromMessage(
                    sprintf('Invalid or missing `history` key (got "%s") in the ONLYOFFICE JWT callback claims', var_export($history, true))
                )
            );
        }

        return Result::ok(
            OptionalValue::fromValue(new OnlyOfficeCallbackSaveResponseData($download_url, $history['serverVersion']))
        );
    }
}
