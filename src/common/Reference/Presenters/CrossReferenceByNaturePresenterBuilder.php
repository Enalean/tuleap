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

namespace Tuleap\Reference\Presenters;

use Tuleap\Reference\CrossReferenceCollection;

class CrossReferenceByNaturePresenterBuilder
{
    /**
     * @var CrossReferenceLinkListPresenterBuilder
     */
    private $link_list_builder;
    /**
     * @var CrossReferenceLinkPresenterCollectionBuilder
     */
    private $link_presenter_collection_builder;

    public function __construct(
        CrossReferenceLinkListPresenterBuilder $link_list_builder,
        CrossReferenceLinkPresenterCollectionBuilder $link_presenter_array_builder,
    ) {
        $this->link_list_builder                 = $link_list_builder;
        $this->link_presenter_collection_builder = $link_presenter_array_builder;
    }

    public function build(
        CrossReferenceCollection $cross_reference_collection,
        bool $display_params,
    ): ?CrossReferenceByNaturePresenter {
        if (
            $cross_reference_collection->getCrossReferencesBoth() === [] &&
            $cross_reference_collection->getCrossReferencesTarget() === [] &&
            $cross_reference_collection->getCrossReferencesSource() === []
        ) {
            return null;
        }

        $cross_ref_list_array = [];

        if ($cross_reference_collection->getCrossReferencesBoth() !== []) {
            $cross_ref_list_array[] = $this->link_list_builder->buildForBoth(
                $this->link_presenter_collection_builder->build($cross_reference_collection->getCrossReferencesBoth(), "both", $display_params)
            );
        }

        if ($cross_reference_collection->getCrossReferencesTarget() !== []) {
            $cross_ref_list_array[] = $this->link_list_builder->buildForTarget(
                $this->link_presenter_collection_builder->build($cross_reference_collection->getCrossReferencesTarget(), "target", $display_params)
            );
        }

        if ($cross_reference_collection->getCrossReferencesSource() !== []) {
            $cross_ref_list_array[] = $this->link_list_builder->buildForSource(
                $this->link_presenter_collection_builder->build($cross_reference_collection->getCrossReferencesSource(), "source", $display_params)
            );
        }

        return new CrossReferenceByNaturePresenter($cross_reference_collection->getLabel(), $cross_ref_list_array);
    }
}
