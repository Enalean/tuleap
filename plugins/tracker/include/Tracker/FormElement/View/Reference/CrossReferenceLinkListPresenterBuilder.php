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

class CrossReferenceLinkListPresenterBuilder
{

    /**
     * @param CrossReferenceLinkPresenter[] $cross_ref_link_collection
     */
    public function buildForTarget(array $cross_ref_link_collection): CrossReferenceLinkListPresenter
    {
        $icon_message = dgettext('tuleap-tracker', 'This item references:');
        $icon_path = "/themes/FlamingParrot/images/ic/right_arrow.png";
        return new CrossReferenceLinkListPresenter('tracker-cross-reference-to', $icon_message, $icon_path, $cross_ref_link_collection);
    }

    /**
     * @param CrossReferenceLinkPresenter[] $cross_ref_link_collection
     */
    public function buildForSource(array $cross_ref_link_collection): CrossReferenceLinkListPresenter
    {
        $icon_message = dgettext('tuleap-tracker', 'This item is referenced by:');
        $icon_path = "/themes/FlamingParrot/images/ic/left_arrow.png";
        return new CrossReferenceLinkListPresenter('tracker-cross-reference-by', $icon_message, $icon_path, $cross_ref_link_collection);
    }
}
