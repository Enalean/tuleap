<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\View\Reference;

use Tuleap\Reference\CrossReferenceByDirectionPresenter;
use Tuleap\Tracker\Artifact\Artifact;

class CrossReferenceFieldPresenter
{
    /**
     * @var bool
     */
    public $can_delete;
    /**
     * @var bool
     */
    public $has_cross_refs_to_display;
    /**
     * @var string
     */
    public $artifact_xref;
    /**
     * @var CrossReferenceByDirectionPresenter
     */
    public $by_direction;

    public function __construct(
        bool $can_delete,
        CrossReferenceByDirectionPresenter $by_direction,
        Artifact $artifact,
    ) {
        $this->can_delete                = $can_delete;
        $this->by_direction              = $by_direction;
        $this->has_cross_refs_to_display = $by_direction->has_source || $by_direction->has_target;
        $this->artifact_xref             = $artifact->getXRef();
    }
}
