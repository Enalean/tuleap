<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class GitPresenters_MirroringPresenter
{

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var GitPresenters_MirrorPresenter[]
     */
    public $mirror_presenters;

    public function __construct(GitRepository $repository, array $mirror_presenters)
    {
        $this->repository        = $repository;
        $this->mirror_presenters = $mirror_presenters;
    }

    public function repository_id()
    {
        return $this->repository->getId();
    }

    public function project_id()
    {
        return $this->repository->getProjectId();
    }

    public function mirroring_title()
    {
        return dgettext('tuleap-git', 'Mirroring');
    }

    public function mirroring_info()
    {
        return sprintf(dgettext('tuleap-git', 'Select the mirrors where you want to replicate the repository <b>%1$s</b>:'), $this->repository->getName());
    }

    public function mirroring_mirror_name()
    {
        return dgettext('tuleap-git', 'Name');
    }

    public function mirroring_mirror_url()
    {
        return dgettext('tuleap-git', 'Identifier');
    }

    public function mirroring_mirror_used()
    {
        return dgettext('tuleap-git', 'Used by this repository?');
    }

    public function mirroring_update_mirroring()
    {
        return dgettext('tuleap-git', 'Update mirroring of this repository');
    }
}
