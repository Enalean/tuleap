<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Widget\Note;

use Codendi_Request;
use Tuleap\Dashboard\User\UserDashboardController;

class UserNote extends Note
{
    public const NAME = 'usernote';

    #[\Override]
    protected static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getDescription(): string
    {
        return _('Allow to write notes on your dashboard using Markdown');
    }

    #[\Override]
    public function create(Codendi_Request $request)
    {
        if ($this->owner_id === null) {
            $current_user = $request->getCurrentUser();
            if ($current_user->isAlive()) {
                $this->setOwner($current_user->getId(), UserDashboardController::LEGACY_DASHBOARD_TYPE);
            } else {
                return false;
            }
        }

        return $this->createNote($request, $this->owner_id, $this->owner_type);
    }
}
