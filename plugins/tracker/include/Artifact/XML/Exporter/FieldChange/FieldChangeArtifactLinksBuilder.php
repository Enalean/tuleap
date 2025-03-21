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

namespace Tuleap\Tracker\Artifact\XML\Exporter\FieldChange;

use SimpleXMLElement;
use Tracker_FormElementFactory;
use XML_SimpleXMLCDATAFactory;

class FieldChangeArtifactLinksBuilder
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simple_xml_cdata_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $simple_xml_cdata_factory)
    {
        $this->simple_xml_cdata_factory = $simple_xml_cdata_factory;
    }

    /**
     * @param ArtifactLinkChange[] $values
     */
    public function build(
        SimpleXMLElement $changeset_xml,
        string $field_name,
        array $values,
    ): void {
        $field_change_node = $changeset_xml->addChild('field_change');
        $field_change_node->addAttribute('type', Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS);
        $field_change_node->addAttribute('field_name', $field_name);

        foreach ($values as $value) {
            $element = $this->simple_xml_cdata_factory->insert($field_change_node, 'value', $value->id);
            if ($value->hasType()) {
                $element->addAttribute('nature', $value->type);
            }
        }
    }
}
