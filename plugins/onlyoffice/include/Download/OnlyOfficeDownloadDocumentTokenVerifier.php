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

namespace Tuleap\OnlyOffice\Download;

use Psr\Log\LoggerInterface;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBTransactionExecutor;

class OnlyOfficeDownloadDocumentTokenVerifier
{
    public function __construct(
        private OnlyOfficeDownloadDocumentTokenDAO $dao,
        private DBTransactionExecutor $db_transaction_executor,
        private SplitTokenVerificationStringHasher $hasher,
        private LoggerInterface $logger,
    ) {
    }

    public function getDocumentDownloadTokenData(SplitToken $download_token, \DateTimeImmutable $current_time): ?DownloadDocumentTokenData
    {
        return $this->db_transaction_executor->execute(
            function () use ($download_token, $current_time): ?DownloadDocumentTokenData {
                $row = $this->dao->searchTokenVerificationAndAssociatedData($download_token->getID(), $current_time->getTimestamp());
                if ($row === null) {
                    $this->logger->debug(sprintf('Download document token #%d not found (possibly expired or already used)', $download_token->getID()));
                    return null;
                }

                if (! $this->hasher->verifyHash($download_token->getVerificationString(), $row['verifier'])) {
                    $this->logger->debug(sprintf('Download document token #%d invalid', $download_token->getID()));
                    return null;
                }

                return new DownloadDocumentTokenData($row['user_id'], $row['document_id']);
            }
        );
    }
}
