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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigurationErrorsCollectorTest extends TestCase
{
    private ConfigurationErrorsCollector $collector;

    #[\Override]
    protected function setUp(): void
    {
        $this->collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
    }

    public function testItHasErrorWhenASemanticIsIncorrect(): void
    {
        $this->collector->addSemanticError(
            'Title',
            'title',
            [TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2), TrackerReferenceStub::withId(3)]
        );
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenAFieldIsRequired(): void
    {
        $this->collector->addRequiredFieldError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric(), 100, 'My field');
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenWorkFlowHasTransition(): void
    {
        $this->collector->addWorkflowTransitionRulesError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenWorkFlowHasGlobalRules(): void
    {
        $this->collector->addWorkflowTransitionDateRulesError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenWorkFlowHasFieldDependency(): void
    {
        $this->collector->addWorkflowDependencyError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenFieldIsNotSubmittable(): void
    {
        $this->collector->addSubmitFieldPermissionError(100, 'My custom field', TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenFieldIsNotUpdatable(): void
    {
        $this->collector->addUpdateFieldPermissionError(100, 'My custom field', TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenUserCanNotEditTeams(): void
    {
        $this->collector->userCanNotSubmitInTeam(TrackerReferenceStub::withId(200));
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenStatusIsNotSetInTeams(): void
    {
        $this->collector->addMissingSemanticInTeamErrors([TrackerReferenceStub::withId(1)]);
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenStatusFieldIsNotSet(): void
    {
        $this->collector->addSemanticNoStatusFieldError(TrackerReferenceStub::withId(1));
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenStatusHasMissingValues(): void
    {
        $this->collector->addMissingValueInSemantic(['Planned', 'On going'], [TrackerReferenceStub::withId(1), TrackerReferenceStub::withId(2)]);
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenNoMilestonePlanningIsFound(): void
    {
        $this->collector->addTeamMilestonePlanningNotFoundOrNotAccessible(ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItHasErrorWhenNoSprintPlanningIsFound(): void
    {
        $this->collector->addTeamSprintPlanningNotFoundOrNotAccessible(ProjectReferenceStub::buildGeneric());
        self::assertTrue($this->collector->hasError());
        self::assertCount(1, $this->collector->getTeamsWithError());
    }

    public function testItDoesNotHaveAnyError(): void
    {
        self::assertFalse($this->collector->hasError());
        self::assertCount(0, $this->collector->getTeamsWithError());
    }
}
