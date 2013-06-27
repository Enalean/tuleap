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


class Tracker_FileInfo {
    const THUMBNAILS_MAX_WIDTH  = 150;
    const THUMBNAILS_MAX_HEIGHT = 112;

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
     * Soap version of the object
     *
     * @return Array
     */
    public function getSoapValue() {
        return array(
            'id'           => $this->id,
            'submitted_by' => $this->submitted_by,
            'description'  => $this->description,
            'filename'     => $this->filename,
            'filesize'     => $this->filesize,
            'filetype'     => $this->filetype,
            'action'       => '',
        );
    }

    /**
     * Returns SOAP encoded content chunk of file
     *
     * @param Integer $offset Where to start reading
     * @param Integer $size   How much to read
     *
     * @return String Base64 encoded content
     */
    public function getSoapContent($offset, $size) {
        if (file_exists($this->getPath())) {
            return base64_encode(file_get_contents($this->getPath(), false, NULL, $offset, $size));
        }
        return null;
    }

    /**
     * @return Tracker_FormElement_Field_File
     */
    public function getField() {
        return $this->field;
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
        return $this->field->getRootPath();
    }

    public function __toString() {
        return '#'. $this->getId() .' '. $this->getFilename();
    }

    public function fileExists() {
        return file_exists($this->getPath());
    }

    public function postUploadActions() {
        if ($this->isImage()) {
            $this->createThumbnail();
        }
    }

    /**
     * Create a thumbnail of the image
     *
     * All modifications to this script should be done in the migration script 125
     *
     * @return void
     */
    private function createThumbnail() {
        $size = getimagesize($this->getPath());
        $thumbnail_width  = $size[0];
        $thumbnail_height = $size[1];
        if ($thumbnail_width > self::THUMBNAILS_MAX_WIDTH || $thumbnail_height > self::THUMBNAILS_MAX_HEIGHT) {
            if ($thumbnail_width / self::THUMBNAILS_MAX_WIDTH < $thumbnail_height / self::THUMBNAILS_MAX_HEIGHT) {
                //keep the height
                $thumbnail_width  = $thumbnail_width * self::THUMBNAILS_MAX_HEIGHT / $thumbnail_height;
                $thumbnail_height = self::THUMBNAILS_MAX_HEIGHT;
            } else {
                //keep the width
                $thumbnail_height = $thumbnail_height * self::THUMBNAILS_MAX_WIDTH / $thumbnail_width;
                $thumbnail_width  = self::THUMBNAILS_MAX_WIDTH;
            }
        }
        switch ($size[2]) {
        case IMAGETYPE_GIF:
            $source      = imagecreatefromgif($this->getPath());
            $destination = imagecreate((int)$thumbnail_width, (int)$thumbnail_height);
            imagepalettecopy($destination, $source);
            $store       = 'imagegif';
            break;
        case IMAGETYPE_JPEG:
            $source      = imagecreatefromjpeg($this->getPath());
            $destination = imagecreatetruecolor((int)$thumbnail_width, (int)$thumbnail_height);
            $store       = 'imagejpeg';
            break;
        case IMAGETYPE_PNG:
            $source      = imagecreatefrompng($this->getPath());
            $destination = imagecreatetruecolor((int)$thumbnail_width, (int)$thumbnail_height);
            $store       = 'imagepng';
            break;
        default:
            // Not an image, exit;
            return false;
        }
        imagecopyresized($destination, $source, 0, 0, 0, 0, (int)$thumbnail_width, (int)$thumbnail_height, $size[0], $size[1]);
        $store($destination, $this->getThumbnailPath());
        imagedestroy($source);
        imagedestroy($destination);
    }

    /**
     * Persist current object to the database
     *
     * @return Boolean
     */
    public function save() {
        $dao = new Tracker_FileInfoDao();
        $this->id = $dao->create($this->submitted_by, $this->description, $this->filename, $this->filesize, $this->filetype);
        if ($this->id) {
            return true;
        }
        return false;
    }

    /**
     * delete a file info
     *
     * @return boolean true on success
     */
    public function delete() {
        if (file_exists($this->getPath())) {
            unlink($this->getPath());
        }
        if (file_exists($this->getThumbnailPath())) {
            unlink($this->getThumbnailPath());
        }
        $dao = new Tracker_FileInfoDao();
        return $dao->delete($this->getId());
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
