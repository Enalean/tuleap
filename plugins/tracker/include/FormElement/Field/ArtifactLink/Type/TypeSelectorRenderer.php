<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\PossibleParentSelector;

final class TypeSelectorRenderer
{
    public function __construct(
        private IRetrieveAllUsableTypesInProject $types_retriever,
        private \TemplateRenderer $renderer,
    ) {
    }

    public function renderToString(
        Artifact $artifact,
        string $prefill_type,
        string $name,
        ?PossibleParentSelector $possible_parent_selector,
    ): string {
        $types = $this->types_retriever->getAllUsableTypesInProject(
            $artifact->getTracker()->getProject()
        );

        $is_parent_selector_displayed = $possible_parent_selector && $possible_parent_selector->isSelectorDisplayed();

        $types_presenter = [];
        foreach ($types as $type) {
            $types_presenter[] = [
                'shortname'     => $type->shortname,
                'forward_label' => $type->forward_label,
                'is_selected'   => ($type->shortname === $prefill_type),
            ];

            if ($is_parent_selector_displayed) {
                continue;
            }

            if ($type->shortname === \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD) {
                $types_presenter[] = [
                    'shortname'     => \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::FAKE_TYPE_IS_PARENT,
                    'forward_label' => $type->reverse_label,
                    'is_selected'   => (\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::FAKE_TYPE_IS_PARENT === $prefill_type),
                ];
            }
        }

        return $this->renderer->renderToString(
            'artifactlink-type-selector',
            new TypeSelectorPresenter(
                $types_presenter,
                $name . '[type]',
                'tracker-form-element-artifactlink-new type-selector',
                false,
            )
        );
    }
}
