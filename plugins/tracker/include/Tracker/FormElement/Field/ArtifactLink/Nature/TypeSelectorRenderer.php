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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\PossibleParentSelector;

final class TypeSelectorRenderer
{
    public function __construct(
        private IRetrieveAllUsableTypesInProject $types_retriever,
        private \TemplateRenderer $renderer
    ) {
    }

    public function renderToString(
        Artifact $artifact,
        string $prefill_type,
        string $name,
        ?PossibleParentSelector $possible_parent_selector
    ): string {
        $types = $this->types_retriever->getAllUsableTypesInProject(
            $artifact->getTracker()->getProject()
        );

        $is_parent_selector_displayed = $possible_parent_selector && $possible_parent_selector->isSelectorDisplayed();

        $natures_presenter = [];
        foreach ($types as $type) {
            $natures_presenter[] = [
                'shortname'     => $type->shortname,
                'forward_label' => $type->forward_label,
                'is_selected'   => ($type->shortname === $prefill_type)
            ];

            if ($is_parent_selector_displayed) {
                continue;
            }

            if ($type->shortname === \Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD) {
                $natures_presenter[] = [
                    'shortname'     => \Tracker_FormElement_Field_ArtifactLink::FAKE_TYPE_IS_PARENT,
                    'forward_label' => $type->reverse_label,
                    'is_selected'   => (\Tracker_FormElement_Field_ArtifactLink::FAKE_TYPE_IS_PARENT === $prefill_type)
                ];
            }
        }

        return $this->renderer->renderToString(
            'artifactlink-nature-selector',
            new NatureSelectorPresenter(
                $natures_presenter,
                $name . '[nature]',
                'tracker-form-element-artifactlink-new nature-selector'
            )
        );
    }
}
