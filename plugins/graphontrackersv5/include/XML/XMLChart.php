<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\XML;

use SimpleXMLElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use XML_SimpleXMLCDATAFactory;

abstract class XMLChart
{
    /**
     * @readonly
     */
    private string $description = '';

    public function __construct(
        /**
         * @readonly
         */
        private int $width,
        /**
         * @readonly
         */
        private int $height,
        /**
         * @readonly
         */
        private int $rank,
        /**
         * @readonly
         */
        private string $title,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    public function withDescription(string $description): static
    {
        $new              = clone $this;
        $new->description = $description;

        return $new;
    }

    public function export(
        SimpleXMLElement $renderers,
        XMLFormElementFlattenedCollection $form_elements,
    ): SimpleXMLElement {
        $renderer_xml = $renderers->addChild('chart');
        $renderer_xml->addAttribute('width', (string) $this->width);
        $renderer_xml->addAttribute('height', (string) $this->height);
        $renderer_xml->addAttribute('rank', (string) $this->rank);

        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($renderer_xml, 'title', $this->title);

        if ($this->description) {
            $cdata = new XML_SimpleXMLCDATAFactory();
            $cdata->insert($renderer_xml, 'description', $this->description);
        }

        return $renderer_xml;
    }
}
