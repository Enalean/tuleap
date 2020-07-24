<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Docman_MIMETypeDetector
{

    private $office_types = [
        '.doc'  => 'application/msword',
        '.dot'  => 'application/msword',
        '.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        '.dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        '.docm' => 'application/vnd.ms-word.document.macroEnabled.12',
        '.dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
        '.xls'  => 'application/vnd.ms-excel',
        '.xlt'  => 'application/vnd.ms-excel',
        '.xla'  => 'application/vnd.ms-excel',
        '.xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        '.xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        '.xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
        '.xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
        '.xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
        '.xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
        '.ppt'  => 'application/vnd.ms-powerpoint',
        '.pot'  => 'application/vnd.ms-powerpoint',
        '.pps'  => 'application/vnd.ms-powerpoint',
        '.ppa'  => 'application/vnd.ms-powerpoint',
        '.pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        '.potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
        '.ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        '.ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
        '.pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
        '.potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
        '.ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
        '.xps'  => 'application/vnd.ms-xpsdocument'
    ];

    /**
     * @param string $filename
     *
     * @return bool True if the file is an office one
     */
    public function isAnOfficeFile($filename)
    {
        return $this->getRightOfficeType($filename) != null;
    }

    /**
     * @param string $filename
     *
     * @return string The mime type corresponding to the extension
     */
    public function getRightOfficeType($filename)
    {
        $file_extension = '.' . pathinfo($filename, PATHINFO_EXTENSION);

        if (isset($this->office_types[$file_extension])) {
            return $this->office_types[$file_extension];
        }

        return null;
    }
}
