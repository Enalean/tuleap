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

namespace Tuleap\Tracker\Artifact\Changeset\XML;

use DateTimeImmutable;
use Tuleap\Tracker\FormElement\Field\XML\XMLChangesetValue;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\XML\XMLUser;

final class XMLChangeset
{
    /**
     * @var XMLUser
     */
    private $submitted_by;
    /**
     * @var DateTimeImmutable
     */
    private $submitted_on;
    /**
     * @var XMLChangesetValue[]
     */
    private $field_change = [];

    public function __construct(XMLUser $submitted_by, DateTimeImmutable $submitted_on)
    {
        $this->submitted_by = $submitted_by;
        $this->submitted_on = $submitted_on;
    }

    /**
     * @psalm-mutation-free
     */
    public function withFieldChange(XMLChangesetValue $field_value): self
    {
        $new                 = clone $this;
        $new->field_change[] = $field_value;
        return $new;
    }

    public function export(\SimpleXMLElement $artifact, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $changeset_xml = $artifact->addChild('changeset');

        $this->submitted_by->export('submitted_by', $changeset_xml);

        (new \XML_SimpleXMLCDATAFactory())->insertWithAttributes(
            $changeset_xml,
            'submitted_on',
            $this->submitted_on->format('c'),
            ['format' => 'ISO8601']
        );

        $changeset_xml->addChild('comments');

        foreach ($this->field_change as $field_change) {
            $field_change->export($changeset_xml, $form_elements);
        }

        return $changeset_xml;
    }
}
