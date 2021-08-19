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

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReference;
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
    /**
     * @readonly
     */
    private bool $is_advanced = false;
    /**
     * @var XMLBindValueReference[]
     */
    private $selected_values = [];

    /**
     * @readonly
     */
    private bool $is_none_selected = false;

    public function __construct(XMLReference $reference)
    {
        $this->reference = $reference;
    }

    /**
     * @psalm-mutation-free
     */
    public function withRank(int $rank): self
    {
        $new       = clone $this;
        $new->rank = $rank;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withIsAdvanced(): self
    {
        $new              = clone $this;
        $new->is_advanced = true;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withNoneSelected(): self
    {
        $new                   = clone $this;
        $new->is_none_selected = true;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withSelectedValues(XMLBindValueReference ...$selected_values): self
    {
        $new                  = clone $this;
        $new->selected_values = array_merge($this->selected_values, $selected_values);
        return $new;
    }

    public function export(\SimpleXMLElement $criterias, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $criterion = $criterias->addChild('criteria');
        $criterion->addAttribute('rank', (string) $this->rank);
        if ($this->is_advanced || count($this->selected_values) > 1) {
            $criterion->addAttribute('is_advanced', '1');
        }

        $field = $criterion->addChild('field');
        $field->addAttribute('REF', $this->reference->getId($form_elements));

        if (count($this->selected_values) > 0 || $this->is_none_selected) {
            $criteria_value = $criterion->addChild('criteria_value');
            $criteria_value->addAttribute('type', 'list');
            if ($this->is_none_selected) {
                $criteria_value->addChild('none_value');
            }
            foreach ($this->selected_values as $selected_value) {
                $criteria_value->addChild('selected_value')->addAttribute('REF', $selected_value->getId($form_elements));
            }
        }

        return $criterion;
    }
}
