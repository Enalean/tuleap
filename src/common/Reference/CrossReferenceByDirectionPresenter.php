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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Reference;

/**
 * @psalm-immutable
 */
final class CrossReferenceByDirectionPresenter
{
    /**
     * @var CrossReferenceNaturePresenter[]
     */
    public $sources_by_nature;
    /**
     * @var CrossReferenceNaturePresenter[]
     */
    public $targets_by_nature;
    /**
     * @var bool
     */
    public $has_source;
    /**
     * @var bool
     */
    public $has_target;

    /**
     * @psalm-param CrossReferenceNaturePresenter[] $sources_by_nature
     * @psalm-param CrossReferenceNaturePresenter[] $targets_by_nature
     */
    public function __construct(array $sources_by_nature, array $targets_by_nature)
    {
        $this->sources_by_nature = $this->sortCrossReferencesNature($sources_by_nature);
        $this->targets_by_nature = $this->sortCrossReferencesNature($targets_by_nature);

        $this->has_source = ! empty($sources_by_nature);
        $this->has_target = ! empty($targets_by_nature);
    }

    /**
     * @param CrossReferenceNaturePresenter[] $cross_references_nature
     * @return CrossReferenceNaturePresenter[]
     */
    private function sortCrossReferencesNature(array $cross_references_nature): array
    {
        usort(
            $cross_references_nature,
            function (CrossReferenceNaturePresenter $a, CrossReferenceNaturePresenter $b) {
                return strnatcasecmp($a->label, $b->label);
            }
        );
        return $cross_references_nature;
    }
}
