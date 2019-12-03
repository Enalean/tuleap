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

namespace Tuleap\Git\Permissions;

use TuleapTestCase;
use GitRepository;

require_once __DIR__ . '/../../bootstrap.php';

class PatternValidatorTest extends TuleapTestCase
{
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var PatternValidator
     */
    private $pattern_validator;

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var FineGrainedRegexpValidator
     */
    private $regexp_validator;

    /**
     * @var FineGrainedPatternValidator
     */
    private $validator;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->repository       = \Mockery::spy(\GitRepository::class);
        $this->regexp_retriever = \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class);
        $this->regexp_validator = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRegexpValidator::class);
        $this->validator        = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPatternValidator::class);

        $this->pattern_validator = new PatternValidator(
            $this->validator,
            $this->regexp_validator,
            $this->regexp_retriever
        );

        $this->repository->shouldReceive('getProject')->andReturns(\Mockery::spy(\Project::class));
    }

    public function itValidPatternForRepositoryWithRegexpModeWhenRegexpAreActivated()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForRepository')->andReturns(true);

        $this->regexp_validator->shouldReceive('isPatternValid')->once();
        $this->validator->shouldReceive('isPatternValid')->never();

        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);
    }

    public function itValidPatternForRepositoryWithStandardModeWhenRegexpAreNotAvailable()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForRepository')->andReturns(false);

        $this->regexp_validator->shouldReceive('isPatternValid')->never();
        $this->validator->shouldReceive('isPatternValid')->once();

        $this->pattern_validator->isValidForRepository($this->repository, 'master', false);
    }

    public function itValidsPatternForRepositoryWithRegexpModeWhenRegexpAreCurrentlyActivated()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForRepository')->andReturns(false);

        $this->regexp_validator->shouldReceive('isPatternValid')->once();
        $this->validator->shouldReceive('isPatternValid')->never();

        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);
    }

    public function itValidsPatternForRepositoryWithStandardModeWhenRegexpAreConflictingWithPlateform()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForRepository')->andReturns(true);
        $this->regexp_retriever->shouldReceive('areRegexpRepositoryConflitingWithPlateform')->andReturns(true);

        $this->regexp_validator->shouldReceive('isPatternValid')->never();
        $this->validator->shouldReceive('isPatternValid')->once();

        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);
    }

    public function itValidPatternForDefaultWithRegexpModeWhenRegexpAreActivated()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForDefault')->andReturns(true);

        $this->regexp_validator->shouldReceive('isPatternValid')->once();
        $this->validator->shouldReceive('isPatternValid')->never();

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', true);
    }

    public function itValidPatternForDefaultWithStandardModeWhenRegexpAreNotAvailable()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForDefault')->andReturns(false);

        $this->regexp_validator->shouldReceive('isPatternValid')->never();
        $this->validator->shouldReceive('isPatternValid')->once();

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', false);
    }

    public function itValidsPatternForDefaultWithRegexpModeWhenRegexpAreCurrentlyActivated()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForDefault')->andReturns(false);

        $this->regexp_validator->shouldReceive('isPatternValid')->once();
        $this->validator->shouldReceive('isPatternValid')->never();

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', true);
    }

    public function itValidsPatternForDefaultWithStandardModeWhenRegexpAreConflictingWithPlateform()
    {
        $this->regexp_retriever->shouldReceive('areRegexpActivatedForDefault')->andReturns(true);
        $this->regexp_retriever->shouldReceive('areDefaultRegexpConflitingWithPlateform')->andReturns(true);

        $this->regexp_validator->shouldReceive('isPatternValid')->never();
        $this->validator->shouldReceive('isPatternValid')->once();

        $this->pattern_validator->isValidForDefault($this->repository->getProject(), 'master', true);
    }
}
