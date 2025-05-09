<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use TemplateRenderer;
use Tracker_FormElement;
use Tracker_FormElement_View_Admin_Field;

final class ArtifactLinkFieldAdmin extends Tracker_FormElement_View_Admin_Field
{
    public function __construct(
        private readonly TemplateRenderer $renderer,
        Tracker_FormElement $formElement,
        array $allUsedElements,
    ) {
        parent::__construct($formElement, $allUsedElements);
    }

    protected function fetchAdminSpecificProperty(string $key, array $property): string
    {
        if ($key === 'can_edit_reverse_links') {
            return $this->renderer->renderToString('can_edit_reverse_links_admin_property', [
                'is_checked' => $this->formElement->getProperty('can_edit_reverse_links') === 1,
            ]);
        }

        return parent::fetchAdminSpecificProperty($key, $property);
    }
}
