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

namespace Tuleap\OAuth2Server\Administration;

/**
 * @psalm-immutable
 */
final class AdminOAuth2AppsPresenter
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
     * @var string
     * @psalm-readonly
     */
    public $generate_new_secret_url;
    /**
     * @var string
     * @psalm-readonly
     */
    public $edit_client_url;
    /**
     * @var LastCreatedOAuth2AppPresenter|null
     * @psalm-readonly
     */
    public $last_created_app;

    /**
     * @param $apps AppPresenter[]
     */
    private function __construct(
        array $apps,
        \CSRFSynchronizerToken $csrf_token,
        string $add_client_url,
        string $delete_client_url,
        string $generate_new_secret_url,
        string $edit_client_url,
        ?LastCreatedOAuth2AppPresenter $last_created_app
    ) {
        $this->apps                    = $apps;
        $this->csrf_token              = $csrf_token;
        $this->add_client_url          = $add_client_url;
        $this->delete_client_url       = $delete_client_url;
        $this->generate_new_secret_url = $generate_new_secret_url;
        $this->edit_client_url         = $edit_client_url;
        $this->last_created_app        = $last_created_app;
    }

    /**
     * @param $apps AppPresenter[]
     */
    public static function forProjectAdministration(
        \Project $project,
        array $apps,
        \CSRFSynchronizerToken $csrf_token,
        ?LastCreatedOAuth2AppPresenter $last_created_app
    ): self {
        return new self(
            $apps,
            $csrf_token,
            AddAppController::getProjectAdminURL($project),
            DeleteAppController::getProjectAdminURL($project),
            NewClientSecretController::getProjectAdminURL($project),
            EditAppController::getProjectAdminURL($project),
            $last_created_app
        );
    }

    /**
     * @param $apps AppPresenter[]
     */
    public static function forSiteAdministration(
        array $apps,
        \CSRFSynchronizerToken $csrf_token,
        ?LastCreatedOAuth2AppPresenter $last_created_app
    ): self {
        return new self(
            $apps,
            $csrf_token,
            '/plugins/oauth2_server/admin/add-app',
            '/plugins/oauth2_server/admin/delete-app',
            '/plugins/oauth2_server/admin/new-client-secret',
            '/plugins/oauth2_server/admin/edit-app',
            $last_created_app
        );
    }
}
