<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Migration;

use PFUser;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryCreator;

class BareRepositoryCreator
{
    /**
     * @var RepositoryCreator
     */
    private $repository_creator;
    /**
     * @var SettingsRetriever
     */
    private $settings_retriever;

    public function __construct(
        RepositoryCreator $repository_creator,
        SettingsRetriever $settings_retriever,
    ) {
        $this->repository_creator = $repository_creator;
        $this->settings_retriever = $settings_retriever;
    }

    /**
     * @throws SvnMigratorException
     * @throws \Tuleap\SVN\Repository\Exception\CannotCreateRepositoryException
     * @throws \Tuleap\SVN\Repository\Exception\UserIsNotSVNAdministratorException
     */
    public function create(Repository $repository, PFUser $user): void
    {
        try {
            $settings = $this->settings_retriever->getSettingsFromCoreRepository($repository);

            $this->repository_creator->importCoreRepository(
                $repository,
                $user,
                $settings
            );
        } catch (RepositoryNameIsInvalidException $e) {
            throw new SvnMigratorException(sprintf(dgettext('tuleap-svn', 'Repository name `%s` is already used in this project'), $repository->getName()));
        }
    }
}
