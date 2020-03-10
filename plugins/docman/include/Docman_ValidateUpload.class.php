<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Docman_ValidateUpload extends Docman_Validator
{
    public function __construct(Codendi_Request $request)
    {
        if (!$request->exist('upload_content')) {
            $ok = false;
            if (isset($_FILES['file'])) {
                switch ($_FILES['file']['error']) {
                    case UPLOAD_ERR_OK:
                        // all is OK
                        $ok = true;
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). The uploaded file exceeds the maximum allowed file size.'), $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). The uploaded file was only partially uploaded.'), $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). No file was uploaded.'), $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). Missing a temporary folder.'), $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). Failed to write file to disk.'), $_FILES['file']['error']));
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). File upload stopped by extension.'), $_FILES['file']['error']));
                        break;
                    default:
                        $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). Unknown error code.'), $_FILES['file']['error']));
                }
            }
            if ($ok && isset($_FILES['file']['size']) && (int) $_FILES['file']['size'] >= (int) ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)) {
                $ok = false;
                $this->addError(sprintf(dgettext('tuleap-docman', 'File upload error(%1$s). The uploaded file exceeds the maximum allowed file size.'), $_FILES['file']['name'] ?? ''));
            }
            if (!isset($_FILES['file']) || ($ok && $_FILES['file']['name'] == '')) {
                $this->addError(dgettext('tuleap-docman', 'File upload error. Please try again.'));
            }
        }
    }
}
