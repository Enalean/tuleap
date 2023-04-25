<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use CSRFSynchronizerToken;

/**
 * @psalm-immutable
 */
final class InitialEffortAdminSemanticPresenter
{
    public readonly bool $has_numeric_fields;

    public function __construct(
        public readonly CSRFSynchronizerToken $csrf_token,
        public readonly string $tracker_admin_semantic_url,
        public readonly array $numeric_fields,
        public readonly string $url,
        public readonly bool $has_initial_effort_field,
    ) {
        $this->has_numeric_fields = count($this->numeric_fields) > 0;
    }
}
