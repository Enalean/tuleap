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

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBTransactionExecutor;

final class JenkinsTuleapPluginHookTokenVerifierDBStore implements JenkinsTuleapPluginHookTokenVerifier
{
    public function __construct(
        private JenkinsTuleapPluginHookTokenDAO $dao,
        private DBTransactionExecutor $db_transaction_executor,
        private SplitTokenIdentifierTranslator $token_identifier_unserializer,
        private SplitTokenVerificationStringHasher $hasher,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function isTriggerTokenValid(ConcealedString $trigger_token, DateTimeImmutable $now): bool
    {
        try {
            $split_token = $this->token_identifier_unserializer->getSplitToken($trigger_token);
        } catch (SplitTokenException $exception) {
            $this->logger->debug('Jenkins trigger token incorrectly formatted', ['context' => $exception]);
            return false;
        }

        return $this->db_transaction_executor->execute(
            function () use ($split_token, $now): bool {
                $row = $this->dao->searchTokenVerification($split_token->getID(), $now->getTimestamp());
                if ($row === null) {
                    $this->logger->debug(sprintf('Jenkins trigger token #%d not found (possibly expired or already used)', $split_token->getID()));
                    return false;
                }

                if (! $this->hasher->verifyHash($split_token->getVerificationString(), $row['verifier'])) {
                    $this->logger->debug(sprintf('Jenkins trigger token #%d invalid', $split_token->getID()));
                    return false;
                }

                $this->dao->deleteTokenByID($split_token->getID());

                return true;
            }
        );
    }
}
