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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\XML;

use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\ArtifactLinkChange;
use Tuleap\Tracker\FormElement\Field\XML\XMLChangesetValue;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;

final class XMLArtifactLinkChangesetValue extends XMLChangesetValue
{
    /**
     * @var ArtifactLinkChange[]
     */
    private $links;

    /**
     * @param ArtifactLinkChange[] $links
     */
    public function __construct(string $field_name, array $links)
    {
        parent::__construct($field_name);
        $this->links = $links;
    }

    public function export(\SimpleXMLElement $changeset_xml, XMLFormElementFlattenedCollection $form_elements): \SimpleXMLElement
    {
        $field_change = parent::export($changeset_xml, $form_elements);

        $field_change->addAttribute('type', \Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS);

        $cdata = new \XML_SimpleXMLCDATAFactory();
        foreach ($this->links as $link) {
            $attributes = [];
            if ($link->type) {
                $attributes['nature'] = $link->type;
            }
            $cdata->insertWithAttributes($field_change, 'value', (string) $link->id, $attributes);
        }

        return $field_change;
    }
}
