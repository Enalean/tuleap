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

namespace Tuleap\Artidoc\REST\v1\ArtifactSection\Field;

use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\PermissionsOnArtifactFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupValue;

/**
 * @psalm-immutable
 */
final readonly class SectionPermissionsOnArtifactFieldRepresentation
{
    public string $type;
    public string $label;
    public string $display_type;
    /**
     * @var list<UserGroupValueRepresentation> $value
     */
    public array $value;

    public function __construct(PermissionsOnArtifactFieldWithValue $field)
    {
        $this->type         = FieldType::PERMISSIONS->value;
        $this->label        = $field->label;
        $this->display_type = $field->display_type->value;
        $this->value        = array_map(
            static fn(UserGroupValue $user_group) => new UserGroupValueRepresentation($user_group),
            $field->user_groups,
        );
    }
}
