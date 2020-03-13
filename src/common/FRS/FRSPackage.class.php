<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

/**
 *
 * FRSPackage.class.php - File Release System Package class
 *
 */
class FRSPackage
{
    public const PERM_READ      = 'PACKAGE_READ';

    public const STATUS_ACTIVE  = 1;
    public const STATUS_DELETED = 2;
    public const STATUS_HIDDEN  = 3;

    public const EVT_CREATE = 101;
    public const EVT_UPDATE = 102;
    public const EVT_DELETE = 103;

    /**
     * @var int $package_id the ID of this FRSPackage
     */
    public $package_id;
    /**
     * @var int $group_id the ID of the group this FRSPackage belong to
     */
    public $group_id;
    /**
     * @var string $name the name of this FRSPackage
     */
    public $name;
    /**
     * @var int $status_id the ID of the status of this FRSPackage
     */
    public $status_id;
    /**
     * @var int $rank the rank of this FRSPackage
     */
    public $rank;
    /**
     * @var bool $approve_license true if the license has been approved, false otherwise
     */
    private $approve_license;

    public function __construct($data_array = null)
    {
        $this->package_id       = null;
        $this->group_id         = null;
        $this->name             = null;
        $this->status_id        = null;
        $this->rank             = null;

        if ($data_array) {
            $this->initFromArray($data_array);
        }
    }

    public function getPackageID()
    {
        return $this->package_id;
    }
    public function setPackageID($package_id)
    {
        $this->package_id = (int) $package_id;
    }
    public function getGroupID()
    {
        return $this->group_id;
    }
    public function setGroupID($group_id)
    {
        $this->group_id = (int) $group_id;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getStatusID()
    {
        return $this->status_id;
    }
    public function setStatusID($status_id)
    {
        $this->status_id = (int) $status_id;
    }
    public function getRank()
    {
        return $this->rank;
    }
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    public function getApproveLicense(): bool
    {
        return $this->approve_license;
    }

    /**
     * @param bool|string $approve_license
     */
    public function setApproveLicense($approve_license): void
    {
        $this->approve_license = (bool) $approve_license;
    }

    /**
     * Determines if the package is active or not
     * @return bool true if the package is active, false otherwise
     */
    public function isActive()
    {
        $frsrf = new FRSPackageFactory();
        return $this->getStatusID() == $frsrf->STATUS_ACTIVE;
    }

    /**
     * Determines if the package is hidden or not
     * @return bool true if the package is hidden, false otherwise
     */
    public function isHidden()
    {
        $frsrf = new FRSPackageFactory();
        return $this->getStatusID() == $frsrf->STATUS_HIDDEN;
    }

    /**
     * Determines if the package is deleted or not
     * @return bool true if the package is deleted, false otherwise
     */
    public function isDeleted()
    {
        $frsrf = new FRSPackageFactory();
        return $this->getStatusID() == $frsrf->STATUS_DELETED;
    }

    private function initFromArray(array $array): void
    {
        if (isset($array['package_id'])) {
            $this->setPackageID($array['package_id']);
        }
        if (isset($array['group_id'])) {
            $this->setGroupID($array['group_id']);
        }
        if (isset($array['name'])) {
            $this->setName($array['name']);
        }
        if (isset($array['status_id'])) {
            $this->setStatusID($array['status_id']);
        }
        if (isset($array['rank'])) {
            $this->setRank($array['rank']);
        }
        if (isset($array['approve_license'])) {
            $this->setApproveLicense($array['approve_license']);
        }
    }

    public function toArray(): array
    {
        $array = array();
        $array['package_id']      = $this->getPackageID();
        $array['group_id']        = $this->getGroupID();
        $array['name']            = $this->getName();
        $array['status_id']       = $this->getStatusID();
        $array['rank']            = $this->getRank();
        $array['approve_license'] = (string) $this->getApproveLicense();
        return $array;
    }

    /**
     * Associative array of data from db.
     *
     * @var  array   $data_array.
     */
    public $data_array;
    public $package_releases;


    /**
     *    getReleases - gets Release objects for all the releases in this package.
     *
     *  return  array   Array of FRSRelease Objects.
     */
    public function &getReleases()
    {
        if (!is_array($this->package_releases) || count($this->package_releases) < 1) {
            $this->package_releases = array();
            $frsrf = new FRSReleaseFactory();
            $this->package_releases = $frsrf->getFRSReleasesFromDb($this->getPackageID());
        }
        return $this->package_releases;
    }

    public function userCanRead($user_id = 0)
    {
        $factory = new FRSPackageFactory();

        return $factory->userCanRead($this->getGroupID(), $this->getPackageID(), $user_id);
    }
}
