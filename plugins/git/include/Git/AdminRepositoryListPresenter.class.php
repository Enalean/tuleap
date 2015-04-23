<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Git_AdminMRepositoryListPresenter {
    const TEMPLATE = 'admin-plugin-list-repositories';

    private $mirror;

    /** @var Git_AdminRepositoryListForProjectPresenter[] */
    public $repository_list_for_projects;

    /** @var Codendi_HTMLPurifier */
    private $purifier;

    public function __construct($mirror, array $repository_list_for_projects) {
        $this->mirror                       = $mirror;
        $this->repository_list_for_projects = $repository_list_for_projects;
        $this->purifier                     = Codendi_HTMLPurifier::instance();
    }

    public function getTemplate() {
        return self::TEMPLATE;
    }

    public function has_repositories() {
        return count($this->repository_list_for_projects) > 0;
    }

    public function projects() {
        $projects = array();
        foreach($this->repository_list_for_projects as $presenter) {
            $projects[] = array(
                'project_id'   => $presenter->project_id,
                'project_name' => $this->purifier->purify($presenter->project_name),
            );
        }
        return $projects;
    }

    public function base_url() {
        return GIT_BASE_URL;
    }

    public function mirror_back() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_back');
    }

    public function mirror_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_title', array($this->mirror));
    }

    public function no_repositories() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_no_repository');
    }

    public function mirror_repo() {
        return $GLOBALS['Language']->getText('plugin_git', 'mirror_repo');
    }
}