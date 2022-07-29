<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\TitleHasIncorrectType;

/**
 * @psalm-immutable
 */
final class TitleHasIncorrectTypePresenter
{
    public string $semantic_title_url;
    public string $tracker_name;
    public string $project_name;
    public string $field_name;

    public function __construct(TitleHasIncorrectType $title_has_incorrect_type)
    {
        $this->semantic_title_url = $title_has_incorrect_type->semantic_title_url;
        $this->tracker_name       = $title_has_incorrect_type->tracker_name;
        $this->project_name       = $title_has_incorrect_type->project_name;
        $this->field_name         = $title_has_incorrect_type->field_name;
    }
}
