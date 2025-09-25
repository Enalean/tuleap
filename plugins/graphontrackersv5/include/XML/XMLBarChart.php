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
use Tuleap\Tracker\FormElement\XML\XMLReference;

final class XMLBarChart extends XMLChart
{
    private const string TYPE = 'bar';

    /**
     * @readonly
     */
    private ?XMLReference $base_reference = null;
    /**
     * @readonly
     */
    private ?XMLReference $group_reference = null;

    /**
     * @psalm-mutation-free
     */
    public function withBase(XMLReference $reference): self
    {
        $new                 = clone $this;
        $new->base_reference = $reference;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withGroup(XMLReference $reference): self
    {
        $new                  = clone $this;
        $new->group_reference = $reference;
        return $new;
    }

    #[\Override]
    public function export(SimpleXMLElement $renderers, XMLFormElementFlattenedCollection $form_elements): SimpleXMLElement
    {
        $renderer_xml = parent::export($renderers, $form_elements);
        $renderer_xml->addAttribute('type', self::TYPE);

        if ($this->base_reference) {
            $renderer_xml->addAttribute('base', $this->base_reference->getId($form_elements));
        }
        if ($this->group_reference) {
            $renderer_xml->addAttribute('group', $this->group_reference->getId($form_elements));
        }

        return $renderer_xml;
    }
}
