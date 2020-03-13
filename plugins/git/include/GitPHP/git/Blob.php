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
 * Commit class
 *
 */
class Blob extends FilesystemObject
{

    /**
     * data
     *
     * Stores the file data
     *
     * @access protected
     */
    protected $data;

    /**
     * dataRead
     *
     * Stores whether data has been read
     *
     * @access protected
     */
    protected $dataRead = false;

    /**
     * size
     *
     * Stores the size
     *
     * @access protected
     */
    protected $size = null;

    /**
     * history
     *
     * Stores the history
     *
     * @access protected
     */
    protected $history = array();

    /**
     * blame
     *
     * Stores blame info
     *
     * @access protected
     */
    protected $blame = array();

    /**
     * blameRead
     *
     * Stores whether blame was read
     *
     * @access protected
     */
    protected $blameRead = false;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash object hash
     * @return mixed blob object
     * @throws \Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        parent::__construct($project, $hash);
    }

    /**
     * GetData
     *
     * Gets the blob data
     *
     * @access public
     * @param bool $explode true to explode data into an array of lines
     * @return string blob data
     */
    public function GetData($explode = false) // @codingStandardsIgnoreLine
    {
        if (!$this->dataRead) {
            $this->ReadData();
        }

        if ($explode) {
            return explode("\n", $this->data);
        } else {
            return $this->data;
        }
    }

    /**
     * ReadData
     *
     * Reads the blob data
     *
     * @access private
     */
    private function ReadData() // @codingStandardsIgnoreLine
    {
        $this->dataRead = true;
        $this->data = $this->GetProject()->GetObject($this->hash);
    }

    /**
     * FileType
     *
     * Gets a file type from its octal mode
     *
     * @access public
     * @static
     * @param string $octMode octal mode
     * @param bool $local true if caller wants localized type
     * @return string file type
     */
    public static function FileType($octMode, $local = false) // @codingStandardsIgnoreLine
    {
        $mode = octdec($octMode);
        if ($mode === 57344) {
            if ($local) {
                return dgettext('tuleap-git', 'submodule');
            }
            return 'submodule';
        }
        if (($mode & 0x4000) == 0x4000) {
            if ($local) {
                return dgettext("gitphp", 'directory');
            } else {
                return 'directory';
            }
        } elseif (($mode & 0xA000) == 0xA000) {
            if ($local) {
                return dgettext("gitphp", 'symlink');
            } else {
                return 'symlink';
            }
        } elseif (($mode & 0x8000) == 0x8000) {
            if ($local) {
                return dgettext("gitphp", 'file');
            } else {
                return 'file';
            }
        }

        if ($local) {
            return dgettext("gitphp", 'unknown');
        } else {
            return 'unknown';
        }
    }

    /**
     * GetSize
     *
     * Gets the blob size
     *
     * @access public
     * @return int size
     */
    public function GetSize() // @codingStandardsIgnoreLine
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!$this->dataRead) {
            $this->ReadData();
        }

        return strlen($this->data);
    }

    /**
     * SetSize
     *
     * Sets the blob size
     *
     * @access public
     * @param int $size size
     */
    public function SetSize($size) // @codingStandardsIgnoreLine
    {
        $this->size = $size;
    }

    /**
     * FileMime
     *
     * Get the file mimetype
     *
     * @access public
     * @param bool $short true to only the type group
     * @return string mime
     */
    public function FileMime($short = false) // @codingStandardsIgnoreLine
    {
        $mime = $this->FileMime_Fileinfo();

        if (empty($mime)) {
            $mime = $this->FileMime_Extension();
        }

        if ((!empty($mime)) && $short) {
            $mime = strtok($mime, '/');
        }

        return $mime;
    }

    /**
     * FileMime_Fileinfo
     *
     * Get the file mimetype using fileinfo
     *
     * @access private
     * @return string mimetype
     */
    private function FileMime_Fileinfo() // @codingStandardsIgnoreLine
    {
        if (!function_exists('finfo_buffer')) {
            return '';
        }

        if (!$this->dataRead) {
            $this->ReadData();
        }

        if (!$this->data) {
            return '';
        }

        $mime = '';

        $finfo = @finfo_open(FILEINFO_MIME);
        if ($finfo) {
            $mime = finfo_buffer($finfo, $this->data, FILEINFO_MIME);
            if ($mime && strpos($mime, '/')) {
                if (strpos($mime, ';')) {
                    $mime = strtok($mime, ';');
                }
            }
            finfo_close($finfo);
        }

        return $mime;
    }

    /**
     * FileMime_Extension
     *
     * Get the file mimetype using the file extension
     *
     * @access private
     * @return string mimetype
     */
    private function FileMime_Extension() // @codingStandardsIgnoreLine
    {
        $file = $this->GetName();

        if (empty($file)) {
            return '';
        }

        $dotpos = strrpos($file, '.');
        if ($dotpos !== false) {
            $file = substr($file, $dotpos + 1);
        }
        switch ($file) {
            case 'jpg':
            case 'jpeg':
            case 'jpe':
                return 'image/jpeg';
                break;
            case 'gif':
                return 'image/gif';
                break;
            case 'png':
                return 'image/png';
                break;
        }

        return '';
    }

    /**
     * GetHistory
     *
     * Gets the history of this file
     *
     * @param int $count number of entries to get
     * @param int $skip  number of entries to skip
     *
     * @return array array of filediff changes
     */
    public function GetPaginatedHistory($count = PHP_INT_MAX, $skip = 0) // @codingStandardsIgnoreLine
    {
        $this->history = [];

        $exe = new GitExe($this->GetProject());

        $args = [];
        $args[] = '--max-count=' . escapeshellarg($count);
        $args[] = '--skip=' . escapeshellarg($skip);
        if (isset($this->commit)) {
            $args[] = $this->commit->GetHash();
        } else {
            $args[] = 'HEAD';
        }
        $args[] = '--';
        $args[] = escapeshellarg($this->GetPath());

        $revlist = $exe->Execute(GitExe::REV_LIST, $args);
        $hasmore = substr_count($revlist, "\n") >= $count;

        $args[] = '|';
        $args[] = $exe->GetBinary();
        $args[] = '--git-dir=' . escapeshellarg($this->GetProject()->GetPath());
        $args[] = GitExe::DIFF_TREE;
        $args[] = '-r';
        $args[] = '--root';
        $args[] = '--stdin';
        $args[] = '--';
        $args[] = escapeshellarg($this->GetPath());

        $historylines = explode("\n", $exe->Execute(GitExe::REV_LIST, $args));

        $commit = null;
        foreach ($historylines as $line) {
            if (preg_match('/^([0-9a-fA-F]{40})/', $line, $regs)) {
                $commit = $this->GetProject()->GetCommit($regs[1]);
            } elseif (isset($commit)) {
                try {
                    $history = new FileDiff($this->GetProject(), $line);
                    $history->SetCommit($commit);
                    $this->history[] = $history;
                } catch (\Exception $e) {
                }
                unset($commit);
            }
        }

        return [$this->history, $hasmore];
    }

    public function GetHistory() // @codingStandardsIgnoreLine
    {
        list($history,) = $this->GetPaginatedHistory(PHP_INT_MAX, 0);

        return $history; //for legacy gitphp view
    }

    /**
     * GetBlame
     *
     * Gets blame info
     *
     * @access public
     * @return array blame array (line to commit mapping)
     */
    public function GetBlame() // @codingStandardsIgnoreLine
    {
        if (!$this->blameRead) {
            $this->ReadBlame();
        }

        return $this->blame;
    }

    /**
     * ReadBlame
     *
     * Read blame info
     *
     * @access private
     */
    private function ReadBlame() // @codingStandardsIgnoreLine
    {
        $this->blameRead = true;

        $exe = new GitExe($this->GetProject());

        $args = array();
        $args[] = '-s';
        $args[] = '-l';
        $args[] = '--root';
        if ($this->commit) {
            $args[] = escapeshellarg($this->commit->GetHash());
        } else {
            $args[] = 'HEAD';
        }
        $args[] = '--';
        $args[] = escapeshellarg($this->GetPath());

        $blamelines = explode("\n", $exe->Execute(GitExe::BLAME, $args));

        $lastcommit = '';
        foreach ($blamelines as $line) {
            if (preg_match('/^([0-9a-fA-F]{40})(\s+.+)?\s+([0-9]+)\)/', $line, $regs)) {
                if ($regs[1] != $lastcommit) {
                    $this->blame[(int) ($regs[3])] = $this->GetProject()->GetCommit($regs[1]);
                    $lastcommit = $regs[1];
                }
            }
        }
    }

    public function isTree()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isBlob()
    {
        return  true;
    }

    public function isSubmodule()
    {
        return false;
    }
}
