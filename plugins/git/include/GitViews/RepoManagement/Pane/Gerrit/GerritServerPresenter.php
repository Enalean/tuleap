<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane\Gerrit;

use Git_RemoteServer_GerritServer;

final readonly class GerritServerPresenter
{
    public int $server_id;
    public string $base_url;

    public function __construct(
        Git_RemoteServer_GerritServer $server,
        public int $is_delete_plugin_enabled,
        public int $should_delete,
    ) {
        $this->server_id = (int) $server->getId();
        $this->base_url  = $server->getBaseUrl();
    }
}
