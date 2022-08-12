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

final class DefaultBranchPushProcessor
{
    public function __construct(
        private VerifyArtifactClosureIsAllowed $closure_verifier,
        private RetrieveCommitMessage $message_retriever,
    ) {
    }

    /**
     * @return list<Ok<PotentialReferencesReceived> | Err<Fault>>
     */
    public function process(DefaultBranchPushReceived $push): array
    {
        if (! $this->closure_verifier->isArtifactClosureAllowed((int) $push->getRepository()->getId())) {
            return [Result::err(ArtifactClosureNotAllowedFault::build($push->getRepository()))];
        }
        return array_map([$this, 'processSingleCommit'], $push->analyzeCommits());
    }

    /**
     * @return Ok<PotentialReferencesReceived> | Err<Fault>
     */
    private function processSingleCommit(CommitAnalysisOrder $order): Ok|Err
    {
        try {
            $commit_message = $this->message_retriever->getCommitMessage((string) $order->getCommitHash());
        } catch (\Git_Command_Exception $e) {
            return Result::err(
                Fault::fromThrowableWithMessage($e, 'Could not retrieve commit message: ' . $e->getMessage())
            );
        }
        $back_reference = GitCommitReferenceString::fromRepositoryAndCommit(
            $order->getRepository(),
            $order->getCommitHash()
        );
        $project        = $order->getRepository()->getProject();
        return Result::ok(
            new PotentialReferencesReceived($commit_message, $project, $order->getPusher(), $back_reference)
        );
    }
}
