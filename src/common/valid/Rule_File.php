<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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

// Needed for 2 GB workaround
class Rule_File extends \Rule // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $maxSize;
    public function __construct()
    {
        $this->maxSize = \ForgeConfig::get('sys_max_size_upload');
    }
    /**
     * Check file upload validity
     *
     * @param  string|array $file  One entry in $_FILES superarray (e.g. $_FILES['test'])
     * @return bool Is file upload valid or not.
     */
    public function isValid($file)
    {
        $ok = \false;
        if (\is_array($file)) {
            switch ($file['error']) {
                case \UPLOAD_ERR_OK:
                    // all is OK
                    $ok = \true;
                    break;
                case \UPLOAD_ERR_INI_SIZE:
                case \UPLOAD_ERR_FORM_SIZE:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_size', $file['error']);
                    break;
                case \UPLOAD_ERR_PARTIAL:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_partial', $file['error']);
                    break;
                case \UPLOAD_ERR_NO_FILE:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_nofile', $file['error']);
                    break;
                default:
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_unknown', $file['error']);
            }
            if ($ok && $file['name'] == '') {
                $ok = \false;
                $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload');
            }
            if ($ok) {
                // Re-check filesize (do not trust uploaded MAX_FILE_SIZE)
                if (\filesize($file['tmp_name']) > $this->maxSize) {
                    $ok = \false;
                    $this->error = $GLOBALS['Language']->getText('rule_file', 'error_upload_size', 1);
                }
            }
        }
        return $ok;
    }
}
