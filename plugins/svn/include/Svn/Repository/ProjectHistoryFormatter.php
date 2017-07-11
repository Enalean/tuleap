<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

class ProjectHistoryFormatter
{
    public function getFullHistory(Repository $repository, array $hook_config)
    {
        return $this->getRepositoryHistory($repository) .
            PHP_EOL .
            $this->getHookConfigHistory($hook_config);
    }

    private function extractHookReadableValue($value, $index)
    {
        if (isset($value[$index])) {
            return var_export($value[$index], true);
        }

        return '-';
    }

    public function getHookConfigHistory(array $hook_config)
    {
        return
            HookConfig::MANDATORY_REFERENCE . ": " .
            $this->extractHookReadableValue($hook_config, HookConfig::MANDATORY_REFERENCE) .
            PHP_EOL .
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE . ": " .
            $this->extractHookReadableValue($hook_config, HookConfig::COMMIT_MESSAGE_CAN_CHANGE);
    }

    public function getRepositoryHistory(Repository $repository)
    {
        return $repository->getName();
    }
}
