<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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

use phpseclib3\Exception\NoKeyLoadedException;
use Tuleap\TrackerEncryption\Dao\TrackerPublicKeyDao;
use Tuleap\TrackerEncryption\Dao\ValueDao;

//phpcs:ignoreFile
class Tracker_Key
{
    private $key;
    private $id_tracker;

    /**
     * @var TrackerPublicKeyDao
     */
    private $dao_pub_key;

    /**
     * @var ValueDao
     */
    private $value_dao;

    public function __construct(TrackerPublicKeyDao $dao_pub_key, ValueDao $value_dao, $id_tracker, $key = "")
    {
        $this->dao_pub_key = $dao_pub_key;
        $this->id_tracker  = $id_tracker;
        $this->key         = $key;
        $this->value_dao   = $value_dao;
    }

    public function getKey()
    {
        return $this->dao_pub_key->retrieveKey($this->id_tracker);
    }

    public function associateKeyToTracker()
    {
        $this->dao_pub_key->insertKey($this->id_tracker, $this->key);
    }

    public function deleteTrackerKey($tracker_id)
    {
        $this->dao_pub_key->deleteKey($tracker_id);
    }

    public function historizeKey($group_id)
    {
        $dao = new ProjectHistoryDao();
        $dao->groupAddHistory($GLOBALS['Language']->getText('project_admin_utils', 'Tracker_key'), $this->getKey(), $group_id, false);
    }

    /**
     * Verify the validity of a given RSA public key
     * @param $key
     *
     * @return bool
     */
    public function isValidPublicKey($key)
    {
        preg_match('/-----BEGIN PUBLIC KEY-----(.*)-----END PUBLIC KEY-----$/s', $key, $match);
        if (! empty($match)) {
            try {
                $public_key = \phpseclib3\Crypt\PublicKeyLoader::load($key);
            } catch (NoKeyLoadedException $exception) {
                return false;
            }
            if (! ($public_key instanceof \phpseclib3\Crypt\RSA\PublicKey) || $public_key->getLength() < 2048 || $public_key->getLength() > 8192) {
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    public function resetEncryptedFieldValues($tracker_id)
    {
        $this->value_dao->resetEncryptedFieldValues($tracker_id);
    }

    /**
     * Defining the maximum characters for the encrypted field refered to the minimum RSA key's permissible size
     * @param $key
     *
     * @return int
     */
    public function getFieldSize($key)
    {
        if ($key === '') {
            return 0;
        }
        try {
            $rsa = \phpseclib3\Crypt\PublicKeyLoader::load($key);
            assert($rsa instanceof \phpseclib3\Crypt\RSA\PublicKey);
        } catch (NoKeyLoadedException $exception) {
            return 0;
        }
        return ($rsa->getLength() - 2 * $rsa->getHash()->getLength() - 16) >> 3;
    }
}
