<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\XML;

/**
 * This class is supposed to be temporary only (I guess one will have a good laugh when they will read that in 3 years)
 * It's there to transition from the XML export done internally to Tracker_FormElement class to a dedicated set of
 * objects.
 *
 * The alternative would have been for `XMLFormElement` not to be abstract. However the goal is to set more solid
 * fondations with this refactoring, hence to mark as clearly as possible the architecture.
 *
 * Until all fields have their equivalent in XML... form, we need this java-ish implementation.
 */
final class XMLFormElementImpl extends XMLFormElement
{
    #[\Override]
    public function exportPermissions(\SimpleXMLElement $form_elements): void
    {
    }
}
