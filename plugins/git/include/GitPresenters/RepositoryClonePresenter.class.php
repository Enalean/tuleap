<?php

/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class RepositoryClonePresenter {

    public $mirrors;

    public $user_is_admin;

    public $settings_text;

    public $how_to_use_text;

    public $ssh_mirrors_text;

    public $clone_text;

    public $add_remote_text;

    public $repository_name;

    public $master_location_name;

    public $additional_actions;

    /** @var GitRepository */
    private $repository;

    /** @var array */
    private $urls;

    public function __construct(
        GitRepository $repository,
        array $urls,
        array $mirrors,
        $user_is_admin,
        $master_location_name,
        $additional_actions
    ) {
        $this->repository    = $repository;
        $this->urls          = $urls;
        $this->mirrors       = $mirrors;
        $this->user_is_admin = $user_is_admin;

        $this->settings_text    = $GLOBALS['Language']->getText('global', 'Settings');
        $this->how_to_use_text  = $GLOBALS['Language']->getText('plugin_git', 'how_to_use_text');
        $this->clone_text       = $GLOBALS['Language']->getText('plugin_git', 'clone_text');
        $this->add_remote_text  = $GLOBALS['Language']->getText('plugin_git', 'add_remote_text');
        $this->ssh_mirrors_text = $GLOBALS['Language']->getText('plugin_git', 'ssh_mirrors_text');
        $this->repository_name  = $repository->getName();

        $this->master_location_name = $master_location_name;
        $this->additional_actions   = $additional_actions;
    }

    public function getTemplateName() {
        return 'repository_clone';
    }

    public function gerrit_url() {
        if (isset($this->urls['gerrit'])) {
            return $this->urls['gerrit'];
        }

        return '';
    }

    public function default_ssh_url() {
        if (isset($this->urls['ssh'])) {
            return $this->urls['ssh'];
        }

        return '';
    }

    public function default_http_url() {
        if (isset($this->urls['http'])) {
            return $this->urls['http'];
        }

        return '';
    }

    public function is_gerrit_active() {
        return (isset($this->urls['gerrit']));
    }

    public function display_gerrit() {
        return (isset($this->urls['gerrit'])) ? '' : 'display:none;';
    }

    public function display_ssh() {
        return (isset($this->urls['ssh'])) ? '' : 'display:none;';
    }

    public function display_http() {
        return (isset($this->urls['http'])) ? '' : 'display:none;';
    }

    public function mirrors_exist() {
        return count($this->mirrors) > 0 && $this->is_ssh_active();
    }

    private function is_ssh_active() {
        return in_array('ssh', array_keys($this->urls));
    }

    public function mirrors() {
        $mirrors = array();
        $mirrors[] = $this->getDefaultDomain();

        foreach ($this->mirrors as $mirror) {
            $mirrors[] = array(
                'ssh_url'    => $this->repository->getSSHForMirror($mirror),
                'name'       => $mirror->name,
                'http_url'   => '',
                'gerrit_url' => '',
                'is_mirror'  => true
            );
        }

        return $mirrors;
    }

    public function has_mirrors() {
        return count($this->mirrors) > 0;
    }

    private function getDefaultDomain() {
        $default = array(
            'name'       => $this->master_location_name,
            'is_mirror'  => false,
            'http_url'   => '',
            'ssh_url'    => '',
            'gerrit_url' => '',
        );

        if (isset($this->urls['ssh'])) {
            $default['ssh_url'] = $this->urls['ssh'];
        }

        if (isset($this->urls['http'])) {
            $default['http_url'] = $this->urls['http'];
        }

        if (isset($this->urls['gerrit'])) {
            $default['gerrit_url'] = $this->urls['gerrit'];
        }

        return $default;
    }

    public function getRepoAdminUrl() {
        return '/plugins/git/?action=repo_management&group_id='.
                $this->repository->getProjectId().
                '&repo_id='.
                $this->repository->getId();
    }

    public function get_default_url() {
        return $this->getFirstEntry($this->urls);
    }

    private function getFirstEntry(array $urls) {
        reset($urls);

        return current($urls);
    }
}
