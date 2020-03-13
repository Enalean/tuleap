<?php
/**
 * Copyright Enalean (c) 2013-2018. All rights reserved.
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

    public function __construct(GitRepositoryFactory $repository_factory, ReferenceManager $reference_manager)
    {
        $this->repository_factory = $repository_factory;
        $this->reference_manager  = $reference_manager;
    }

    /**
     * Return a reference that match keyword and value
     * @param String $keyword
     * @param String $value
     * @return Reference
     */
    public function getReference(Project $project, $keyword, $value)
    {
        $reference = false;
        list($repository_name, $sha1) = $this->splitRepositoryAndSha1($value);
        $repository = $this->repository_factory->getRepositoryByPath($project->getId(), $project->getUnixName() . '/' . $repository_name . '.git');
        if ($repository) {
            $args = array($repository->getId(), $sha1);
            $reference = $this->reference_manager->loadReferenceFromKeywordAndNumArgs($keyword, $project->getID(), count($args), $value);
            if ($reference) {
                $reference->replaceLink($args);
            }
        }
        return $reference;
    }

    private function splitRepositoryAndSha1($value)
    {
        $last_slash_position  = strrpos($value, '/');
        $repository_name      = substr($value, 0, $last_slash_position);
        $sha1                 = substr($value, $last_slash_position + 1);
        return array($repository_name, $sha1);
    }
}
