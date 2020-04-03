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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
namespace Tuleap\Git\Permissions;

use GitRepository;
use Project;

class PatternValidator
{
    /**
     * @var FineGrainedPatternValidator
     */
    private $validator;

    /**
     * @var FineGrainedRegexpValidator
     */
    private $regexp_validator;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    public function __construct(
        FineGrainedPatternValidator $validator,
        FineGrainedRegexpValidator $regexp_validator,
        RegexpFineGrainedRetriever $regexp_retriever
    ) {
        $this->validator        = $validator;
        $this->regexp_validator = $regexp_validator;
        $this->regexp_retriever = $regexp_retriever;
    }

    public function isValidForRepository(GitRepository $repository, $pattern, $are_we_activating_regexp)
    {
        if (
            ($this->regexp_retriever->areRegexpActivatedForRepository($repository) || $are_we_activating_regexp)
            && ! $this->regexp_retriever->areRegexpRepositoryConflitingWithPlateform($repository)
        ) {
            return $this->regexp_validator->isPatternValid($pattern);
        }

        return $this->validator->isPatternValid($pattern);
    }

    public function isValidForDefault(Project $project, $pattern, $are_we_activating_regexp)
    {
        if (
            ($this->regexp_retriever->areRegexpActivatedForDefault($project) || $are_we_activating_regexp)
            && ! $this->regexp_retriever->areDefaultRegexpConflitingWithPlateform($project)
        ) {
            return $this->regexp_validator->isPatternValid($pattern);
        }

        return $this->validator->isPatternValid($pattern);
    }

    public function isValid($pattern)
    {
        return $this->validator->isPatternValid($pattern);
    }
}
