<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

class FRSFileInvalidNameException extends \FRSFileException // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param int $code Numeric code
     */
    public function __construct($file, $code = 0)
    {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'filename_invalid') . ': ' . $file->getFileName(), $code);
    }
}
