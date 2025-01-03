<?php
/**
 * Copyright (c) Enalean 2016 - Present. All rights reserved
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

use Exception;
use Tuleap\SVNCore\Repository;

class HookConfig
{
    public const MANDATORY_REFERENCE       = 'mandatory_reference';
    public const COMMIT_MESSAGE_CAN_CHANGE = 'commit_message_can_change';

    /**
     * @var Repository
     */
    private $repository;
    /**
     * @var array
     */
    private $data;

    public function __construct(Repository $repo, array $row)
    {
        $this->repository = $repo;
        $this->data       = $row;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getHookConfig($config_name)
    {
        if (! isset($this->data[$config_name])) {
            switch ($config_name) {
                case self::MANDATORY_REFERENCE:
                case self::COMMIT_MESSAGE_CAN_CHANGE:
                    return false;
                default:
                    throw new Exception("Incorrect hook configuration $config_name");
            }
        } else {
            return $this->data[$config_name];
        }
    }
}
