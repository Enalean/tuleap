<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Administration\Project;

use CSRFSynchronizerToken;
use Tuleap\BotMattermost\Bot\Bot;

class ProjectAdministrationPresenter
{
    /**
     * @var Bot[]
     */
    public array $project_bots;
    public bool $has_bots;
    public CSRFSynchronizerToken $csrf_token;
    public int $project_id;

    /**
     * @param Bot[] $project_bots
     */
    public function __construct(array $project_bots, CSRFSynchronizerToken $csrf_token, int $project_id)
    {
        $this->project_bots = $project_bots;
        $this->has_bots     = ! empty($project_bots);
        $this->csrf_token   = $csrf_token;
        $this->project_id   = $project_id;
    }
}
