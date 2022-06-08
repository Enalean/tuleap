<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Service;

use Tuleap\Project\ProjectByIDFactory;

/**
 * @psalm-immutable
 */
abstract class ServiceActivationEvent
{
    private function __construct(public bool $is_used, public \Project $project)
    {
    }

    /**
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    final public static function fromEvent(array $params, ProjectByIDFactory $factory): self
    {
        if ($params['shortname'] !== MediawikiStandaloneService::SERVICE_SHORTNAME) {
            return new InvalidServiceActivationEvent();
        }
        try {
            return new ValidServiceActivationEvent($params['is_used'], $factory->getValidProjectById((int) $params['group_id']));
        } catch (\Project_NotFoundException) {
        }
        return new InvalidServiceActivationEvent();
    }

    /**
     * @psalm-pure
     */
    abstract public function isValid(): bool;
}
