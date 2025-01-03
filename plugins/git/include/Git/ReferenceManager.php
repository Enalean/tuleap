<?php
/**
 * Copyright Enalean (c) 2013-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Git\Reference\CommitInfoFromReferenceValue;
use Tuleap\Git\Reference\TagInfoFromReferenceValue;
use Tuleap\Git\Reference\ReferenceDao;

/**
 * I'm able to understand cross reference grammar related to git commits and to
 * create Reference objects that correspond to a literal reference.
 */
class Git_ReferenceManager
{
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    /**
     * @var ReferenceDao
     */
    private $reference_dao;

    public function __construct(
        GitRepositoryFactory $repository_factory,
        ReferenceManager $reference_manager,
        ReferenceDao $reference_dao,
    ) {
        $this->repository_factory = $repository_factory;
        $this->reference_manager  = $reference_manager;
        $this->reference_dao      = $reference_dao;
    }

    /**
     * Return a reference that match keyword and value
     */
    public function getCommitReference(Project $project, string $keyword, string $value): ?Reference
    {
        $commit_info = $this->getCommitInfoFromReferenceValue($project, $value);
        if (! $commit_info->getRepository()) {
            return null;
        }

        $args      = [$commit_info->getRepository()->getId(), $commit_info->getSha1()];
        $reference = $this->reference_manager->loadReferenceFromKeywordAndNumArgs($keyword, $project->getID(), count($args), $value);
        if ($reference) {
            $reference->replaceLink($args);
        }

        return $reference;
    }

    public function getTagReference(Project $project, string $keyword, string $value): ?Reference
    {
        $existing_reference_row = $this->reference_dao->searchExistingProjectReference(
            $keyword,
            (int) $project->getID()
        );
        if ($existing_reference_row !== null) {
            //Keep the behaviour of the already existing project reference
            return $this->reference_manager->buildReference(
                $existing_reference_row
            );
        }

        $commit_info = $this->getTagInfoFromReferenceValue($project, $value);
        if (! $commit_info->getRepository()) {
            return null;
        }

        $args      = [$commit_info->getRepository()->getId(), $commit_info->getTagName()];
        $reference = $this->reference_manager->loadReferenceFromKeywordAndNumArgs($keyword, $project->getID(), count($args), $value);
        if ($reference) {
            $reference->replaceLink($args);
        }

        return $reference;
    }

    public function getCommitInfoFromReferenceValue(Project $project, string $value): CommitInfoFromReferenceValue
    {
        [$repository_name, $sha1] = $this->splitRepositoryAndValue($value);

        $repository = $this->repository_factory
            ->getRepositoryByPath($project->getId(), $project->getUnixName() . '/' . $repository_name . '.git');

        return new CommitInfoFromReferenceValue($repository, $sha1);
    }

    private function getTagInfoFromReferenceValue(Project $project, string $value): TagInfoFromReferenceValue
    {
        [$repository_name, $sha1] = $this->splitRepositoryAndValue($value);

        $repository = $this->repository_factory
            ->getRepositoryByPath($project->getId(), $project->getUnixName() . '/' . $repository_name . '.git');

        return new TagInfoFromReferenceValue($repository, $sha1);
    }

    private function splitRepositoryAndValue(string $value): array
    {
        $last_slash_position = strrpos($value, '/');
        $repository_name     = substr($value, 0, $last_slash_position);
        $sha1                = substr($value, $last_slash_position + 1);
        return [$repository_name, $sha1];
    }
}
