<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han
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
 * Git Filesystem object class
 *
 * @abstract
 */
abstract class FilesystemObject extends GitObject implements GitObjectType
{

    /**
     * path
     *
     * Stores the object path
     *
     * @access protected
     */
    protected $path = '';

    /**
     * mode
     *
     * Stores the object mode
     *
     * @access protected
     */
    protected $mode;

    /**
     * commit
     *
     * Stores the commit this object belongs to
     *
     * @access protected
     */
    protected $commit;

    /**
     * pathTree
     *
     * Stores the trees of this object's base path
     *
     * @access protected
     */
    protected $pathTree = [];

    /**
     * pathTreeRead
     *
     * Stores whether the trees of the object's base path have been read
     *
     * @access protected
     */
    protected $pathTreeRead = false;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash object hash
     * @return mixed git filesystem object
     * @throws \Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        parent::__construct($project, $hash);
    }

    /**
     * GetName
     *
     * Gets the object name
     *
     * @access public
     * @return string name
     */
    public function GetName() // @codingStandardsIgnoreLine
    {
        if (! empty($this->path)) {
            return basename($this->path);
        }

        return '';
    }

    /**
     * GetPath
     *
     * Gets the full path
     *
     * @access public
     * @return string path
     */
    public function GetPath() // @codingStandardsIgnoreLine
    {
        if (! empty($this->path)) {
            return $this->path;
        }

        return '';
    }


    public function GetFullPath() // @codingStandardsIgnoreLine
    {
        if (! (isset($_GET['f']))) {
            return $this->path;
        }

        return $_GET['f'] . "/" . $this->path;
    }

    /**
     * SetPath
     *
     * Sets the object path
     *
     * @access public
     * @param string $path object path
     */
    public function SetPath($path) // @codingStandardsIgnoreLine
    {
        $this->path = $path;
    }

    /**
     * GetMode
     *
     * Gets the object mode
     *
     * @access public
     * @return string mode
     */
    public function GetMode() // @codingStandardsIgnoreLine
    {
        return $this->mode;
    }

    /**
     * GetModeString
     *
     * Gets the mode as a readable string
     *
     * @access public
     * @return string mode string
     */
    public function GetModeString() // @codingStandardsIgnoreLine
    {
        if (empty($this->mode)) {
            return '';
        }

        $mode = octdec($this->mode);

        /*
         * Git doesn't store full ugo modes,
         * it only knows if something is a directory,
         * symlink, or an executable or non-executable file
         */
        if (($mode & 0x4000) == 0x4000) {
            return 'drwxr-xr-x';
        } elseif (($mode & 0xA000) == 0xA000) {
            return 'lrwxrwxrwx';
        } elseif (($mode & 0x8000) == 0x8000) {
            if (($mode & 0x0040) == 0x0040) {
                return '-rwxr-xr-x';
            } else {
                return '-rw-r--r--';
            }
        }
        return '----------';
    }

    /**
     * SetMode
     *
     * Sets the object mode
     *
     * @access public
     * @param string $mode tree mode
     */
    public function SetMode($mode) // @codingStandardsIgnoreLine
    {
        $this->mode = $mode;
    }

    /**
     * GetCommit
     *
     * Gets the commit this object belongs to
     *
     * @access public
     * @return mixed commit object
     */
    public function GetCommit() // @codingStandardsIgnoreLine
    {
        return $this->commit;
    }

    /**
     * SetCommit
     *
     * Sets the commit this object belongs to
     *
     * @access public
     * @param mixed $commit commit object
     */
    public function SetCommit($commit) // @codingStandardsIgnoreLine
    {
        $this->commit = $commit;
    }

    /**
     * GetPathTree
     *
     * Gets the objects of the base path
     *
     * @access public
     * @return array array of tree objects
     */
    public function GetPathTree() // @codingStandardsIgnoreLine
    {
        if (! $this->pathTreeRead) {
            $this->ReadPathTree();
        }

        return $this->pathTree;
    }

    /**
     * ReadPathTree
     *
     * Reads the objects of the base path
     *
     * @access private
     */
    private function ReadPathTree() // @codingStandardsIgnoreLine
    {
        $this->pathTreeRead = true;

        if (empty($this->path)) {
            /* this is a top level tree, it has no subpath */
            return;
        }

        $path = $this->path;

        while (($pos = strrpos($path, '/')) !== false) {
            $path = substr($path, 0, $pos);
            $pathhash = $this->commit->PathToHash($path);
            if (! empty($pathhash)) {
                $parent = $this->GetProject()->GetTree($pathhash);
                $parent->SetPath($path);
                $this->pathTree[] = $parent;
            }
        }

        if (count($this->pathTree) > 0) {
            $this->pathTree = array_reverse($this->pathTree);
        }
    }

    /**
     * ComparePath
     *
     * Compares two objects by path
     *
     * @access public
     * @static
     * @param mixed $a first object
     * @param mixed $b second object
     * @return int comparison result
     */
    public static function ComparePath($a, $b) // @codingStandardsIgnoreLine
    {
        return strcmp($a->GetPath(), $b->GetPath());
    }
}
