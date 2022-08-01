<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ProgramSelectOptionConfiguration;

/**
 * @psalm-immutable
 */
final class ProgramSelectOptionConfigurationPresenter
{
    /**
     * @var int|string
     */
    public $id;
    public string $label;
    public bool $is_selected;

    private function __construct(ProgramSelectOptionConfiguration $configuration)
    {
        $this->id          = $configuration->id;
        $this->label       = $configuration->label;
        $this->is_selected = $configuration->is_selected;
    }

    /**
     * @param ProgramSelectOptionConfiguration[] $configuration_list
     * @return self[]
     */
    public static function build(array $configuration_list): array
    {
        $built_configurations = [];
        foreach ($configuration_list as $configuration) {
            $built_configurations[] = new self($configuration);
        }
        return $built_configurations;
    }
}
