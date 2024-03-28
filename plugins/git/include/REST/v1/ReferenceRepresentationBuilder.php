<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */
declare(strict_types=1);

namespace Tuleap\Git\REST\v1;

use CrossReferenceFactory;

class ReferenceRepresentationBuilder
{
    private CrossReferenceFactory $cross_reference_factory;

    public function __construct(CrossReferenceFactory $cross_reference_factory)
    {
        $this->cross_reference_factory = $cross_reference_factory;
    }

    /**
     * @return ReferenceRepresentation[]
     */
    public function buildReferenceRepresentationList(): array
    {
        $this->cross_reference_factory->fetchDatas();
        $directions_list = $this->cross_reference_factory->getFormattedCrossReferences();

        $reference_representation_list = [];
        foreach ($directions_list as $direction => $references_list) {
            foreach ($references_list as $reference) {
                $reference_representation_list[] =
                match ($direction) {
                    'target' => ReferenceRepresentation::outReferenceRepresentation($reference['ref'], $reference['url']),
                    'source' => ReferenceRepresentation::inReferenceRepresentation($reference['ref'], $reference['url']),
                    'both'   => ReferenceRepresentation::bothReferenceRepresentation($reference['ref'], $reference['url']),
                };
            }
        }
        return $reference_representation_list;
    }
}
