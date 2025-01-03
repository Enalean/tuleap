<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Tuleap\FRS\FRSPermissionManager;

class FRSFile
{
    public const EVT_CREATE  = 301;
    public const EVT_UPDATE  = 302;
    public const EVT_DELETE  = 303;
    public const EVT_RESTORE = 304;

    /**
     * @var int $file_id the ID of this FRSFile
     */
    public $file_id;
    /**
     * @var String $filename the name of this FRSFile
     */
    public $filename;
    /**
     * @var String $filepath the full path where the file is created
     */
    public $filepath;
    /**
     * @var int $release_id the ID of the release this FRSFile belong to
     */
    public $release_id;
    /**
     * @var int $type_id the ID of the type of this FRSFile
     */
    public $type_id;
    /**
     * @var int $processor_id the ID of the processor to use with this FRSFile
     */
    public $processor_id;
    /**
     * @var int $release_time the ??? of this FRSFile
     */
    public $release_time;
    /**
     * @var int $file_size the size of this FRSFile
     */
    public $file_size;
    /**
     * @var int $post_date the ??? of this FRSFile
     */
    public $post_date;
    /**
     * @var string $status the status of this FRSFile (A=>Active; D=>Deleted)
     */
    public $status;
    /**
     * @var string $computed_md5 hash of the file computed in server side
     */
    public $computed_md5;
    /**
     * @var string $reference_md5 hash of the file submited by user (calculated in client side)
     */
    public $reference_md5;
    /**
     * @var int $user_id id of user that created the file
     */
    public $user_id;
    /**
     * @var string $file_location the full path of this FRSFile
     */
    public $file_location;
    /**
     * @var Group $group the project this file belong to
     */
    private $group;

    /**
     *
     * @var string comment/description of the file
     */
    private $comment;

    /**
     * @var FRSRelease $release The release the file belongs to
     */
    protected $release;

    public function __construct($data_array = null)
    {
        $this->file_id       = null;
        $this->filename      = null;
        $this->filepath      = null;
        $this->release_id    = null;
        $this->type_id       = null;
        $this->processor_id  = null;
        $this->release_time  = null;
        $this->file_size     = null;
        $this->post_date     = null;
        $this->status        = null;
        $this->computed_md5  = null;
        $this->reference_md5 = null;
        $this->user_id       = null;
        $this->file_location = null;
        $this->comment       = null;

        if ($data_array) {
            $this->initFromArray($data_array);
        }
    }

    public function getFileID()
    {
        return $this->file_id;
    }

    public function setFileID($file_id)
    {
        $this->file_id = (int) $file_id;
    }

    public function getFileName()
    {
        return $this->filename;
    }

    public function setFileName($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Obtain the name of the file as stored in filesystem
     * Old files are stored in the filesystem as uploaded by the user
     * In that case filepath == NULL then the returned value is filename
     *
     * @return String
     */
    public function getFilePath()
    {
        if ($this->filepath == null) {
            return $this->filename;
        } else {
            return $this->filepath;
        }
    }

    public function setFilePath($filepath)
    {
        $this->filepath = $filepath;
    }

    public function getReleaseID()
    {
        return $this->release_id;
    }

    public function setReleaseID($release_id)
    {
        $this->release_id = (int) $release_id;
    }

    public function getTypeID()
    {
        return $this->type_id;
    }

    public function setTypeID($type_id)
    {
        $this->type_id = (int) $type_id;
    }

    public function getProcessorID()
    {
        return $this->processor_id;
    }

    public function setProcessorID($processor_id)
    {
        $this->processor_id = (int) $processor_id;
    }

    public function getReleaseTime()
    {
        return $this->release_time;
    }

    public function setReleaseTime($release_time)
    {
        $this->release_time = (int) $release_time;
    }

    public function setFileLocation($location)
    {
        $this->file_location = $location;
    }

    /**
     * Returns the location of the file on the server
     *
     * @return string the location of this file on the server
     */
    public function getFileLocation()
    {
        if ($this->file_location == null) {
            $group               = $this->getGroup();
            $group_unix_name     = $group->getUnixName(false);
            $basename            = $this->getFilePath();
            $this->file_location = ForgeConfig::get('ftp_frs_dir_prefix') . '/' . $group_unix_name . '/' . $basename;
        }
        return $this->file_location;
    }

    public function getFileSize()
    {
        if ($this->file_size == null) {
            $file_location   = $this->getFileLocation();
            $this->file_size = \filesize($file_location);
        }
        return $this->file_size;
    }

    public function setFileSize($file_size)
    {
        $this->file_size = $file_size;
    }

    public static function convertBytesToKbytes($size_in_bytes, $decimals_precision = 0)
    {
        $size_in_kbytes = $size_in_bytes / 1024;

        $decimal_separator  = $GLOBALS['Language']->getText('system', 'decimal_separator');
        $thousand_separator = $GLOBALS['Language']->getText('system', 'thousand_separator');
        // because I don't know how to specify a space in a .tab file
        if ($thousand_separator == "' '") {
            $thousand_separator = ' ';
        }
        return number_format($size_in_kbytes, $decimals_precision, $decimal_separator, $thousand_separator);
    }

    public function getDisplayFileSize()
    {
        $decimals_precision = 0;
        if ($this->getFileSize() < 1024) {
            $decimals_precision = 2;
        }
        return $this->convertBytesToKbytes($this->getFileSize(), $decimals_precision);
    }

    public function getPostDate()
    {
        return $this->post_date;
    }

    public function setPostDate($post_date)
    {
        $this->post_date = (int) $post_date;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function isActive()
    {
        return ($this->status == 'A');
    }

    public function isDeleted()
    {
        return ($this->status == 'D');
    }

    public function setComputedMd5($computedMd5)
    {
        $this->computed_md5 = $computedMd5;
    }

    public function getComputedMd5()
    {
        return $this->computed_md5;
    }

    public function setReferenceMd5($referenceMd5)
    {
        $this->reference_md5 = $referenceMd5;
    }

    public function getReferenceMd5()
    {
        return $this->reference_md5;
    }

    public function setUserID($userId)
    {
        $this->user_id = $userId;
    }

    public function getUserID()
    {
        return $this->user_id;
    }

    public function setRelease($release)
    {
        $this->release    = $release;
        $this->release_id = $release->getReleaseID();
    }

    public function getRelease()
    {
        return $this->release;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function initFromArray($array)
    {
        if (isset($array['file_id'])) {
            $this->setFileID($array['file_id']);
        }
        if (isset($array['filename'])) {
            $this->setFileName($array['filename']);
        }
        if (isset($array['filepath'])) {
            $this->setFilePath($array['filepath']);
        }
        if (isset($array['release_id'])) {
            $this->setReleaseID($array['release_id']);
        }
        if (isset($array['type_id'])) {
            $this->setTypeID($array['type_id']);
        }
        if (isset($array['processor_id'])) {
            $this->setProcessorID($array['processor_id']);
        }
        if (isset($array['release_time'])) {
            $this->setReleaseTime($array['release_time']);
        }
        if (isset($array['file_size'])) {
            $this->setFileSize($array['file_size']);
        }
        if (isset($array['post_date'])) {
            $this->setPostDate($array['post_date']);
        }
        if (isset($array['status'])) {
            $this->setStatus($array['status']);
        }
        if (isset($array['computed_md5'])) {
            $this->setComputedMd5($array['computed_md5']);
        }
        if (isset($array['reference_md5'])) {
            $this->setReferenceMd5($array['reference_md5']);
        }
        if (isset($array['user_id'])) {
            $this->setUserID($array['user_id']);
        }
        if (isset($array['comment'])) {
            $this->setComment($array['comment']);
        }
    }

    public function toArray()
    {
        $array                  = [];
        $array['file_id']       = $this->getFileID();
        $array['filename']      = $this->getFileName();
        $array['filepath']      = $this->getFilePath();
        $array['release_id']    = $this->getReleaseID();
        $array['type_id']       = $this->getTypeID();
        $array['processor_id']  = $this->getProcessorID();
        $array['release_time']  = $this->getReleaseTime();
        $array['file_location'] = $this->getFileLocation();
        $array['file_size']     = $this->getFileSize();
        $array['post_date']     = $this->getPostDate();
        $array['status']        = $this->getStatus();
        $array['computed_md5']  = $this->getComputedMd5();
        $array['reference_md5'] = $this->getReferenceMd5();
        $array['user_id']       = $this->getUserID();
        $array['comment']       = $this->getComment();

        return $array;
    }

    public $dao;

    public function &_getFRSFileDao()
    {
        if (! $this->dao) {
            $this->dao = new FRSFileDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    /**
     * Determine if the file exists really on the server or not
     *
     * @return bool true if the file exists on the server, false otherwise
     */
    public function fileExists()
    {
        return file_exists($this->getFileLocation());
    }

    /**
     * Get the Package ID of this File
     *
     * @return int the packahe ID of this file
     */
    public function getPackageID()
    {
        // retrieve the release the file belongs to
        $release_id   = $this->getReleaseID();
        $release_fact = new FRSReleaseFactory();
        $release      = $release_fact->getFRSReleaseFromDb($release_id);
        $package_id   = $release->getPackageID();
        return $package_id;
    }

    /**
     * Get the Group (the project) of this File
     *
     * @return Project the group the file belongs to
     */
    public function getGroup()
    {
        if (empty($this->group)) {
            $pm = ProjectManager::instance();
            // retrieve the release the file belongs to
            $release_id   = $this->getReleaseID();
            $release_fact = FRSReleaseFactory::instance();
            $release      = $release_fact->getFRSReleaseFromDb($release_id, null, null, FRSReleaseDao::INCLUDE_DELETED);
            $group_id     = $release->getGroupID();
            $this->group  = $pm->getProject($group_id);
        }
        return $this->group;
    }

    /**
     * Set the Group (the project) of this File
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    /**
     * Returns the content of the file, in a raw resource
     *
     * +2GB safe
     *
     * @return mixed the content of the file
     */
    public function getContent($offset = 0, $size = -1)
    {
        if ($size == -1) {
            $size = $this->getFileSize();
        }
        return file_get_contents(realpath($this->getFileLocation()), false, null, $offset, $size);
    }

    /**
     * Log the download of the file in the log system
     *
     * Only log one download attempt per file/user/hour.
     * in order to reduce the amount of download attempt logged.
     *
     * @param int $user_id the user that download the file (if 0, the current user will be taken)
     * @return bool true if there is no error, false otherwise
     */
    public function LogDownload($user_id = 0)
    {
        if ($user_id == 0) {
            $user_id = UserManager::instance()->getCurrentUser()->getId();
        }
        $time = $_SERVER['REQUEST_TIME'] - 3600;
        $dao  = $this->_getFrsFileDao();
        if (! $dao->existsDownloadLogSince($this->getFileID(), $user_id, $time)) {
            return $dao->logDownload($this, $user_id);
        }
        return true;
    }

    /**
     * userCanDownload : determine if the user can download the file or not
     *
     * WARNING : for the moment, user can download the file if the user can view the package and can view the release the file belongs to.
     *
     * @return bool true if the user has permissions to download the file, false otherwise
     */
    public function userCanDownload(PFUser $user): bool
    {
        $project            = $this->getGroup();
        $permission_manager = FRSPermissionManager::build();
        if ($permission_manager->isAdmin($project, $user)) {
            return true;
        }

        $user_can_download = false;
        if (! $this->isDeleted()) {
            $user_id    = $user->getId();
            $project_id = $project->getID();
            if (permission_exist('RELEASE_READ', $this->getReleaseID())) {
                if (permission_is_authorized('RELEASE_READ', $this->getReleaseID(), $user_id, $project_id)) {
                    $user_can_download = true;
                }
            } elseif (permission_is_authorized('PACKAGE_READ', $this->getPackageID(), $user_id, $project_id)) {
                $user_can_download = true;
            }
        }
        return $user_can_download;
    }

    /**
     * Returns the HTML content for tooltip when hover a reference with the nature file
     * @returns string HTML content for file tooltip
     */
    public function getReferenceTooltip()
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        $tooltip       = '';
        $rf            = new FRSReleaseFactory();
        $pf            = new FRSPackageFactory();
        $release_id    = $this->getReleaseID();
        $release       = $rf->getFRSReleaseFromDb($release_id);
        $package_id    = $release->getPackageID();
        $package       = $pf->getFRSPackageFromDb($package_id);
        $tooltip      .= '<table>';
        $tooltip      .= ' <tr>';
        $tooltip      .= '  <td><strong>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'filename') . ':</strong></td>';
        $tooltip      .= '  <td>' . $html_purifier->purify(basename($this->getFileName())) . '</td>';
        $tooltip      .= ' </tr>';
        $tooltip      .= ' <tr>';
        $tooltip      .= '  <td><strong>' . $GLOBALS['Language']->getText('file_ref_tooltip', 'package_release') . ':</strong></td>';
        $tooltip      .= '  <td>' . $html_purifier->purify($package->getName() . ' / ' . $release->getName()) . '</td>';
        $tooltip      .= ' </tr>';
        $tooltip      .= ' <tr>';
        $tooltip      .= '  <td><strong>' . $GLOBALS['Language']->getText('file_showfiles', 'date') . ':</strong></td>';
        $tooltip      .= '  <td>' . $html_purifier->purify(format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), $release->getReleaseDate())) . '</td>';
        $tooltip      .= ' </tr>';
        $tooltip      .= ' <tr>';
        $tooltip      .= '  <td><strong>' . $GLOBALS['Language']->getText('file_showfiles', 'size') . ':</strong></td>';
        $tooltip      .= '  <td>' . $html_purifier->purify($this->getDisplayFileSize()) . '</td>';
        $tooltip      .= ' </tr>';
        $tooltip      .= '</table>';
        return $tooltip;
    }
}
