<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress\Administration;

use Tuleap\Tracker\Semantic\Progress\IComputeProgression;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnEffort;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnLinksCount;

class SemanticProgressAdminPresenterBuilder
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(\Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function build(
        \Tuleap\Tracker\Tracker $tracker,
        string $semantic_usages_description,
        bool $is_semantic_defined,
        string $updater_url,
        \CSRFSynchronizerToken $csrf_token,
        IComputeProgression $method,
    ): SemanticProgressAdminPresenter {
        $numeric_fields         = $this->form_element_factory->getUsedFormElementsByType($tracker, ['int', 'float', 'computed']);
        $initial_effort_options = $this->buildSelectBoxEntries(
            $numeric_fields,
            $this->getSelectedTotalEffortFieldId($method)
        );

        $remaining_effort_options = $this->buildSelectBoxEntries(
            $numeric_fields,
            $this->getSelectedRemainingEffortFieldId($method)
        );

        $available_computation_methods = $this->buildComputationMethodsSelectBoxEntries(
            $method
        );

        $has_a_link_field = ! empty($this->form_element_factory->getUsedArtifactLinkFields($tracker));

        return new SemanticProgressAdminPresenter(
            $tracker,
            $semantic_usages_description,
            $is_semantic_defined,
            $updater_url,
            $method::getMethodName(),
            $csrf_token,
            $initial_effort_options,
            $remaining_effort_options,
            $available_computation_methods,
            $has_a_link_field
        );
    }

    /**
     * @param \Tuleap\Tracker\FormElement\Field\TrackerField[] $form_elements
     */
    private function buildSelectBoxEntries(array $form_elements, ?int $selected_field_id): array
    {
        return array_map(
            function (\Tuleap\Tracker\FormElement\Field\TrackerField $field) use ($form_elements, $selected_field_id) {
                return [
                    'id' => $field->getId(),
                    'label' => $field->getLabel(),
                    'is_selected' => $field->getId() === $selected_field_id,
                ];
            },
            $form_elements
        );
    }

    private function buildComputationMethodsSelectBoxEntries(IComputeProgression $method): array
    {
        return [
            [
                'name' => MethodBasedOnEffort::getMethodName(),
                'label' => MethodBasedOnEffort::getMethodLabel(),
                'is_selected' => $method::getMethodName() === MethodBasedOnEffort::getMethodName(),
            ], [
                'name' => MethodBasedOnLinksCount::getMethodName(),
                'label' => MethodBasedOnLinksCount::getMethodLabel(),
                'is_selected' => $method::getMethodName() === MethodBasedOnLinksCount::getMethodName(),
            ],
        ];
    }

    private function getSelectedTotalEffortFieldId(IComputeProgression $method): ?int
    {
        if (! ($method instanceof MethodBasedOnEffort)) {
            return null;
        }

        return $method->getTotalEffortFieldId();
    }

    private function getSelectedRemainingEffortFieldId(IComputeProgression $method): ?int
    {
        if (! ($method instanceof MethodBasedOnEffort)) {
            return null;
        }

        return $method->getRemainingEffortFieldId();
    }
}
