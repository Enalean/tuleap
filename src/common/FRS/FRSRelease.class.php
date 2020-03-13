<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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


class FRSRelease
{
    public const PERM_READ        = 'RELEASE_READ';

    public const STATUS_ACTIVE  = 1;
    public const STATUS_DELETED = 2;
    public const STATUS_HIDDEN  = 3;

    public const EVT_CREATE = 201;
    public const EVT_UPDATE = 202;
    public const EVT_DELETE = 203;

    /**
     * @var int $release_id the ID of this FRSRelease
     */
    public $release_id;
    /**
     * @var int $package_id the ID of the package this FRSRelease belong to
     */
    public $package_id;
    /**
     * @var string $name the name of this FRSRelease
     */
    public $name;
    /**
     * @var string $notes the notes of this FRSRelease
     */
    public $notes;
    /**
     * @var int $changes the changes of this FRSRelease
     */
    public $changes;
    /**
     * @var int $status_id the ID of the status of this FRSRelease
     */
    public $status_id;
    /**
     * @var int $preformatted 1 if the text is preformatted, 0 otherwise
     */
    public $preformatted;
    /**
     * @var int $release_date the creation date of this FRSRelease
     */
    public $release_date;
    /**
     * @var int $released_by the ID of the user who creates this FRSRelease
     */
    public $released_by;


    /**
     * @var Project $project Project the release belongs to
     */
    protected $project;
    /**
     * @var int $group_id Project ID the release belongs to
     */
    protected $group_id;

    public function __construct($data_array = null)
    {
        $this->release_id       = null;
        $this->package_id       = null;
        $this->name             = null;
        $this->notes            = null;
        $this->changes          = null;
        $this->status_id        = null;
        $this->preformatted     = null;
        $this->release_date     = null;
        $this->released_by      = null;

        if ($data_array) {
            $this->initFromArray($data_array);
        }
    }

    public function getReleaseID(): int
    {
        return (int) $this->release_id;
    }

    public function setReleaseID($release_id)
    {
        $this->release_id = (int) $release_id;
    }

    public function getPackageID()
    {
        return $this->package_id;
    }

    public function setPackageID($package_id)
    {
        $this->package_id = (int) $package_id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function setChanges($changes)
    {
        $this->changes = $changes;
    }

    public function getStatusID()
    {
        return $this->status_id;
    }

    public function setStatusID($status_id)
    {
        $this->status_id = $status_id;
    }

    public function getPreformatted()
    {
        return $this->preformatted;
    }

    public function setPreformatted($preformatted)
    {
        $this->preformatted = $preformatted;
    }

    public function getReleaseDate()
    {
        return $this->release_date;
    }

    public function setReleaseDate($release_date)
    {
        $this->release_date = $release_date;
    }

    public function getReleasedBy()
    {
        return $this->released_by;
    }

    public function setReleasedBy($released_by)
    {
        $this->released_by = $released_by;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        if (!isset($this->project)) {
            $this->project = $this->_getProjectManager()->getProject($this->getGroupID());
        }
        return $this->project;
    }

    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * Determines if the release is active or not
     * @return bool true if the release is active, false otherwise
     */
    public function isActive()
    {
        $release_factory = new FRSReleaseFactory();
        return $this->getStatusID() == $release_factory->STATUS_ACTIVE;
    }

    /**
     * Determines if the release is hidden or not
     * @return bool true if the release is hidden, false otherwise
     */
    public function isHidden()
    {
        $release_factory = new FRSReleaseFactory();
        return $this->getStatusID() == $release_factory->STATUS_HIDDEN;
    }

    /**
     * Determines if the release is deleted or not
     * @return bool true if the release is boolean, false otherwise
     */
    public function isDeleted()
    {
        $release_factory = new FRSReleaseFactory();
        return $this->getStatusID() == $release_factory->STATUS_DELETED;
    }

    /**
     * Determines if the release notes and changes are preformatted or not
     * @return bool true if the release notes and changes are preformatted, false otherwise
     */
    public function isPreformatted()
    {
        return $this->getPreformatted() == 1;
    }

    /**
     * Set group id
     */
    public function setGroupID($group_id)
    {
        $this->group_id = $group_id;
    }

    /**
     * Returns the group ID the release belongs to
     */
    public function getGroupID()
    {
        if (!isset($this->group_id)) {
            if (isset($this->project)) {
                $this->group_id = $this->project->getID();
            } else {
                $package = $this->_getFRSPackageFactory()->getFRSPackageFromDb($this->getPackageID(), null, FRSPackageDao::INCLUDE_DELETED);
                $this->group_id = $package->getGroupID();
            }
        }
        return $this->group_id;
    }

    /**
     * @return FRSPackage
     */
    public function getPackage()
    {
        return $this->_getFRSPackageFactory()->getFRSPackageFromDb($this->getPackageID());
    }

    public function initFromArray($array)
    {
        if (isset($array['release_id'])) {
            $this->setReleaseID($array['release_id']);
        }
        if (isset($array['package_id'])) {
            $this->setPackageID($array['package_id']);
        }
        if (isset($array['name'])) {
            $this->setName($array['name']);
        }
        if (isset($array['notes'])) {
            $this->setNotes($array['notes']);
        }
        if (isset($array['changes'])) {
            $this->setChanges($array['changes']);
        }
        if (isset($array['status_id'])) {
            $this->setStatusID($array['status_id']);
        }
        if (isset($array['preformatted'])) {
            $this->setPreformatted($array['preformatted']);
        }
        if (isset($array['release_date'])) {
            $this->setReleaseDate($array['release_date']);
        }
        if (isset($array['released_by'])) {
            $this->setReleasedBy($array['released_by']);
        }
    }

    public function toArray()
    {
        $array = array();
        $array['release_id']   = $this->getReleaseID();
        $array['package_id']   = $this->getPackageID();
        $array['name']         = $this->getName();
        $array['notes']        = $this->getNotes();
        $array['changes']      = $this->getChanges();
        $array['status_id']    = $this->getStatusID();
        $array['preformatted'] = $this->getPreformatted();
        $array['release_date'] = $this->getReleaseDate();
        $array['released_by'] = $this->getReleasedBy();
        return $array;
    }

    /**
     * Associative array of data from db.
     *
     * @var  array   $data_array.
     */
    public $data_array;
    public $release_files;

    /**
     *    getFiles - gets all the file objects for files in this release.
     *
     *    return    array    Array of FRSFile Objects.
     */
    public function &getFiles()
    {
        if (!is_array($this->release_files) || count($this->release_files) < 1) {
            $this->release_files = array();
            $frsff = new FRSFileFactory();
            $this->release_files = $frsff->getFRSFilesFromDb($this->getReleaseID());
        }
        return $this->release_files;
    }

    public function userCanRead($user_id = 0)
    {
        $release_factory = new FRSReleaseFactory();

        return $release_factory->userCanRead($this->getGroupID(), $this->getPackageID(), $this->getReleaseID(), $user_id);
    }

    /**
     * Returns the HTML content for tooltip when hover a reference with the nature release
     * @returns string HTML content for release tooltip
     */
    public function getReferenceTooltip()
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        $tooltip = '';
        $package_id = $this->getPackageID();
        $pf = new FRSPackageFactory();
        $package = $pf->getFRSPackageFromDb($package_id);
        $tooltip .= '<table>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'release_name') . ':</strong></td>';
        $tooltip .= '  <td>' . $html_purifier->purify($this->getName()) . '</td>';
        $tooltip .= ' </tr>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_admin_editpackages', 'p_name') . ':</strong></td>';
        $tooltip .= '  <td>' . $html_purifier->purify($package->getName()) . '</td>';
        $tooltip .= ' </tr>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_showfiles', 'date') . ':</strong></td>';
        $tooltip .= '  <td>' . $html_purifier->purify(format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), $this->getReleaseDate())) . '</td>';
        $tooltip .= ' </tr>';
        $tooltip .= '</table>';
        return $tooltip;
    }

    /**
     * Get a Package Factory
     *
     * @return FRSPackageFactory
     */
    public function _getFRSPackageFactory()
    {
        return FRSPackageFactory::instance();
    }

    /**
     * Get ProjectManager
     *
     * @return ProjectManager
     */
    public function _getProjectManager()
    {
        return ProjectManager::instance();
    }
}
