<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Tracker_REST_FormElementRepresentation;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeRepresentation;

/**
 * @psalm-immutable
 */
final class LinksFieldRepresentation extends Tracker_REST_FormElementRepresentation
{
    /**
     * @var TypeRepresentation[]
     */
    public array $allowed_types;

    /**
     * @param mixed $default_rest_value
     * @param mixed|null $values
     */
    private function __construct(
        \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField $form_element,
        string $type,
        bool $is_collapsed,
        $default_rest_value,
        $values,
        array $rest_binding_properties,
        array $permissions,
        array $allowed_types,
        ?PermissionsForGroupsRepresentation $permissions_for_groups,
    ) {
        parent::__construct(
            $form_element,
            $type,
            $is_collapsed,
            $default_rest_value,
            $values,
            $rest_binding_properties,
            $permissions,
            $permissions_for_groups
        );

        $this->allowed_types = $allowed_types;
    }

    /**
     * @param TypePresenter[] $allowed_link_types_presenters
     */
    public static function buildRepresentationWithAllowedLinkTypes(
        \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField $form_element,
        string $type,
        array $permissions,
        array $allowed_link_types_presenters,
        ?PermissionsForGroupsRepresentation $permissions_for_groups,
    ): self {
        return new self(
            $form_element,
            $type,
            $form_element->isCollapsed(),
            $form_element->getDefaultRESTValue(),
            $form_element->getRESTAvailableValues(),
            $form_element->getRESTBindingProperties(),
            $permissions,
            self::buildTypesRepresentations($allowed_link_types_presenters),
            $permissions_for_groups,
        );
    }

    /**
     * @param TypePresenter[] $allowed_types
     * @return TypeRepresentation[]
     */
    private static function buildTypesRepresentations(array $allowed_types): array
    {
        return array_map(
            static fn(TypePresenter $type) => TypeRepresentation::build(
                $type->shortname,
                $type->forward_label,
                $type->reverse_label
            ),
            $allowed_types
        );
    }
}
