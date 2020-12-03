<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Project;
use Reference;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;

class GitlabCommitReferenceBuilder
{
    /**
     * @var ReferenceDao
     */
    private $reference_dao;

    /**
     * @var GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    public function __construct(ReferenceDao $reference_dao, GitlabRepositoryFactory $gitlab_repository_factory)
    {
        $this->reference_dao             = $reference_dao;
        $this->gitlab_repository_factory = $gitlab_repository_factory;
    }

    public function buildGitlabCommitReference(Project $project, string $keyword, string $value): ?Reference
    {
        if ($keyword !== 'gitlab_commit') {
            return null;
        }

        if ($this->reference_dao->isAProjectReferenceExisting($keyword, (int) $project->getID())) {
            //Keep the behaviour of the already existing project reference
            return null;
        }

        list($repository_name, $sha1) = $this->splitRepositoryAndSha1($value);

        if ($repository_name === null || $sha1 === null) {
            return null;
        }

        $repository = $this->gitlab_repository_factory->getGitlabRepositoryByNameInProject(
            $project,
            $repository_name
        );

        if ($repository === null) {
            return null;
        }

        return new GitlabCommitReference(
            $repository,
            $project,
            $sha1
        );
    }

    private function splitRepositoryAndSha1(string $value): array
    {
        $repository_name = null;
        $sha1            = null;

        $last_slash_position  = strrpos($value, '/');
        if ($last_slash_position !== false) {
            $repository_name      = substr($value, 0, $last_slash_position);
            $sha1                 = substr($value, $last_slash_position + 1);
        }

        return [$repository_name, $sha1];
    }
}
