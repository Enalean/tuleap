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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Report\XML;

use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;

final class XMLReportCriterion
{
    /**
     * @var int
     * @readonly
     */
    private $rank = 1;
    /**
     * @var XMLReference
     * @readonly
     */
    private $reference;

    public function __construct(XMLReference $reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return static
     * @psalm-mutation-free
     */
    public function withRank(int $rank): self
    {
        $new       = clone $this;
        $new->rank = $rank;
        return $new;
    }

    public function export(\SimpleXMLElement $criterias, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $criterion = $criterias->addChild('criteria');
        $criterion->addAttribute('rank', (string) $this->rank);

        $field = $criterion->addChild('field');
        $field->addAttribute('REF', $this->reference->getId($form_elements));

        return $criterion;
    }
}
