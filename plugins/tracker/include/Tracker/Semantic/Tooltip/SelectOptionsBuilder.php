<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Tooltip;

use Tuleap\Tracker\FormElement\RetrieveFormElementsForTracker;

final class SelectOptionsBuilder
{
    private const SEPARATOR = '::';

    public function __construct(
        private readonly RetrieveFormElementsForTracker $retriever,
    ) {
    }

    /**
     * @param array<int, \Tracker_FormElement> $to_exclude
     */
    public function build(\Tuleap\Tracker\Tracker $tracker, \PFUser $user, array $to_exclude): SelectOptionsRoot
    {
        $form_elements = $this->retriever->getUsedFormElementForTracker($tracker);

        [$options, $optgroups] = $this->extractOptionsAndOptgroups($form_elements, $user, $to_exclude, '');

        return new SelectOptionsRoot($options, $optgroups);
    }

    /**
     * @param \Tracker_FormElement[] $form_elements
     * @param array<int, \Tracker_FormElement> $to_exclude
     *
     * @return array{0: SelectOption[], 1: SelectOptgroup[]}
     */
    private function extractOptionsAndOptgroups(array $form_elements, \PFUser $user, array $to_exclude, string $prefix): array
    {
        $options   = [];
        $optgroups = [];

        foreach ($form_elements as $element) {
            if (! $element->userCanRead($user)) {
                continue;
            }

            if (isset($to_exclude[$element->getId()])) {
                continue;
            }

            if ($element instanceof \Tracker_FormElement_Field && $element->canBeDisplayedInTooltip()) {
                $options[] = new SelectOption($element->getLabel(), (string) $element->getId());
            } elseif ($element instanceof \Tracker_FormElement_Container) {
                [$container_options, $container_optgroups] = $this->extractOptionsAndOptgroups(
                    $element->getFormElements(),
                    $user,
                    $to_exclude,
                    $prefix . $element->getLabel() . self::SEPARATOR
                );

                $optgroups = [
                    ...$optgroups,
                    ...($container_options ? [new SelectOptgroup($prefix . $element->getLabel(), $container_options)] : []),
                    ...$container_optgroups,
                ];
            }
        }

        return [$options, $optgroups];
    }
}
