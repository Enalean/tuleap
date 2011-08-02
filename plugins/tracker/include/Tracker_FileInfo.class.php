<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('dao/Tracker_FileInfoDao.class.php');

class Tracker_FileInfo {
    
    protected $id;
    protected $field;
    protected $submitted_by;
    protected $description;
    protected $filename;
    protected $filesize;
    protected $filetype;
    
    protected $supported_image_types = array('gif', 'png', 'jpeg', 'jpg');
    
    /**
     * @param integer                   $id
     * @param Tracker_FormElement_Field $field
     * @param integer                   $submitted_by
     * @param string                    $description
     * @param string                    $filename
     * @param integer                   $filesize
     * @param string                    $filetype
     */
    public function __construct($id, $field, $submitted_by, $description, $filename, $filesize, $filetype) {
        $this->id           = $id; 
        $this->field        = $field;
        $this->submitted_by = $submitted_by;
        $this->description  = $description;
        $this->filename     = $filename;
        $this->filesize     = $filesize;
        $this->filetype     = $filetype;
    }
    
    /**
     * @return string the description of the file
     */
    public function getDescription() {
        return $this->description;
    }
    
    /**
     * @return int the id of the user whos submitted the file
     */
    public function getSubmittedBy() {
        return $this->submitted_by;
    }
    
    /**
     * @return string the filename of the file
     */
    public function getFilename() {
        return $this->filename;
    }
    
    /**
     * @return string the size of the file
     */
    public function getFilesize() {
        return $this->filesize;
    }
    
    /**
     * Get the human readable file size
     *
     * @return string 
     */
    public function getHumanReadableFilesize() {
        $s = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
        $e = 0;
        if ($this->getFilesize()) {
            $e = floor(log($this->getFilesize()) / log(1024));
        }
        return sprintf('%.0f '.$s[$e], ($this->getFilesize() / pow(1024, floor($e))));
    }
    
    /**
     * @return string the type of the file
     */
    public function getFiletype() {
        return $this->filetype;
    }
    
    /**
     * @return int the id of the file
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * @return true if the file is a supported image
     */
    public function isImage() {
        $parts = split('/', $this->getFileType());
        return $parts[0] == 'image' && in_array(strtolower($parts[1]), $this->supported_image_types) ;
    }
    
    /**
     * @return string the filesystem path to the file
     */
    public function getPath() {
        return $this->getRootPath() .'/'. $this->id;
    }
    
    /**
     * @return string the filesystem path to the file
     */
    public function getThumbnailPath() {
        if ($this->isImage()) {
            return $this->getRootPath() .'/thumbnails/'. $this->id;
        }
        return null;
    }
    
    /**
     * Compute the root path of the filesystem
     * @return string
     */
    protected function getRootPath() {
        return $GLOBALS['sys_data_dir'] .'/tracker/'. $this->field->getId();
    }
    
    public function __toString() {
        return '#'. $this->getId() .' '. $this->getFilename();
    }
    
    /**
     * create a file info
     *
     * @param Tracker_FormElement_Field $field
     * @param integer                   $submitted_by
     * @param string                    $description
     * @param string                    $filename
     * @param integer                   $filesize
     * @param string                    $filetype
     *
     * @return Tracker_FileInfo
     */
    public static function create($field, $submitted_by, $description, $filename, $filesize, $filetype) {
        $instance = null;
        $dao = new Tracker_FileInfoDao();
        if ($id = $dao->create($submitted_by, $description, $filename, $filesize, $filetype)) {
            $instance = new Tracker_FileInfo($id, $field, $submitted_by, $description, $filename, $filesize, $filetype);
        }
        return $instance;
    }
    
    /**
     * delete a file info4
     *
     * @param Tracker_FileInfo $file_info
     *
     * @return boolean true on success
     */
    public static function delete(Tracker_FileInfo $file_info) {
        $dao = new Tracker_FileInfoDao();
        return $dao->create($file_info->getId());
    }
    
    protected static $instances_by_id;
    /**
     * get an instance of fileinfo, identified by its $id
     *
     * @param Tracker_FormElement_Field_File $field The field the fileinfo belongs to
     * @param int                            $id    The id of the fileinfo
     * @param array                          $row   The raw data of the fileinfo (optionnal)
     *
     * @return Tracker_FileInfo or null if not found
     */
    public static function instance(Tracker_FormElement_Field_File $field, $id, $row = null) {
        if (!isset(self::$instances_by_id[$id])) {
            self::$instances_by_id[$id] = null;
            $dao = new Tracker_FileInfoDao();
            //TODO: check that the attachment belongs to the field
            if ($row || ($row = $dao->searchById($id)->getRow())) {
                self::$instances_by_id[$id] = new Tracker_FileInfo(
                    $row['id'], 
                    $field,
                    $row['submitted_by'],
                    $row['description'],
                    $row['filename'],
                    $row['filesize'],
                    $row['filetype']
                );
            }
        }
        return self::$instances_by_id[$id];
    }
}
?>
