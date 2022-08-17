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

namespace Tuleap\Git\Hook\DefaultBranchPush;

use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\Git\CommitMetadata\GitCommitReferenceString;
use Tuleap\Git\CommitMetadata\RetrieveCommitMessage;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ArtifactClosureNotAllowedFault;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Reference\TextWithPotentialReferences;

final class DefaultBranchPushProcessor
{
    public function __construct(
        private VerifyArtifactClosureIsAllowed $closure_verifier,
        private RetrieveCommitMessage $message_retriever,
    ) {
    }

    /**
     * @return Ok<DefaultBranchPushProcessed> | Err<Fault>
     */
    public function process(DefaultBranchPushReceived $push): Ok|Err
    {
        if (! $this->closure_verifier->isArtifactClosureAllowed((int) $push->getRepository()->getId())) {
            return Result::err(ArtifactClosureNotAllowedFault::build($push->getRepository()));
        }
        // We intentionally do not combine the list of results.
        // We want to continue processing other commits in case of fault and then log all faults.
        $texts  = [];
        $faults = [];
        foreach ($push->getCommitHashes() as $commit_hash) {
            $result = $this->processSingleCommit($push->getRepository(), $commit_hash);
            if (Result::isOk($result)) {
                $texts[] = $result->value;
            } else {
                $faults[] = $result->error;
            }
        }
        return Result::ok(
            new DefaultBranchPushProcessed(
                new PotentialReferencesReceived(
                    $texts,
                    $push->getRepository()->getProject(),
                    $push->getPusher(),
                ),
                $faults
            )
        );
    }

    /**
     * @return Ok<TextWithPotentialReferences> | Err<Fault>
     */
    private function processSingleCommit(\GitRepository $git_repository, CommitHash $commit_hash): Ok|Err
    {
        try {
            $commit_message = $this->message_retriever->getCommitMessage((string) $commit_hash);
        } catch (\Git_Command_Exception $e) {
            return Result::err(
                Fault::fromThrowableWithMessage($e, 'Could not retrieve commit message: ' . $e->getMessage())
            );
        }
        $back_reference = GitCommitReferenceString::fromRepositoryAndCommit($git_repository, $commit_hash);
        return Result::ok(new TextWithPotentialReferences($commit_message, $back_reference));
    }
}
