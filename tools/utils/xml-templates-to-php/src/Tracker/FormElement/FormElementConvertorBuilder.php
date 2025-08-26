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

namespace Tuleap\Tools\Xml2Php\Tracker\FormElement;

use Psr\Log\LoggerInterface;

class FormElementConvertorBuilder
{
    public static function buildFromXML(
        \SimpleXMLElement $xml,
        \SimpleXMLElement $xml_tracker,
        LoggerInterface $output,
    ): ?FormElementConvertor {
        $mapping = [
            'string'   => \Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField::class,
            'text'     => \Tuleap\Tracker\FormElement\Field\Text\XML\XMLTextField::class,
            'float'    => \Tuleap\Tracker\FormElement\Field\FloatingPointNumber\XML\XMLFloatField::class,
            'int'      => \Tuleap\Tracker\FormElement\Field\Integer\XML\XMLIntegerField::class,
            'date'     => \Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField::class,
            'sb'       => \Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField::class,
            'file'     => \Tuleap\Tracker\FormElement\Field\Files\XML\XMLFileField::class,
            'lud'      => \Tuleap\Tracker\FormElement\Field\LastUpdateDate\XML\XMLLastUpdateDateField::class,
            'subon'    => \Tuleap\Tracker\FormElement\Field\SubmittedOn\XML\XMLSubmittedOnField::class,
            'subby'    => \Tuleap\Tracker\FormElement\Field\SubmittedBy\XML\XMLSubmittedByField::class,
            'aid'      => \Tuleap\Tracker\FormElement\Field\ArtifactId\XML\XMLArtifactIdField::class,
            'art_link' => \Tuleap\Tracker\FormElement\Field\ArtifactLink\XML\XMLArtifactLinkField::class,
            'cross'    => \Tuleap\Tracker\FormElement\Field\CrossReference\XML\XMLCrossReferenceField::class,
            'burndown' => \Tuleap\Tracker\FormElement\Field\Burndown\XML\XMLBurndownField::class,
            'luby'     => \Tuleap\Tracker\FormElement\Field\LastUpdateBy\XML\XMLLastModifiedByField::class,
            'column'   => \Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn::class,
            'fieldset' => \Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset::class,
        ];

        $type = (string) $xml['type'];

        switch ($type) {
            // Containers
            case 'fieldset':
            case 'column':
                return new ContainerConvertor($xml, $xml_tracker, $mapping[$type]);
            // Fields
            case 'subon':
            case 'subby':
            case 'aid':
            case 'art_link':
            case 'burndown':
            case 'cross':
            case 'float':
            case 'int':
            case 'luby':
            case 'lud':
            case 'string':
            case 'file':
                return new FieldConvertor($xml, $xml_tracker, $mapping[$type]);
            case 'date':
                return new DateConvertor($xml, $xml_tracker, $mapping[$type]);
            case 'sb':
                $bind_type = (string) $xml->bind['type'];
                if ($bind_type === 'static' || $bind_type === 'users') {
                    return new SelectboxConvertor($xml, $xml_tracker, $mapping[$type]);
                } else {
                    $output->error(sprintf('%s sb are not implemented yet', $bind_type));
                }
                break;
            case 'text':
                return new TextConvertor($xml, $xml_tracker, $mapping[$type]);
            // to implement
            case 'computed':
            case 'msb':
            case 'rb':
            case 'cb':
            case 'perm':
            case 'tbl':
            case 'shared':
            case 'priority':
            // Static fields
            case 'linebreak':
            case 'separator':
            case 'staticrichtext':
                $output->error(sprintf('Form element type %s is not implemented yet', $type));
        }

        return null;
    }
}
