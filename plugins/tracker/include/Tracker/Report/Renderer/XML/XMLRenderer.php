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

namespace Tuleap\Tracker\Report\Renderer\XML;

use SimpleXMLElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use XML_SimpleXMLCDATAFactory;

abstract class XMLRenderer
{
    /**
     * @var string
     * @readonly
     */
    private $name;
    /**
     * @var int
     * @readonly
     */
    private $rank = 1;

    public function __construct(string $name)
    {
        $this->name = $name;
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

    public function export(SimpleXMLElement $renderers, XMLFormElementFlattenedCollection $form_elements): SimpleXMLElement
    {
        $renderer_xml = $renderers->addChild('renderer');
        $renderer_xml->addAttribute('rank', (string) $this->rank);

        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($renderer_xml, 'name', $this->name);

        return $renderer_xml;
    }
}
