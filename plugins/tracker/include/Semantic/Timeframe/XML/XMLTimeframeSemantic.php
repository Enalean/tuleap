<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe\XML;

use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\XML\XMLSemantic;

final class XMLTimeframeSemantic extends XMLSemantic
{
    public function __construct(
        /**
         * @readonly
         */
        private XMLReference $start_date,
        /**
         * @readonly
         */
        private XMLReference $end_date,
    ) {
        parent::__construct(SemanticTimeframe::NAME);
    }

    public function export(\SimpleXMLElement $parent_node, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $child = parent::export($parent_node, $form_elements);

        $child->addChild('start_date_field')->addAttribute('REF', $this->start_date->getId($form_elements));
        $child->addChild('end_date_field')->addAttribute('REF', $this->end_date->getId($form_elements));

        return $child;
    }
}
