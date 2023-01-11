<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use Tuleap\CSRFSynchronizerTokenPresenter;

/**
 * @psalm-immutable
 */
final class OnlyOfficeAdminSettingsPresenter
{
    public string $create_url;
    public string $base_url;

    /**
     * @param OnlyOfficeServerPresenter[] $servers
     */
    public function __construct(
        public array $servers,
        public CSRFSynchronizerTokenPresenter $csrf_token,
    ) {
        $this->create_url = OnlyOfficeCreateAdminSettingsController::URL;
        $this->base_url   = OnlyOfficeAdminSettingsController::ADMIN_SETTINGS_URL;
    }
}
