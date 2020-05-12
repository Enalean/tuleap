<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\XML\Exporter\FieldChange;

use SimpleXMLElement;
use Tracker_FormElement_Field_List_Bind_Users;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class FieldChangeListBuilder
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simple_xml_cdata_factory;

    /**
     * @var UserXMLExporter
     */
    private $user_xml_exporter;

    public function __construct(XML_SimpleXMLCDATAFactory $simple_xml_cdata_factory, UserXMLExporter $user_xml_exporter)
    {
        $this->simple_xml_cdata_factory = $simple_xml_cdata_factory;
        $this->user_xml_exporter        = $user_xml_exporter;
    }

    public function build(
        SimpleXMLElement $changeset_xml,
        string $field_name,
        string $bind_type,
        array $values
    ): void {
        $field_change = $changeset_xml->addChild('field_change');
        $field_change->addAttribute('field_name', $field_name);
        $field_change->addAttribute('type', 'list');
        $field_change->addAttribute('bind', $bind_type);

        if (empty($values)) {
            $field_change->addChild('value');
        } elseif ($bind_type === Tracker_FormElement_Field_List_Bind_Users::TYPE) {
            foreach ($values as $value) {
                $this->user_xml_exporter->exportUserByUserId($value, $field_change, 'value');
            }
        } else {
            foreach ($values as $value) {
                $this->simple_xml_cdata_factory->insertWithAttributes(
                    $field_change,
                    'value',
                    (string) $value,
                    ['format' => 'id']
                );
            }
        }
    }
}
