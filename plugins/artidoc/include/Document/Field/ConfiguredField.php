<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\TestManagement\Step\Definition\Field\StepsDefinition;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\NumericField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;

final readonly class ConfiguredField
{
    public bool $can_display_type_be_changed;

    public function __construct(
        public TextField|Tracker_FormElement_Field_List|ArtifactLinkField|NumericField|Tracker_FormElement_Field_Date|Tracker_FormElement_Field_PermissionsOnArtifact|StepsDefinition $field,
        public DisplayType $display_type,
    ) {
        $this->can_display_type_be_changed = match ($this->field::class) {
            ArtifactLinkField::class,
            TextField::class,
            StepsDefinition::class => true,
            default                => false,
        };
    }
}
