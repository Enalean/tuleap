<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class FRSException extends Exception {
};

class FRSFileException extends FRSException {
};

class FRSFileMD5SumException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'md5_fail', array(basename($file->getFileName()), $file->getComputedMd5())), $code);
    }
}

class FRSFileInvalidNameException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'filename_invalid').': '.$file->getFileName(), $code);
    }
}

class FRSFileExistsException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'filename_exists').': '.$file->getFileName(), $code);
    }
}

class FRSFileToBeRestoredException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'filename_to_be_restored').': '.$file->getFileName(), $code);
    }
}

class FRSFileForgeException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'fileforge_error', array($file->getFileName())), $code);
    }
}

class FRSFileDbException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'db_error', array($file->getFileName())), $code);
    }
}

class FRSFileIllegalNameException extends FRSFileException {
    /**
     * Constructor
     *
     * @param FRSFile $file The file that triggered the exception
     * @param Integer $code Numeric code
     */
    public function __construct($file, $code = 0) {
        parent::__construct($GLOBALS['Language']->getText('file_admin_editreleases', 'illegal_file_name').': '.$file->getFileName(), $code);
    }
}

?>