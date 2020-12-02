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

namespace Tuleap\Tracker\FormElement\View\Reference;

use Tuleap\reference\CrossReferenceByNatureCollection;

class CrossReferenceFieldPresenterBuilder
{

    /**
     * @var CrossReferenceByNaturePresenterBuilder
     */
    private $cross_ref_by_nature_presenter_builder;

    public function __construct(CrossReferenceByNaturePresenterBuilder $cross_ref_by_nature_presenter_builder)
    {
        $this->cross_ref_by_nature_presenter_builder = $cross_ref_by_nature_presenter_builder;
    }

    public function build(CrossReferenceByNatureCollection $cross_ref_by_nature_collection, bool $can_delete): CrossReferenceFieldPresenter
    {
        $cross_refs_by_nature_presenter_collection = [];

        foreach ($cross_ref_by_nature_collection->getAll() as $cross_reference_collection) {
            $cross_ref_by_nature_presenter = $this->cross_ref_by_nature_presenter_builder->build(
                $cross_reference_collection
            );
            if ($cross_ref_by_nature_presenter) {
                $cross_refs_by_nature_presenter_collection[] = $cross_ref_by_nature_presenter;
            }
        }

        return new CrossReferenceFieldPresenter(
            $can_delete,
            $cross_refs_by_nature_presenter_collection
        );
    }
}
