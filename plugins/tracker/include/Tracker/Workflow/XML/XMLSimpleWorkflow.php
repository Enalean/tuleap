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

namespace Tuleap\Tracker\Workflow\XML;

use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\FormElement\XML\XMLReference;

final class XMLSimpleWorkflow implements XMLWorkflow
{
    /**
     * @readonly
     */
    private ?XMLReference $field_reference;

    /**
     * @readonly
     */
    private bool $is_used = false;

    /**
     * @psalm-mutation-free
     */
    public function withField(XMLReference $reference): self
    {
        $new                  = clone $this;
        $new->field_reference = $reference;
        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withIsUsed(): self
    {
        $new          = clone $this;
        $new->is_used = true;
        return $new;
    }

    #[\Override]
    public function export(\SimpleXMLElement $parent_node, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $workflow = $parent_node->addChild('simple_workflow');

        if ($this->field_reference) {
            $workflow->addChild('field_id')
                ->addAttribute('REF', $this->field_reference->getId($form_elements));
        }

        (new \XML_SimpleXMLCDATAFactory())->insert($workflow, 'is_used', $this->is_used ? 1 : 0);

        return $workflow;
    }
}
