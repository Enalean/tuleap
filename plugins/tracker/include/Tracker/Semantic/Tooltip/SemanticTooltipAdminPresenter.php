<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Tooltip;

use CSRFSynchronizerToken;

/**
 * @psalm-immutable
 */
final class SemanticTooltipAdminPresenter
{
    public readonly int $nb_other_semantics;
    public readonly bool $has_other_semantics;
    public readonly bool $has_used_fields;
    public readonly bool $has_options;

    /**
     * @param string[]                $other_semantics
     * @param TooltipFieldPresenter[] $used_fields
     */
    public function __construct(
        public readonly array $other_semantics,
        public readonly CSRFSynchronizerToken $csrf_token,
        public readonly array $used_fields,
        public readonly string $form_url,
        public readonly string $tracker_admin_semantic_url,
        public readonly SelectOptionsRoot $select_options,
    ) {
        $this->nb_other_semantics  = count($other_semantics);
        $this->has_other_semantics = $this->nb_other_semantics > 0;

        $this->has_used_fields = count($used_fields) > 0;
        $this->has_options     = $this->select_options->options || $this->select_options->optgroups;
    }
}
