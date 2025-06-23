<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use CSRFSynchronizerToken;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Tracker;

final class SemanticDoneAdminPresenter
{
    public bool $semantic_status_is_defined;
    public int $tracker_id;
    public bool $has_closed_values_selectable;

    public function __construct(
        public CSRFSynchronizerToken $csrf_token,
        Tracker $tracker,
        public array $closed_values,
        public string $form_url,
        public string $tracker_admin_semantic_url,
        public bool $has_done_values,
        ?Tracker_FormElement_Field $semantic_status_field = null,
    ) {
        $this->semantic_status_is_defined   = ($semantic_status_field !== null);
        $this->tracker_id                   = $tracker->getId();
        $this->has_closed_values_selectable = count($this->closed_values) > 0;
    }
}
