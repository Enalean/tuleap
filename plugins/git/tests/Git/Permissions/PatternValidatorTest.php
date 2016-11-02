<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once dirname(__FILE__) . '/../../bootstrap.php';

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

        $this->repository       = mock('GitRepository');
        $this->regexp_retriever = mock('Tuleap\Git\Permissions\RegexpFineGrainedRetriever');
        $this->regexp_validator = mock('Tuleap\Git\Permissions\FineGrainedRegexpValidator');
        $this->validator        = mock('Tuleap\Git\Permissions\FineGrainedPatternValidator');

        $this->pattern_validator = new PatternValidator(
            $this->validator,
            $this->regexp_validator,
            $this->regexp_retriever
        );
    }

    public function itValidPatternWithRegexpModeWhenRegexpAreActivated()
    {
        stub($this->regexp_retriever)->areRegexpActivatedForRepository()->returns(true);
        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);

        $this->regexp_validator->expectOnce('isPatternValid');
        $this->validator->expectNever('isPatternValid');
    }

    public function itValidPatternWithStandardModeWhenRegexpAreNotAvailable()
    {
        stub($this->regexp_retriever)->areRegexpActivatedForRepository()->returns(false);
        $this->pattern_validator->isValidForRepository($this->repository, 'master', false);

        $this->regexp_validator->expectNever('isPatternValid');
        $this->validator->expectOnce('isPatternValid');
    }

    public function itValidsPatternWithRegexpModeWhenRegexpAreCurrentlyActivated()
    {
        stub($this->regexp_retriever)->areRegexpActivatedForRepository()->returns(false);
        $this->pattern_validator->isValidForRepository($this->repository, 'master', true);

        $this->regexp_validator->expectOnce('isPatternValid');
        $this->validator->expectNever('isPatternValid');
    }
}
