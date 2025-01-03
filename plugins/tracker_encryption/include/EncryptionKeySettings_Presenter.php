<?php
/**
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Tuleap\TrackerEncryption\Dao\TrackerPublicKeyDao;
use Tuleap\TrackerEncryption\Dao\ValueDao;

//phpcs:ignoreFile
class Tracker_EncryptionKeySettings_Presenter
{
    public $action_url;
    private $tracker_id;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct($tracker_id, $action_url, CSRFSynchronizerToken $csrf_token)
    {
        $this->tracker_id = $tracker_id;
        $this->action_url = $action_url;
        $this->csrf_token = $csrf_token;
    }

    public function display_help()
    {
        return dgettext('tuleap-tracker_encryption', 'Please add a valid RSA public key.This key will be used to encrypt form elements of type "encrypted" that belongs to this tracker.');
    }

    public function submit_button()
    {
        return $GLOBALS['Language']->getText('global', 'save_change');
    }

    public function cancel_button()
    {
        return gettext('Cancel');
    }

    public function tracker_key_label()
    {
        return dgettext('tuleap-tracker_encryption', 'Tracker key');
    }

    public function update_key_warning()
    {
        return dgettext('tuleap-tracker_encryption', 'You are about to update your key, You will lost all your encrypted fields values for this tracker if you do this.');
    }

    /**
     * @return string tracker key formatted in one line
     */
    public function get_tracker_key()
    {
        $tracker_key = new Tracker_Key(
            new TrackerPublicKeyDao(),
            new ValueDao(
                new Tracker_Artifact_Changeset_ValueDao()
            ),
            $this->tracker_id
        );

        return $tracker_key->getKey();
    }
}
