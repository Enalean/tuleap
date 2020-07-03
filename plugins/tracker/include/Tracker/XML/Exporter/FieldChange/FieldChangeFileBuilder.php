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
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\File\IdForXMLImportExportConvertor;

class FieldChangeFileBuilder
{
    public function build(
        SimpleXMLElement $changeset_xml,
        string $field_name,
        array $fileinfo_ids
    ): void {
        $field_change_node = $changeset_xml->addChild('field_change');
        $field_change_node->addAttribute('type', Tracker_FormElementFactory::FIELD_FILE_TYPE);
        $field_change_node->addAttribute('field_name', $field_name);

        foreach ($fileinfo_ids as $fileinfo_id) {
            $value_node = $field_change_node->addChild('value');
            $value_node->addAttribute('ref', IdForXMLImportExportConvertor::convertFileInfoIdToXMLId($fileinfo_id));
        }
    }
}
