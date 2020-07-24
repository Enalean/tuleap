<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Tmp Dir
 *
 * Temporary directory class
 *
 */

/**
 * TmpDir class
 *
 * Class to handle managing files in a temporary directory
 *
 */
class TmpDir
{

    /**
     * instance
     *
     * Stores the singleton instance
     *
     * @access protected
     * @static
     */
    protected static $instance;

    /**
     * dir
     *
     * Stores the directory
     *
     * @access protected
     */
    protected $dir = null;

    /**
     * files
     *
     * Stores a list of files in this tmpdir
     *
     * @access protected
     */
    protected $files = [];

    /**
     * GetInstance
     *
     * Returns the singleton instance
     *
     * @access public
     * @static
     * @return mixed instance of tmpdir class
     */
    public static function GetInstance() // @codingStandardsIgnoreLine
    {
        if (! self::$instance) {
            self::$instance = new TmpDir();
        }
        return self::$instance;
    }

    /**
     * SystemTmpDir
     *
     * Gets the system defined temporary directory
     *
     * @access public
     * @static
     * @return string temp dir
     */
    public static function SystemTmpDir() // @codingStandardsIgnoreLine
    {
        $tmpdir = sys_get_temp_dir();

        return Util::AddSlash(realpath($tmpdir));
    }

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        $this->dir = Util::AddSlash(Config::GetInstance()->GetValue('gittmp'));

        if (empty($this->dir)) {
            $this->dir = TmpDir::SystemTmpDir();
        }

        if (empty($this->dir)) {
            throw new \Exception(dgettext("gitphp", 'No tmpdir defined'));
        }

        if (file_exists($this->dir)) {
            if (is_dir($this->dir)) {
                if (! is_writeable($this->dir)) {
                    throw new \Exception(sprintf(dgettext("gitphp", 'Specified tmpdir %1$s is not writable'), $this->dir));
                }
            } else {
                throw new \Exception(sprintf(dgettext("gitphp", 'Specified tmpdir %1$s is not a directory'), $this->dir));
            }
        } elseif (! mkdir($this->dir, 0700)) {
            throw new \Exception(sprintf(dgettext("gitphp", 'Could not create tmpdir %1$s'), $this->dir));
        }
    }

    /**
     * __destruct
     *
     * Destructor
     *
     * @access public
     */
    public function __destruct()
    {
        $this->Cleanup();
    }

    /**
     * GetDir
     *
     * Gets the temp dir
     *
     * @return string temp dir
     */
    public function GetDir() // @codingStandardsIgnoreLine
    {
        return $this->dir;
    }

    /**
     * SetDir
     *
     * Sets the temp dir
     *
     * @param string $dir new temp dir
     */
    public function SetDir($dir) // @codingStandardsIgnoreLine
    {
        $this->Cleanup();
        $this->dir = $dir;
    }

    /**
     * AddFile
     *
     * Adds a file to the temp dir
     *
     * @param string $filename file name
     * @param string $content file content
     */
    public function AddFile($filename, $content) // @codingStandardsIgnoreLine
    {
        if (empty($filename)) {
            return;
        }

        file_put_contents($this->dir . $filename, $content);

        if (! in_array($filename, $this->files)) {
            $this->files[] = $filename;
        }
    }

    /**
     * RemoveFile
     *
     * Removes a file from the temp dir
     *
     * @param string $filename file name
     */
    public function RemoveFile($filename) // @codingStandardsIgnoreLine
    {
        if (empty($filename)) {
            return;
        }

        unlink($this->dir . $filename);

        $idx = array_search($filename, $this->files);
        if ($idx !== false) {
            unset($this->files[$idx]);
        }
    }

    /**
     * Cleanup
     *
     * Cleans up any temporary files
     */
    public function Cleanup() // @codingStandardsIgnoreLine
    {
        if (! empty($this->dir) && (count($this->files) > 0)) {
            foreach ($this->files as $file) {
                $this->RemoveFile($file);
            }
        }
    }
}
