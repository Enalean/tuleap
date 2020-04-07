<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman;

use Docman_Folder;
use Exception;

class DeleteFailedException extends Exception
{
    /**
     * @var string
     */
    private $i18n_message;

    private function __construct(string $message, string $i18n_message)
    {
        parent::__construct($message);
        $this->i18n_message = $i18n_message;
    }

    public static function missingPermissionSubItems(): self
    {
        return new self(
            "missing permission on a sub item",
            dgettext(
                'tuleap-docman',
                "Cannot delete this item because it contains items you are not allowed to modify (no 'Write' access, you may even not be able to read them). Please contact your document manager administrator."
            )
        );
    }

    public static function fromFile(\Docman_File $item): self
    {
        return new self(
            "Missing permission to delete " . $item->getTitle(),
            sprintf(
                dgettext(
                    'tuleap-docman',
                    'You do not have sufficient access rights to delete %s.'
                ),
                $item->getTitle()
            )
        );
    }

    public static function fromWiki(): self
    {
        return new self(
            "Can't delete wiki page",
            dgettext('tuleap-docman', 'Error while trying to delete the wiki page from wiki service.')
        );
    }

    public static function fromItem(\Docman_Item $item): self
    {
        return new self(
            "Missing permission to delete " . $item->getTitle(),
            sprintf(
                dgettext(
                    'tuleap-docman',
                    'You do not have sufficient access rights to delete %s.'
                ),
                $item->getTitle()
            )
        );
    }

    public static function fromFolder(Docman_Folder $item): self
    {
        return new self(
            "Folder is not empty: " . $item->getTitle(),
            sprintf(
                dgettext(
                    'tuleap-docman',
                    'Can\'t delete folder %s because it is not empty.'
                ),
                $item->getTitle()
            )
        );
    }

    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
