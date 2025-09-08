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

namespace Tuleap\Tracker\Semantic\Status\XML;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReference;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;
use Tuleap\Tracker\Semantic\XML\XMLSemantic;

final class XMLStatusSemantic extends XMLSemantic
{
    /**
     * @var XMLBindValueReference[]
     * @readonly
     */
    private array $open_values = [];

    public function __construct(
        /**
         * @readonly
         */
        private XMLReference $reference,
    ) {
        parent::__construct(\Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::NAME);
    }

    /**
     * @psalm-mutation-free
     */
    public function withOpenValues(XMLBindValueReference ...$open_values): self
    {
        $new              = clone $this;
        $new->open_values = array_merge($new->open_values, $open_values);
        return $new;
    }

    #[\Override]
    public function export(\SimpleXMLElement $parent_node, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $semantic = parent::export($parent_node, $form_elements);

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insert($semantic, 'shortname', \Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus::NAME);
        $cdata->insert($semantic, 'label', 'Status');
        $cdata->insert($semantic, 'description', 'Define the status of an artifact');
        $semantic->addChild('field')->addAttribute('REF', $this->reference->getId($form_elements));

        $open_values = $semantic->addChild('open_values');
        foreach ($this->open_values as $open_value) {
            $xml_open_value = $open_values->addChild('open_value');
            $xml_open_value->addAttribute('REF', $open_value->getId($form_elements));
        }

        return $semantic;
    }
}
