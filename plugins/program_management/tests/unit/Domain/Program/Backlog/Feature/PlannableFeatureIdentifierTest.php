<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;
use Tuleap\Test\PHPUnit\TestCase;

final class PlannableFeatureIdentifierTest extends TestCase
{
    private RetrieveTrackerOfArtifactStub $retrieve_tracker;
    private FeatureIdentifier $feature_identifier;

    protected function setUp(): void
    {
        $this->retrieve_tracker   = RetrieveTrackerOfArtifactStub::withIds(1);
        $this->feature_identifier = FeatureIdentifierBuilder::withId(100);
    }

    public function testItThrowExceptionWhenFeatureIsNotPlannable(): void
    {
        $verify_is_plannable = VerifyIsPlannableStub::buildNotPlannableElement();
        $this->expectException(FeatureIsNotPlannableException::class);

        PlannableFeatureIdentifier::build($verify_is_plannable, $this->retrieve_tracker, $this->feature_identifier);
    }

    public function testItBuildFeature(): void
    {
        $verify_is_plannable = VerifyIsPlannableStub::buildPlannableElement();

        self::assertSame(
            $this->feature_identifier->getId(),
            PlannableFeatureIdentifier::build($verify_is_plannable, $this->retrieve_tracker, $this->feature_identifier)->getId()
        );
    }
}
