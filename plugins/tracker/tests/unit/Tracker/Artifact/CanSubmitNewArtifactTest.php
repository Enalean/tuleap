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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;

final class CanSubmitNewArtifactTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAllowArtifactSubmissionByDefault(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $tracker             = \Mockery::mock(\Tracker::class);
        $can_submit_artifact = new CanSubmitNewArtifact($user, $tracker);

        $this->assertSame($user, $can_submit_artifact->getUser());
        $this->assertSame($tracker, $can_submit_artifact->getTracker());
        $this->assertTrue($can_submit_artifact->canSubmitNewArtifact());
        $can_submit_artifact->disableArtifactSubmission();
        $this->assertFalse($can_submit_artifact->canSubmitNewArtifact());
    }
}
