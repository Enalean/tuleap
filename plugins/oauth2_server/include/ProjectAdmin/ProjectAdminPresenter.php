<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OAuth2Server\ProjectAdmin;

final class ProjectAdminPresenter
{
    /**
     * @var AppPresenter[]
     * @psalm-readonly
     */
    public $apps;
    /**
     * @var \CSRFSynchronizerToken
     * @psalm-readonly
     */
    public $csrf_token;
    /**
     * @var string
     * @psalm-readonly
     */
    public $add_client_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $delete_client_url;
    /**
     * @var LastCreatedOAuth2AppPresenter|null
     * @psalm-readonly
     */
    public $last_created_app;

    /**
     * @param $apps AppPresenter[]
     */
    public function __construct(
        array $apps,
        \CSRFSynchronizerToken $csrf_token,
        \Project $project,
        ?LastCreatedOAuth2AppPresenter $last_created_app
    ) {
        $this->apps              = $apps;
        $this->csrf_token        = $csrf_token;
        $this->add_client_url    = AddAppController::getUrl($project);
        $this->delete_client_url = DeleteAppController::getUrl($project);
        $this->last_created_app  = $last_created_app;
    }
}
