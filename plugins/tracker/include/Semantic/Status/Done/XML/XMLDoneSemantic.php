<?php
/**
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

namespace Tuleap\Tracker\Semantic\Status\Done\XML;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReference;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\XML\XMLSemantic;

final class XMLDoneSemantic extends XMLSemantic
{
    /**
     * @var XMLBindValueReference[]
     * @readonly
     */
    private array $done_values = [];

    public function __construct()
    {
        parent::__construct(SemanticDone::NAME);
    }

    /**
     * @psalm-mutation-free
     */
    public function withDoneValues(XMLBindValueReference ...$done_values): self
    {
        $new              = clone $this;
        $new->done_values = array_merge($new->done_values, $done_values);
        return $new;
    }

    public function export(\SimpleXMLElement $parent_node, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $semantic = parent::export($parent_node, $form_elements);

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insert($semantic, 'shortname', SemanticDone::NAME);
        $cdata->insert($semantic, 'label', 'Done');
        $cdata->insert($semantic, 'description', 'Define the closed status that are considered Done');

        $closed_values = $semantic->addChild('closed_values');
        foreach ($this->done_values as $done_value) {
            $closed_values
                ->addChild('closed_value')
                ->addAttribute('REF', $done_value->getId($form_elements));
        }

        return $semantic;
    }
}
