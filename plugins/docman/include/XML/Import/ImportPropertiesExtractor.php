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

use DateTimeImmutable;
use PFUser;
use SimpleXMLElement;
use Tuleap\xml\InvalidDateException;
use Tuleap\xml\XMLDateHelper;
use User\XML\Import\IFindUserFromXMLReference;
use User\XML\Import\UserNotFoundException;

class ImportPropertiesExtractor
{
    /**
     * @var DateTimeImmutable
     */
    private $current_date;
    /**
     * @var IFindUserFromXMLReference
     */
    private $user_finder;
    /**
     * @var PFUser
     */
    private $current_user;

    public function __construct(
        DateTimeImmutable $current_date,
        PFUser $current_user,
        IFindUserFromXMLReference $user_finder
    ) {
        $this->current_date = $current_date;
        $this->user_finder  = $user_finder;
        $this->current_user = $current_user;
    }

    /**
     * @throws UnknownItemTypeException
     * @throws InvalidDateException
     * @throws UserNotFoundException
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

        $owner = $this->current_user;
        if ($node->properties->owner) {
            $owner = $this->user_finder->getUser($node->properties->owner);
        }

        switch ($type) {
            case NodeImporter::TYPE_FILE:
                $properties = ImportProperties::buildFile($title, $description, $create_date, $update_date, $owner);
                break;

            case NodeImporter::TYPE_EMBEDDEDFILE:
                $properties = ImportProperties::buildEmbedded($title, $description, $create_date, $update_date, $owner);
                break;

            case NodeImporter::TYPE_LINK:
                $properties = ImportProperties::buildLink(
                    $title,
                    $description,
                    (string) $node->url,
                    $create_date,
                    $update_date,
                    $owner
                );
                break;

            case NodeImporter::TYPE_EMPTY:
                $properties = ImportProperties::buildEmpty($title, $description, $create_date, $update_date, $owner);
                break;

            case NodeImporter::TYPE_FOLDER:
                $properties = ImportProperties::buildFolder($title, $description, $create_date, $update_date, $owner);
                break;
            default:
                throw new UnknownItemTypeException($type);
        }

        return $properties;
    }
}
