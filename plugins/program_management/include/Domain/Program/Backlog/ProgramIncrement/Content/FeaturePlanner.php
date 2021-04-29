<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\VerifyLinkedUserStoryIsNotPlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogStore;

final class FeaturePlanner
{
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var VerifyLinkedUserStoryIsNotPlanned
     */
    private $story_verifier;
    /**
     * @var RemoveFeature
     */
    private $feature_remover;
    /**
     * @var TopBacklogStore
     */
    private $top_backlog_store;
    /**
     * @var AddFeature
     */
    private $feature_adder;

    public function __construct(
        DBTransactionExecutor $transaction_executor,
        VerifyLinkedUserStoryIsNotPlanned $story_verifier,
        RemoveFeature $feature_remover,
        TopBacklogStore $top_backlog_store,
        AddFeature $feature_adder
    ) {
        $this->transaction_executor = $transaction_executor;
        $this->story_verifier       = $story_verifier;
        $this->feature_remover      = $feature_remover;
        $this->top_backlog_store    = $top_backlog_store;
        $this->feature_adder        = $feature_adder;
    }

    /**
     * @throws FeatureHasPlannedUserStoryException
     * @throws AddFeatureException
     * @throws RemoveFeatureException
     * @throws ProgramIncrementNotFoundException
     */
    public function plan(FeatureAddition $feature_addition): void
    {
        $this->transaction_executor->execute(
            function () use ($feature_addition) {
                $removal = FeatureRemoval::fromFeature(
                    $this->story_verifier,
                    $feature_addition->feature,
                    $feature_addition->user,
                );
                $this->feature_remover->removeFromAllProgramIncrements($removal);
                $this->top_backlog_store->removeArtifactsFromExplicitTopBacklog([$feature_addition->feature->id]);
                $this->feature_adder->add($feature_addition);
            }
        );
    }
}
