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

namespace Tuleap\Git\CIBuilds;

use CSRFSynchronizerToken;
use Tuleap\Git\GitViews\RepoManagement\Pane\CIBuilds;

/**
 * @psalm-immutable
 */
final class CIBuildsPanePresenter
{
    /**
     * @var string|null
     */
    public $ci_token;
    /**
     * @var int
     */
    public $repository_id;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $form_action_url;
    /**
     * @var array
     */
    public $set_build_status_options;

    public function __construct(
        int $repository_id,
        int $project_id,
        array $set_build_status_options,
        ?string $ci_token,
    ) {
        $this->ci_token                 = $ci_token;
        $this->repository_id            = $repository_id;
        $this->form_action_url          = GIT_BASE_URL . '/?' . http_build_query([
            'group_id' => $project_id,
            'pane' => CIBuilds::ID,
        ]);
        $this->csrf_token               = new CSRFSynchronizerToken($this->form_action_url);
        $this->set_build_status_options = $set_build_status_options;
    }
}
