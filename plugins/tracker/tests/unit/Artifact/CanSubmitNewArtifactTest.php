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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanSubmitNewArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CanSubmitNewArtifact $can_submit_artifact;
    private \PFUser $user;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->user                = UserTestBuilder::aUser()->build();
        $this->tracker             = TrackerTestBuilder::aTracker()->build();
        $this->can_submit_artifact = new CanSubmitNewArtifact($this->user, $this->tracker);
    }

    public function testAllowArtifactSubmissionByDefault(): void
    {
        self::assertSame($this->user, $this->can_submit_artifact->getUser());
        self::assertSame($this->tracker, $this->can_submit_artifact->getTracker());
        $this->assertTrue($this->can_submit_artifact->canSubmitNewArtifact());
        $this->can_submit_artifact->disableArtifactSubmission();
        $this->assertFalse($this->can_submit_artifact->canSubmitNewArtifact());
    }
}
