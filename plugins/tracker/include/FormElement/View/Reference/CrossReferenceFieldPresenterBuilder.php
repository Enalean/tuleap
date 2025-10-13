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

use Tuleap\Reference\CrossReferenceByDirectionPresenterBuilder;
use Tuleap\Tracker\Artifact\Artifact;

class CrossReferenceFieldPresenterBuilder
{
    /**
     * @var CrossReferenceByDirectionPresenterBuilder
     */
    private $builder;

    public function __construct(CrossReferenceByDirectionPresenterBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function build(bool $can_delete, Artifact $artifact, \PFUser $current_user): CrossReferenceFieldPresenter
    {
        return new CrossReferenceFieldPresenter(
            $can_delete,
            $this->builder->build(
                (string) $artifact->getId(),
                Artifact::REFERENCE_NATURE,
                (int) $artifact->getTracker()->getGroupId(),
                $current_user
            ),
            $artifact
        );
    }
}
