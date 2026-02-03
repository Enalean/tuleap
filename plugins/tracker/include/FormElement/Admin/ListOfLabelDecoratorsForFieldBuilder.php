<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Admin;

use Override;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\TrackerFormElement;

final readonly class ListOfLabelDecoratorsForFieldBuilder implements BuildListOfLabelDecoratorsForField
{
    #[Override]
    public function getLabelDecorators(TrackerFormElement $form_element): array
    {
        $decorators = [];
        if ($form_element instanceof TrackerField) {
            $decorators = $form_element->getUsagesInSemantics()->getLabelDecorators();
        }

        if ($form_element->hasNotifications()) {
            $decorators[] = LabelDecorator::buildWithIcon(
                dgettext('tuleap-tracker', 'Notifications'),
                dgettext('tuleap-tracker', 'People selected in this field may receive notifications.'),
                'fa-solid fa-bell',
            );
        }

        return $decorators;
    }
}
