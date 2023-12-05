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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\Admin;

use Tuleap\SVNCore\Repository;

final class SectionsPresenter
{
    public $notifications;
    public $access_control;
    public $immutable_tag;
    public $notifications_url;
    public $access_control_url;
    public $immutable_tag_url;
    /**
     * @var string
     */
    public $hooks_config;
    /**
     * @var string
     */
    public $repository_delete;
    /**
     * @var string
     */
    public $hooks_config_url;
    /**
     * @var string
     */
    public $repository_delete_url;
    /**
     * @var bool
     */
    public $can_delete;

    public function __construct(Repository $repository)
    {
        $this->notifications     = dgettext('tuleap-svn', 'Notifications');
        $this->access_control    = dgettext('tuleap-svn', 'Access control');
        $this->immutable_tag     = dgettext('tuleap-svn', 'Immutable tags');
        $this->hooks_config      = dgettext('tuleap-svn', 'Commit rules');
        $this->repository_delete = dgettext('tuleap-svn', 'Delete');

        $this->notifications_url     = SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'display-mail-notification',
            'repo_id'  => $repository->getId(),
        ]);
        $this->access_control_url    = SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'access-control',
            'repo_id'  => $repository->getId(),
        ]);
        $this->immutable_tag_url     = SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'display-immutable-tag',
            'repo_id'  => $repository->getId(),
        ]);
        $this->hooks_config_url      = SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'hooks-config',
            'repo_id'  => $repository->getId(),
        ]);
        $this->repository_delete_url = SVN_BASE_URL . '/?' . http_build_query([
            'group_id' => $repository->getProject()->getId(),
            'action'   => 'display-repository-delete',
            'repo_id'  => $repository->getId(),
        ]);

        $this->can_delete = $repository->canBeDeleted();
    }
}
