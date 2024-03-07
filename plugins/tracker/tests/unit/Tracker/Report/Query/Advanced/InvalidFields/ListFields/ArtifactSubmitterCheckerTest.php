<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Test\Builders\Fields\SubmittedByFieldBuilder;

final class ArtifactSubmitterCheckerTest extends TestCase
{
    private const VALID_USERNAME   = 'user_1';
    private const INVALID_USERNAME = 'fake_user';
    private Stub & \UserManager $user_manager;

    protected function setUp(): void
    {
        $this->user_manager = $this->createStub(\UserManager::class);
    }

    /**
     * @throws ListToMySelfForAnonymousComparisonException
     * @throws ListToNowComparisonException
     * @throws ListToStatusOpenComparisonException
     * @throws SubmittedByUserDoesntExistException
     */
    private function check(Comparison $comparison): void
    {
        $field = SubmittedByFieldBuilder::aSubmittedByField(992)->build();

        $artifact_submitter_checker = new ArtifactSubmitterChecker(
            $this->user_manager
        );
        $artifact_submitter_checker->checkFieldIsValidForComparison($comparison, $field);
    }

    public function testItThrowsExceptionWhenSearchedUserIsNotFound(): void
    {
        $this->user_manager->method('getUserByLoginName')->willReturnCallback(
            static fn(string $username): ?\PFUser => match ($username) {
                self::VALID_USERNAME   => UserTestBuilder::aUser()->build(),
                self::INVALID_USERNAME => null
            }
        );

        $this->expectException(SubmittedByUserDoesntExistException::class);
        $this->check(
            new InComparison(
                new Field('submitted_by'),
                new InValueWrapper([
                    new SimpleValueWrapper(self::VALID_USERNAME),
                    new SimpleValueWrapper(self::INVALID_USERNAME),
                ]),
            )
        );
    }

    public function testItIgnoresEmptyString(): void
    {
        $this->user_manager->method('getUserByLoginName')->willReturn(UserTestBuilder::buildWithDefaults());

        $this->expectNotToPerformAssertions();
        $this->check(
            new EqualComparison(
                new Field('submitted_by'),
                new InValueWrapper([
                    new SimpleValueWrapper(''),
                ])
            )
        );
    }

    public function testItAllowsValidUsername(): void
    {
        $this->user_manager->method('getUserByLoginName')->willReturn(UserTestBuilder::buildWithDefaults());

        $this->expectNotToPerformAssertions();
        $this->check(
            new EqualComparison(new Field('submitted_by'), new SimpleValueWrapper(self::VALID_USERNAME))
        );
    }
}
