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

namespace unit\Tracker\Report\Query\Advanced\InvalidFields;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\ArtifactSubmitterChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields\SubmittedByUserDoesntExistException;

class ArtifactSubmitterCheckerTest extends TestCase
{
    private Stub|\UserManager $user_manager;
    private Stub|CollectionOfListValuesExtractor $values_extractor;
    private ArtifactSubmitterChecker $artifact_submitter_checker;
    private \Tracker_FormElement_Field_SubmittedBy $field;
    private Stub|Comparison $comparison;
    private Stub|ValueWrapper $wrapper;

    protected function setUp(): void
    {
        $this->wrapper    = $this->createStub(ValueWrapper::class);
        $this->comparison = $this->createStub(Comparison::class);
        $this->comparison->method('getValueWrapper')->willReturn($this->wrapper);

        $this->field = new \Tracker_FormElement_Field_SubmittedBy(
            1,
            101,
            null,
            'Submitted by',
            'submitted_by',
            null,
            true,
            null,
            null,
            null,
            null,
            null
        );

        $this->user_manager               = $this->createStub(\UserManager::class);
        $this->values_extractor           = $this->createStub(CollectionOfListValuesExtractor::class);
        $this->artifact_submitter_checker = new ArtifactSubmitterChecker($this->values_extractor, $this->user_manager);
    }

    public function testItThrowExceptionWhenUserDoesntExist(): void
    {
        $this->values_extractor->method("extractCollectionOfValues")
            ->with($this->wrapper, $this->field)
            ->willReturn(['user_1', '', 'fake_user']);

        $this->user_manager->method('getUserByLoginName')->willReturnCallback(
            fn (string $username): ?\PFUser => match ($username) {
                "user_1" => UserTestBuilder::aUser()->build(),
                "fake_user" => null
            }
        );

        $this->expectException(SubmittedByUserDoesntExistException::class);

        $this->artifact_submitter_checker->checkFieldIsValidForComparison($this->comparison, $this->field);
    }
}
