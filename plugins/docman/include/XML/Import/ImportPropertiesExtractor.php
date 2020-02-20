<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Docman\XML\Import;

use SimpleXMLElement;
use Tuleap\xml\XMLDateHelper;

class ImportPropertiesExtractor
{
    /**
     * @var \DateTimeImmutable
     */
    private $current_date;

    public function __construct(\DateTimeImmutable $current_date)
    {
        $this->current_date = $current_date;
    }

    /**
     * @throws UnknownItemTypeException|\Tuleap\xml\InvalidDateException
     */
    public function getImportProperties(SimpleXMLElement $node): ImportProperties
    {
        $type        = (string) $node['type'];
        $title       = (string) $node->properties->title;
        $description = (string) $node->properties->description;

        $update_date = $this->current_date;
        if ($node->properties->update_date) {
            $update_date = XMLDateHelper::extractFromNode($node->properties->update_date);
        }

        $create_date = $update_date;
        if ($node->properties->create_date) {
            $create_date = XMLDateHelper::extractFromNode($node->properties->create_date);
        }

        switch ($type) {
            case NodeImporter::TYPE_FILE:
                $properties = ImportProperties::buildFile($title, $description, $create_date, $update_date);
                break;

            case NodeImporter::TYPE_EMBEDDEDFILE:
                $properties = ImportProperties::buildEmbedded($title, $description, $create_date, $update_date);
                break;

            case NodeImporter::TYPE_WIKI:
                $properties = ImportProperties::buildWiki(
                    $title,
                    $description,
                    (string) $node->pagename,
                    $create_date,
                    $update_date
                );
                break;

            case NodeImporter::TYPE_LINK:
                $properties = ImportProperties::buildLink(
                    $title,
                    $description,
                    (string) $node->url,
                    $create_date,
                    $update_date
                );
                break;

            case NodeImporter::TYPE_EMPTY:
                $properties = ImportProperties::buildEmpty($title, $description, $create_date, $update_date);
                break;

            case NodeImporter::TYPE_FOLDER:
                $properties = ImportProperties::buildFolder($title, $description, $create_date, $update_date);
                break;
            default:
                throw new UnknownItemTypeException($type);
        }

        return $properties;
    }
}
