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

use Psr\Log\LoggerInterface;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class OnlyOfficeSaveDocumentTokenVerifier
{
    public function __construct(
        private OnlyOfficeSaveDocumentTokenDAO $dao,
        private SplitTokenVerificationStringHasher $hasher,
        private LoggerInterface $logger,
    ) {
    }

    public function getDocumentSaveTokenData(SplitToken $save_token, \DateTimeImmutable $current_time): ?SaveDocumentTokenData
    {
        $row           = $this->dao->searchTokenVerificationAndAssociatedData($save_token->getID(), $current_time->getTimestamp());
        $save_token_id = $save_token->getID();
        if ($row === null) {
            $this->logger->debug(sprintf('Save document token #%d not found (possibly expired)', $save_token_id));
            return null;
        }

        if (! $this->hasher->verifyHash($save_token->getVerificationString(), $row['verifier'])) {
            $this->logger->debug(sprintf('Save document token #%d invalid', $save_token_id));
            return null;
        }

        return new SaveDocumentTokenData($save_token_id, $row['user_id'], $row['document_id']);
    }
}
