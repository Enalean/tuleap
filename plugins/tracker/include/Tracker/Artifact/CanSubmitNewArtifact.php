<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use PFUser;
use Tracker;
use Tuleap\Event\Dispatchable;

final class CanSubmitNewArtifact implements Dispatchable
{
    public const NAME = 'canSubmitNewArtifact';

    /**
     * @psalm-readonly
     */
    private PFUser $user;
    /**
     * @psalm-readonly
     */
    private Tracker $tracker;
    private bool $can_submit_new_artifact = true;
    /**
     * @var string[]
     */
    private array $error_messages = [];
    private bool $should_collect_all_issues;

    public function __construct(PFUser $user, Tracker $tracker, bool $should_collect_all_issues)
    {
        $this->user                      = $user;
        $this->tracker                   = $tracker;
        $this->should_collect_all_issues = $should_collect_all_issues;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function canSubmitNewArtifact(): bool
    {
        return $this->can_submit_new_artifact;
    }

    public function disableArtifactSubmission(): void
    {
        $this->can_submit_new_artifact = false;
    }

    /**
     * @param string[] $error_message
     */
    public function addErrorMessage(array $error_message): void
    {
        $this->error_messages = $error_message;
    }

    public function getErrorMessages(): array
    {
        return $this->error_messages;
    }

    public function shouldCollectAllIssues(): bool
    {
        return $this->should_collect_all_issues;
    }
}
