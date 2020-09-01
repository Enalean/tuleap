<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use GitRepository;
use CSRFSynchronizerToken;

class ModalsPresenter
{
    public $jenkins_server_url;
    public $project_id;
    public $repository_id;
    public $jenkins_notification_label;
    public $jenkins_notification_desc;
    public $jenkins_documentation_link_label;
    public $modal_create_jenkins;
    public $modal_edit_jenkins;
    public $csrf_token;
    public $btn_cancel;

    public function __construct(
        GitRepository $repository,
        $jenkins_server_url,
        CSRFSynchronizerToken $csrf
    ) {
        $this->jenkins_server_url = $jenkins_server_url;
        $this->csrf_token         = $csrf->getToken();

        $this->project_id    = $repository->getProjectId();
        $this->repository_id = $repository->getId();

        $this->modal_create_jenkins = new ModalCreatePresenter();
        $this->modal_edit_jenkins   = new ModalEditPresenter();

        $this->jenkins_notification_label       = dgettext('tuleap-hudson_git', 'Jenkins server');
        $this->jenkins_notification_desc        = dgettext('tuleap-hudson_git', 'Jenkins server will be notified about git activity on this repository and will trigger git polling. More details on');
        $this->jenkins_documentation_link_label = dgettext('tuleap-hudson_git', 'Jenkins documentation');

        $this->btn_cancel = $GLOBALS['Language']->getText('global', 'btn_cancel');
    }
}
