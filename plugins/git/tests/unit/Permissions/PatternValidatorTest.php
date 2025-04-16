<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PatternValidatorTest extends TestCase
{
    private RegexpFineGrainedRetriever&MockObject $regexp_retriever;
    private PatternValidator $pattern_validator;
    private GitRepository $repository;
    private FineGrainedRegexpValidator&MockObject $regexp_validator;
    private FineGrainedPatternValidator&MockObject $validator;

    protected function setUp(): void
    {
        $this->repository       = GitRepositoryTestBuilder::aProjectRepository()->inProject(ProjectTestBuilder::aProject()->build())->build();
        $this->regexp_retriever = $this->createMock(RegexpFineGrainedRetriever::class);
        $this->regexp_validator = $this->createMock(FineGrainedRegexpValidator::class);
        $this->validator        = $this->createMock(FineGrainedPatternValidator::class);

        $this->pattern_validator = new PatternValidator(
            $this->validator,
            $this->regexp_validator,
            $this->regexp_retriever
        );
    }

    public function testItValidPatternForRepositoryWithRegexpModeWhenRegexpAreActivated(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(true);
        $this->regexp_retriever->method('areRegexpRepositoryConflitingWithPlateform');

        $this->regexp_validator->expects($this->once())->method('isPatternValid');
        $this->validator->expects($this->never())->method('isPatternValid');

        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);
    }

    public function testItValidPatternForRepositoryWithStandardModeWhenRegexpAreNotAvailable(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);

        $this->regexp_validator->expects($this->never())->method('isPatternValid');
        $this->validator->expects($this->once())->method('isPatternValid');

        $this->pattern_validator->isValidForRepository($this->repository, 'master', false);
    }

    public function testItValidsPatternForRepositoryWithRegexpModeWhenRegexpAreCurrentlyActivated(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(false);
        $this->regexp_retriever->method('areRegexpRepositoryConflitingWithPlateform');

        $this->regexp_validator->expects($this->once())->method('isPatternValid');
        $this->validator->expects($this->never())->method('isPatternValid');

        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);
    }

    public function testItValidsPatternForRepositoryWithStandardModeWhenRegexpAreConflictingWithPlateform(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForRepository')->willReturn(true);
        $this->regexp_retriever->method('areRegexpRepositoryConflitingWithPlateform')->willReturn(true);

        $this->regexp_validator->expects($this->never())->method('isPatternValid');
        $this->validator->expects($this->once())->method('isPatternValid');

        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);
    }

    public function testItValidPatternForDefaultWithRegexpModeWhenRegexpAreActivated(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForDefault')->willReturn(true);
        $this->regexp_retriever->method('areDefaultRegexpConflitingWithPlateform');

        $this->regexp_validator->expects($this->once())->method('isPatternValid');
        $this->validator->expects($this->never())->method('isPatternValid');

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', true);
    }

    public function testItValidPatternForDefaultWithStandardModeWhenRegexpAreNotAvailable(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForDefault')->willReturn(false);

        $this->regexp_validator->expects($this->never())->method('isPatternValid');
        $this->validator->expects($this->once())->method('isPatternValid');

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', false);
    }

    public function testItValidsPatternForDefaultWithRegexpModeWhenRegexpAreCurrentlyActivated(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForDefault')->willReturn(false);
        $this->regexp_retriever->method('areDefaultRegexpConflitingWithPlateform');

        $this->regexp_validator->expects($this->once())->method('isPatternValid');
        $this->validator->expects($this->never())->method('isPatternValid');

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', true);
    }

    public function testItValidsPatternForDefaultWithStandardModeWhenRegexpAreConflictingWithPlateform(): void
    {
        $this->regexp_retriever->method('areRegexpActivatedForDefault')->willReturn(true);
        $this->regexp_retriever->method('areDefaultRegexpConflitingWithPlateform')->willReturn(true);

        $this->regexp_validator->expects($this->never())->method('isPatternValid');
        $this->validator->expects($this->once())->method('isPatternValid');

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', true);
    }
}
