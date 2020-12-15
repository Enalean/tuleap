<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

/**
 * @psalm-immutable
 */
final class CrossReferenceNaturePresenter
{
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $icon;
    /**
     * @var CrossReferenceSectionPresenter[]
     */
    public $sections;

    /**
     * @param CrossReferenceSectionPresenter[] $sections
     */
    public function __construct(string $label, string $icon, array $sections)
    {
        $this->label = $label;
        $this->icon = $icon;
        $this->sections = $sections;
    }

    public function withAdditionalCrossReference(string $section_label, CrossReferencePresenter $cross_reference): self
    {
        foreach ($this->sections as $index => $matching_section) {
            if ($matching_section->label !== $section_label) {
                continue;
            }

            $new_sections = $this->sections;
            array_splice(
                $new_sections,
                $index,
                1,
                [$matching_section->withAdditionalCrossReference($cross_reference)]
            );

            return new self($this->label, $this->icon, $new_sections);
        }

        return new self(
            $this->label,
            $this->icon,
            array_merge(
                $this->sections,
                [new CrossReferenceSectionPresenter($section_label, [$cross_reference])]
            )
        );
    }
}
