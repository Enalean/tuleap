<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * Tree class
 *
 */
class Tree extends FilesystemObject
{

    /**
     * contents
     *
     * Tree contents
     *
     * @access protected
     */
    protected $contents = array();

    /**
     * contentsRead
     *
     * Stores whether contents were read
     *
     * @access protected
     */
    protected $contentsRead = false;

    /**
     * contentsReferenced
     *
     * Stores whether contents have been referenced into pointers
     *
     * @access private
     */
    private $contentsReferenced = false;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash tree hash
     * @return mixed tree object
     * @throws \Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        parent::__construct($project, $hash);
    }

    /**
     * SetCommit
     *
     * Sets the commit for this tree (overrides base)
     *
     * @access public
     * @param mixed $commit commit object
     */
    public function SetCommit($commit) // @codingStandardsIgnoreLine
    {
        parent::SetCommit($commit);

        if ($this->contentsRead && !$this->contentsReferenced) {
            foreach ($this->contents as $obj) {
                if (! $obj->isSubmodule()) {
                    $obj->SetCommit($commit);
                }
            }
        }
    }

    /**
     * GetContents
     *
     * Gets the tree contents
     *
     * @access public
     * @return array array of objects for contents
     */
    public function GetContents() // @codingStandardsIgnoreLine
    {
        if (!$this->contentsRead) {
            $this->ReadContents();
        }

        if ($this->contentsReferenced) {
            $this->DereferenceContents();
        }

        return $this->contents;
    }

    /**
     * ReadContents
     *
     * Reads the tree contents
     *
     * @access protected
     */
    protected function ReadContents() // @codingStandardsIgnoreLine
    {
        $this->contentsRead = true;
        $this->ReadContentsRaw();
    }

    /**
     * ReadContentsRaw
     *
     * Reads the tree contents using the raw git object
     *
     * @access private
     */
    private function ReadContentsRaw() // @codingStandardsIgnoreLine
    {
        $treeData = $this->GetProject()->GetObject($this->hash);

        $start = 0;
        $len = strlen($treeData);
        while ($start < $len) {
            $pos = strpos($treeData, "\0", $start);

            list($mode, $path) = explode(' ', substr($treeData, $start, $pos - $start), 2);
            $mode = str_pad($mode, 6, '0', STR_PAD_LEFT);
            $hash = bin2hex(substr($treeData, $pos + 1, 20));
            $start = $pos + 21;

            $octmode = octdec($mode);

            if (!empty($this->path)) {
                $path = $this->path . '/' . $path;
            }

            if ($octmode === 57344) {
                $this->contents[] = new Submodule($path, $hash);
                continue;
            }

            $obj = null;
            if ($octmode & 0x4000) {
                // tree
                $obj = $this->GetProject()->GetTree($hash);
            } else {
                // blob
                $obj = $this->GetProject()->GetBlob($hash);
            }

            if (!$obj) {
                continue;
            }

            $obj->SetMode($mode);
            $obj->SetPath($path);
            if ($this->commit) {
                $obj->SetCommit($this->commit);
            }
            $this->contents[] = $obj;
        }
    }

    /**
     * ReferenceContents
     *
     * Turns the contents objects into reference pointers
     *
     * @access private
     */
    private function ReferenceContents() // @codingStandardsIgnoreLine
    {
        if ($this->contentsReferenced) {
            return;
        }

        if (!(isset($this->contents) && (count($this->contents) > 0))) {
            return;
        }

        for ($i = 0; $i < count($this->contents); ++$i) {
            $obj = $this->contents[$i];
            $data = array();

            $data['hash'] = $obj->GetHash();
            $data['mode'] = $obj->GetMode();
            $data['path'] = $obj->GetPath();

            if ($obj instanceof Tree) {
                $data['type'] = 'tree';
            } elseif ($obj instanceof Blob) {
                $data['type'] = 'blob';
                $data['size'] = $obj->GetSize();
            }

            $this->contents[$i] = $data;
        }

        $this->contentsReferenced = true;
    }

    /**
     * DereferenceContents
     *
     * Turns the contents pointers back into objects
     *
     * @access private
     */
    private function DereferenceContents() // @codingStandardsIgnoreLine
    {
        if (!$this->contentsReferenced) {
            return;
        }

        if (!(isset($this->contents) && (count($this->contents) > 0))) {
            return;
        }

        for ($i = 0; $i < count($this->contents); ++$i) {
            $data = $this->contents[$i];
            $obj = null;

            if (!isset($data['hash']) || empty($data['hash'])) {
                continue;
            }

            if ($data['type'] == 'tree') {
                $obj = $this->GetProject()->GetTree($data['hash']);
            } elseif ($data['type'] == 'blob') {
                $obj = $this->GetProject()->GetBlob($data['hash']);
                if (isset($data['size']) && !empty($data['size'])) {
                    $obj->SetSize($data['size']);
                }
            } else {
                continue;
            }

            if (isset($data['mode']) && !empty($data['mode'])) {
                $obj->SetMode($data['mode']);
            }

            if (isset($data['path']) && !empty($data['path'])) {
                $obj->SetPath($data['path']);
            }

            if ($this->commit) {
                $obj->SetCommit($this->commit);
            }

            $this->contents[$i] = $obj;
        }

        $this->contentsReferenced = false;
    }

    public function isTree()
    {
        return true;
    }

    public function isBlob()
    {
        return false;
    }

    public function isSubmodule()
    {
        return false;
    }
}
