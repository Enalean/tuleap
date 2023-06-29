<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class OnlyOfficeSaveDocumentTokenRefresherDBStore implements OnlyOfficeSaveDocumentTokenRefresher
{
    public function __construct(
        private SplitTokenIdentifierTranslator $document_save_token_identifier_unserializer,
        private OnlyOfficeSaveDocumentTokenVerifier $only_office_save_document_token_verifier,
        private \DateInterval $expiration_delay,
        private OnlyOfficeSaveDocumentTokenDAO $dao,
    ) {
    }

    public function refreshToken(ConcealedString $raw_save_token, \DateTimeImmutable $now): Ok|Err
    {
        try {
            $split_token = $this->document_save_token_identifier_unserializer->getSplitToken($raw_save_token);
        } catch (SplitTokenException $exception) {
            return Result::err(Fault::fromThrowable($exception));
        }

        $save_token_data = $this->only_office_save_document_token_verifier->getDocumentSaveTokenData($split_token, $now);

        if ($save_token_data === null) {
            return Result::err(Fault::fromMessage(sprintf('No valid save token found with ID #%d (possibly already expired)', $split_token->getID())));
        }

        $this->dao->updateTokensExpirationDate(
            $save_token_data->document_id,
            $save_token_data->server_id,
            $now->getTimestamp(),
            $now->add($this->expiration_delay)->getTimestamp()
        );
        return Result::ok(null);
    }
}
