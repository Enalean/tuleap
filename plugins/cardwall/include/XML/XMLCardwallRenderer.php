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

namespace Tuleap\Cardwall\XML;

use SimpleXMLElement;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;
use Tuleap\Tracker\Report\Renderer\XML\XMLRenderer;

final class XMLCardwallRenderer extends XMLRenderer
{
    private const string TYPE = 'plugin_cardwall';

    /**
     * @readonly
     */
    private ?XMLReference $reference = null;

    /**
     * @psalm-mutation-free
     */
    public function withField(XMLReference $reference): self
    {
        $new            = clone $this;
        $new->reference = $reference;
        return $new;
    }

    #[\Override]
    public function export(SimpleXMLElement $renderers, XMLFormElementFlattenedCollection $form_elements): SimpleXMLElement
    {
        $renderer_xml = parent::export($renderers, $form_elements);
        $renderer_xml->addAttribute('type', self::TYPE);
        if ($this->reference) {
            $renderer_xml->addAttribute('field_id', $this->reference->getId($form_elements));
        }

        return $renderer_xml;
    }
}
