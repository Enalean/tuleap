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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\SVN\Reference;

use Project;
use SvnPlugin;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RuleName;

class Extractor
{

    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    public function __construct(RepositoryManager $repository_manager)
    {
        $this->repository_manager = $repository_manager;
    }

    /**
     * @return false|Reference
     */
    public function getReference(Project $project, string $keyword, string $value)
    {
        if (! $project->usesService(SvnPlugin::SERVICE_SHORTNAME)) {
            return false;
        }

        if (ctype_digit($value)) {
            try {
                $repository = $this->repository_manager->getCoreRepository($project);
                return new Reference($project, $repository, $keyword, (int) $value);
            } catch (CannotFindRepositoryException $exception) {
                return false;
            }
        }

        $matches = [];
        if (! preg_match($this->getRegExp(), $value, $matches)) {
            return false;
        }

        $repository_name = $matches[1];
        $revision_id     = $matches[2];

        try {
            $repository = $this->repository_manager->getRepositoryByName($project, $repository_name);
        } catch (CannotFindRepositoryException $exception) {
            return false;
        }

        return new Reference($project, $repository, $keyword, $revision_id);
    }

    private function getRegExp()
    {
        return '#^(' . RuleName::PATTERN_REPOSITORY_NAME . ')/([0-9]+)$#';
    }
}
