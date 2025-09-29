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

namespace Tuleap\Tracker\FormElement\Field\Date\XML;

use Tuleap\Tracker\FormElement\Field\XML\XMLChangesetValue;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;

final class XMLDateChangesetValue extends XMLChangesetValue
{
    /**
     * @var \DateTimeImmutable
     */
    private $value;

    public function __construct(string $field_name, \DateTimeImmutable $value)
    {
        parent::__construct($field_name);
        $this->value = $value;
    }

    #[\Override]
    public function export(\SimpleXMLElement $changeset_xml, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $field_change = parent::export($changeset_xml, $form_elements);

        $field_change->addAttribute('type', \Tracker_FormElementFactory::FIELD_DATE_TYPE);

        (new \XML_SimpleXMLCDATAFactory())->insertWithAttributes(
            $field_change,
            'value',
            $this->value->format('c'),
            ['format' => 'ISO8601'],
        );

        return $field_change;
    }
}
