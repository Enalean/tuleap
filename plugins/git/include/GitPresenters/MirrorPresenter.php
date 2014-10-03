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

class GitPresenters_MirrorPresenter {

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var Git_Mirror_Mirror[]
     */
    public $mirrors;

    public function __construct(GitRepository $repository, $mirrors) {
        $this->repository = $repository;
        $this->mirrors    = $mirrors;
    }

    public function repository_id() {
        return $this->repository->getId();
    }

    public function repository_is_mirrored() {
        return $this->repository->getIsMirrored();
    }

    public function project_id() {
        return $this->repository->getProjectId();
    }

    public function mirroring_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_title');
    }

    public function mirroring_info() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_info', array($this->repository->getName()));
    }

    public function mirroring_mirror() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_mirror');
    }

    public function mirroring_unmirror() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirroring_unmirror');
    }

}
