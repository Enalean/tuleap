<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use Tuleap\SVNCore\Repository;

class HookConfigRetriever
{
    /**
     * @var HookDao
     */
    private $hook_dao;
    /**
     * @var HookConfigSanitizer
     */
    private $hook_config_sanitizer;

    public function __construct(HookDao $hook_dao, HookConfigSanitizer $hook_config_sanitizer)
    {
        $this->hook_dao              = $hook_dao;
        $this->hook_config_sanitizer = $hook_config_sanitizer;
    }

    /**
     *
     * @return HookConfig
     */
    public function getHookConfig(Repository $repository)
    {
        $row = $this->hook_dao->getHookConfig($repository->getId());
        if (! $row) {
            $row = [];
        }

        $this->hook_config_sanitizer->sanitizeHookConfigArray($row);

        return new HookConfig($repository, $row);
    }
}
