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
 */

namespace Tuleap\Tracker\Semantic\Status\Open;

use CSRFSynchronizerToken;

/**
 * @psalm-immutable
 */
final class AdminPresenter
{
    /**
     * @param PossibleFieldsForStatusPresenter[] $possible_status_field
     * @param StatusValuePresenter[] $possible_open_values
     */
    public function __construct(
        public int $field_id,
        public string $label,
        public string $semantic_status_url,
        public CSRFSynchronizerToken $csrf_token,
        public bool $can_user_choose_status_field,
        public array $possible_status_field,
        public bool $has_status_field,
        public array $possible_open_values,
        public string $tracker_admin_semantic_url,
        public bool $is_semantic_done_defined,
        public ?string $status_field_label,
    ) {
    }
}
