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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\SemanticStatusNoField;

/**
 * @psalm-immutable
 */
final class SemanticStatusNoFieldPresenter
{
    public string $semantic_url;
    public int $tracker_id;

    public function __construct(SemanticStatusNoField $semantic_status_no_field)
    {
        $this->semantic_url = $semantic_status_no_field->semantic_url;
        $this->tracker_id   = $semantic_status_no_field->tracker_id;
    }
}
