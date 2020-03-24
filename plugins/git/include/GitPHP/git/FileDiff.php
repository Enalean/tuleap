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

use Tuleap\Git\BinaryDetector;

/**
 * Commit class
 *
 */
class FileDiff
{
    /**
     * diffInfoRead
     *
     * Stores whether diff info has been read
     *
     * @access protected
     */
    protected $diffInfoRead = false;

    /**
     * diffDataRead
     *
     * Stores whether diff data has been read
     *
     * @access protected
     */
    protected $diffDataRead = false;

    /**
     * diffData
     *
     * Stores the diff data
     *
     * @access protected
     */
    protected $diffData;

    /**
     * diffDataSplitRead
     *
     * Stores whether split diff data has been read
     *
     * @access protected
     */
    protected $diffDataSplitRead = false;

    /**
     * diffDataSplit
     *
     * Stores the diff data split up by left/right changes
     *
     * @access protected
     */
    protected $diffDataSplit;

    /**
     * diffDataName
     *
     * Filename used on last data diff
     *
     * @access protected
     */
    protected $diffDataName;

    /**
     * fromMode
     *
     * Stores the from file mode
     *
     * @access protected
     */
    protected $fromMode;

    /**
     * toMode
     *
     * Stores the to file mode
     *
     * @access protected
     */
    protected $toMode;

    /**
     * fromHash
     *
     * Stores the from hash
     *
     * @access protected
     */
    protected $fromHash;

    /**
     * toHash
     *
     * Stores the to hash
     *
     * @access protected
     */
    protected $toHash;

    /**
     * status
     *
     * Stores the status
     *
     * @access protected
     */
    protected $status;

    /**
     * similarity
     *
     * Stores the similarity
     *
     * @access protected
     */
    protected $similarity;

    /**
     * fromFile
     *
     * Stores the from filename
     *
     * @access protected
     */
    protected $fromFile;

    /**
     * toFile
     *
     * Stores the to filename
     *
     * @access protected
     */
    protected $toFile;

    /**
     * fromFileType
     *
     * Stores the from file type
     *
     * @access protected
     */
    protected $fromFileType;

    /**
     * toFileType
     *
     * Stores the to file type
     *
     * @access protected
     */
    protected $toFileType;

    /**
     * project
     *
     * Stores the project
     *
     * @access protected
     */
    protected $project;

    /**
     * commit
     *
     * Stores the commit that caused this filediff
     *
     * @access protected
     */
    protected $commit;

    /**
     * @var array
     */
    private $stats = [];

    /**
     * @param mixed  $project  project
     * @param string $fromHash source hash, can also be a diff-tree info line
     * @param string $toHash   target hash, required if $fromHash is a hash
     * @param array  $stats_indexed_by_filename
     *
     * @throws \Exception on invalid parameters
     */
    public function __construct($project, $fromHash, $toHash = '', array $stats_indexed_by_filename = [])
    {
        $this->project = $project;

        if ($this->ParseDiffTreeLine($fromHash)) {
            if (isset($stats_indexed_by_filename[$this->toFile])) {
                $this->stats = $stats_indexed_by_filename[$this->toFile];
            }
            return;
        }

        if (!(preg_match('/^[0-9a-fA-F]{40}$/', $fromHash) && preg_match('/^[0-9a-fA-F]{40}$/', $toHash))) {
            throw new \Exception('Invalid parameters for FileDiff');
        }

        $this->fromHash = $fromHash;
        $this->toHash = $toHash;
    }

    public function isBinaryFile()
    {
        $blob = $this->GetToBlob();
        if (! $blob) {
            return false;
        }
        return BinaryDetector::isBinary($blob->GetData());
    }

    public function hasStats()
    {
        return ! empty($this->stats);
    }

    public function getAddedStats()
    {
        return $this->stats['added'];
    }

    public function getRemovedStats()
    {
        return $this->stats['removed'];
    }

    /**
     * ParseDiffTreeLine
     *
     * @access private
     * @param string $diffTreeLine line from difftree
     * @return bool true if data was read from line
     */
    private function ParseDiffTreeLine($diffTreeLine) // @codingStandardsIgnoreLine
    {
        if (preg_match('/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$/', $diffTreeLine, $regs)) {
            $this->diffInfoRead = true;

            $this->fromMode = $regs[1];
            $this->toMode = $regs[2];
            $this->fromHash = $regs[3];
            $this->toHash = $regs[4];
            $this->status = $regs[5];
            $this->similarity = ltrim($regs[6], '0');
            $this->fromFile = strtok($regs[7], "\t");
            $this->toFile = strtok("\t");
            if ($this->toFile === false) {
                /* no filename change */
                $this->toFile = $this->fromFile;
            }

            return true;
        }

        return false;
    }

    /**
     * ReadDiffInfo
     *
     * Reads file diff info
     *
     * @access protected
     */
    protected function ReadDiffInfo() // @codingStandardsIgnoreLine
    {
        $this->diffInfoRead = true;

        /* TODO: read a single difftree line on-demand */
    }

    /**
     * GetFromMode
     *
     * Gets the from file mode
     * (full a/u/g/o)
     *
     * @access public
     * @return string from file mode
     */
    public function GetFromMode() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return $this->fromMode;
    }

    /**
     * GetFromModeShort
     *
     * Gets the from file mode in short form
     * (standard u/g/o)
     *
     * @access public
     * @return string short from file mode
     */
    public function GetFromModeShort() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return substr($this->fromMode, -4);
    }

    /**
     * GetToMode
     *
     * Gets the to file mode
     * (full a/u/g/o)
     *
     * @access public
     * @return string to file mode
     */
    public function GetToMode() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return $this->toMode;
    }

    /**
     * GetToModeShort
     *
     * Gets the to file mode in short form
     * (standard u/g/o)
     *
     * @access public
     * @return string short to file mode
     */
    public function GetToModeShort() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return substr($this->toMode, -4);
    }

    /**
     * GetFromHash
     *
     * Gets the from hash
     *
     * @access public
     * @return string from hash
     */
    public function GetFromHash() // @codingStandardsIgnoreLine
    {
        return $this->fromHash;
    }

    /**
     * GetToHash
     *
     * Gets the to hash
     *
     * @access public
     * @return string to hash
     */
    public function GetToHash() // @codingStandardsIgnoreLine
    {
        return $this->toHash;
    }

    /**
     * GetFromBlob
     *
     * Gets the from file blob
     *
     * @access public
     * @return mixed blob object
     */
    public function GetFromBlob() // @codingStandardsIgnoreLine
    {
        if (empty($this->fromHash)) {
            return null;
        }

        return $this->project->GetBlob($this->fromHash);
    }

    /**
     * GetToBlob
     *
     * Gets the to file blob
     *
     * @access public
     * @return null|Blob
     */
    public function GetToBlob() // @codingStandardsIgnoreLine
    {
        if (empty($this->toHash)) {
            return null;
        }

        return $this->project->GetBlob($this->toHash);
    }

    /**
     * GetStatus
     *
     * Gets the status of the change
     *
     * @access public
     * @return string status
     */
    public function GetStatus() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return $this->status;
    }

    /**
     * GetSimilarity
     *
     * Gets the similarity
     *
     * @access public
     * @return string similarity
     */
    public function GetSimilarity() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return $this->similarity;
    }

    /**
     * GetFromFile
     *
     * Gets the from file name
     *
     * @access public
     * @return string from file
     */
    public function GetFromFile() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return $this->fromFile;
    }

    /**
     * GetToFile
     *
     * Gets the to file name
     *
     * @access public
     * @return string to file
     */
    public function GetToFile() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return $this->toFile;
    }

    /**
     * GetFromFileType
     *
     * Gets the from file type
     *
     * @access public
     * @param bool $local true if caller wants localized type
     * @return string from file type
     */
    public function GetFromFileType($local = false) // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return Blob::FileType($this->fromMode, $local);
    }

    /**
     * GetToFileType
     *
     * Gets the to file type
     *
     * @access public
     * @param bool $local true if caller wants localized type
     * @return string to file type
     */
    public function GetToFileType($local = false) // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return Blob::FileType($this->toMode, $local);
    }

    /**
     * FileTypeChanged
     *
     * Tests if filetype changed
     *
     * @access public
     * @return bool true if file type changed
     */
    public function FileTypeChanged() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return (octdec($this->fromMode) & 0x17000) != (octdec($this->toMode) & 0x17000);
    }

    /**
     * FileModeChanged
     *
     * Tests if file mode changed
     *
     * @access public
     * @return bool true if file mode changed
     */
    public function FileModeChanged() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return (octdec($this->fromMode) & 0777) != (octdec($this->toMode) & 0777);
    }

    /**
     * FromFileIsRegular
     *
     * Tests if the from file is a regular file
     *
     * @access public
     * @return bool true if from file is regular
     */
    public function FromFileIsRegular() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return (octdec($this->fromMode) & 0x8000) == 0x8000;
    }

    /**
     * ToFileIsRegular
     *
     * Tests if the to file is a regular file
     *
     * @access public
     * @return bool true if to file is regular
     */
    public function ToFileIsRegular() // @codingStandardsIgnoreLine
    {
        if (!$this->diffInfoRead) {
            $this->ReadDiffInfo();
        }

        return (octdec($this->toMode) & 0x8000) == 0x8000;
    }

    /**
     * GetDiff
     *
     * Gets the diff output
     *
     * @access public
     * @param string $file override the filename on the diff
     * @return string diff output
     */
    public function GetDiff($file = '', $readFileData = true, $explode = false) // @codingStandardsIgnoreLine
    {
        if ($this->diffDataRead && ($file == $this->diffDataName)) {
            if ($explode) {
                return explode("\n", $this->diffData);
            } else {
                return $this->diffData;
            }
        }

        if ((!$this->diffInfoRead) && $readFileData) {
            $this->ReadDiffInfo();
        }

        $this->diffDataName = $file;
        $this->diffDataRead = true;

        if ((!empty($this->status)) && ($this->status != 'A') && ($this->status != 'D') && ($this->status != 'M')) {
            $this->diffData = '';
            return;
        }

        if (function_exists('xdiff_string_diff')) {
            $this->diffData = $this->GetXDiff(3, true, $file);
        } else {
            $tmpdir = TmpDir::GetInstance();

            $pid = 0;
            if (function_exists('posix_getpid')) {
                $pid = posix_getpid();
            } else {
                $pid = rand();
            }

            $fromTmpFile = null;
            $toTmpFile = null;

            $fromName = null;
            $toName = null;

            if ((empty($this->status)) || ($this->status == 'D') || ($this->status == 'M')) {
                $fromBlob = $this->GetFromBlob();
                $fromTmpFile = 'gitphp_' . $pid . '_from';
                $tmpdir->AddFile($fromTmpFile, $fromBlob->GetData());

                $fromName = 'a/';
                if (!empty($file)) {
                    $fromName .= $file;
                } elseif (!empty($this->fromFile)) {
                    $fromName .= $this->fromFile;
                } else {
                    $fromName .= $this->fromHash;
                }
            }

            if ((empty($this->status)) || ($this->status == 'A') || ($this->status == 'M')) {
                $toBlob = $this->GetToBlob();
                $toTmpFile = 'gitphp_' . $pid . '_to';
                $tmpdir->AddFile($toTmpFile, $toBlob->GetData());

                $toName = 'b/';
                if (!empty($file)) {
                    $toName .= $file;
                } elseif (!empty($this->toFile)) {
                    $toName .= $this->toFile;
                } else {
                    $toName .= $this->toHash;
                }
            }

            $this->diffData = DiffExe::Diff((empty($fromTmpFile) ? null : escapeshellarg($tmpdir->GetDir() . $fromTmpFile)), $fromName, (empty($toTmpFile) ? null : escapeshellarg($tmpdir->GetDir() . $toTmpFile)), $toName);

            if (!empty($fromTmpFile)) {
                $tmpdir->RemoveFile($fromTmpFile);
            }

            if (!empty($toTmpFile)) {
                $tmpdir->RemoveFile($toTmpFile);
            }
        }

        if ($explode) {
            return explode("\n", $this->diffData);
        } else {
            return $this->diffData;
        }
    }

    /**
     * GetDiffSplit
     *
     * construct the side by side diff data from the git data
     * The result is an array of ternary arrays with 3 elements each:
     * First the mode ("" or "-added" or "-deleted" or "-modified"),
     * then the first column, then the second.
     *
     * @access public
     * @return an array of line elements (see above)
     */
    public function GetDiffSplit() // @codingStandardsIgnoreLine
    {
        if ($this->diffDataSplitRead) {
            return $this->diffDataSplit;
        }

        $this->diffDataSplitRead = true;

        $exe = new GitExe($this->project);

        $fromBlob = $this->GetFromBlob();
        $blob = $fromBlob->GetData(true);

        $diffLines = '';
        if (function_exists('xdiff_string_diff')) {
            $diffLines = explode("\n", $this->GetXDiff(0, false));
        } else {
            $diffLines = explode("\n", $exe->Execute(
                GitExe::DIFF,
                array("-U0", escapeshellarg($this->fromHash),
                    escapeshellarg($this->toHash))
            ));
        }

        unset($exe);

        // parse diffs
        $diffs = array();
        $currentDiff = false;
        foreach ($diffLines as $d) {
            if (strlen($d) == 0) {
                continue;
            }
            switch ($d[0]) {
                case '@':
                    if ($currentDiff) {
                        assert(isset($currentDiff['left'], $currentDiff['right'], $currentDiff['line']));
                        if (count($currentDiff['left']) == 0 && count($currentDiff['right']) > 0) {
                            $currentDiff['line']++;     // HACK to make added blocks align correctly
                        }
                        $diffs[] = $currentDiff;
                    }
                    $comma = strpos($d, ",");
                    $line = -intval(substr($d, 2, $comma - 2));
                    $currentDiff = array("line" => $line,
                        "left" => array(), "right" => array());
                    break;
                case '+':
                    if ($currentDiff) {
                        $currentDiff["right"][] = substr($d, 1);
                    }
                    break;
                case '-':
                    if ($currentDiff) {
                        $currentDiff["left"][] = substr($d, 1);
                    }
                    break;
                case ' ':
                    echo "should not happen!";
                    if ($currentDiff) {
                        $currentDiff["left"][] = substr($d, 1);
                        $currentDiff["right"][] = substr($d, 1);
                    }
                    break;
            }
        }
        if ($currentDiff) {
            assert(isset($currentDiff['left'], $currentDiff['right'], $currentDiff['line']));
            if (count($currentDiff['left']) == 0 && count($currentDiff['right']) > 0) {
                $currentDiff['line']++;     // HACK to make added blocks align correctly
            }
            $diffs[] = $currentDiff;
        }

        // iterate over diffs
        $output = array();
        $idx = 0;
        foreach ($diffs as $d) {
            while ($idx + 1 < $d['line']) {
                $h = $blob[$idx];
                $output[] = array('', $h, $h);
                $idx ++;
            }

            if (count($d['left']) == 0) {
                $mode = 'added';
            } elseif (count($d['right']) == 0) {
                $mode = 'deleted';
            } else {
                $mode = 'modified';
            }

            for ($i = 0; $i < count($d['left']) || $i < count($d['right']); $i++) {
                $left = $i < count($d['left']) ? $d['left'][$i] : false;
                $right = $i < count($d['right']) ? $d['right'][$i] : false;
                $output[] = array($mode, $left, $right);
            }

            $idx += count($d['left']);
        }

        while ($idx < count($blob)) {
            $h = $blob[$idx];
            $output[] = array('', $h, $h);
            $idx ++;
        }

        $this->diffDataSplit = $output;
        return $output;
    }

    /**
     * GetXDiff
     *
     * Get diff using xdiff
     *
     * @access private
     * @param int $context number of context lines
     * @param bool $header true to include standard diff header
     * @param string $file override the file name
     * @return string diff content
     */
    private function GetXDiff($context = 3, $header = true, $file = null) // @codingStandardsIgnoreLine
    {
        if (!function_exists('xdiff_string_diff')) {
            return '';
        }

        $fromData = '';
        $toData = '';
        $isBinary = false;
        $fromName = '/dev/null';
        $toName = '/dev/null';
        if (empty($this->status) || ($this->status == 'M') || ($this->status == 'D')) {
            $fromBlob = $this->GetFromBlob();
            $fromData = $fromBlob->GetData(false);
            $isBinary = BinaryDetector::isBinary($fromData);
            $fromName = 'a/';
            if (!empty($file)) {
                $fromName .= $file;
            } elseif (!empty($this->fromFile)) {
                $fromName .= $this->fromFile;
            } else {
                $fromName .= $this->fromHash;
            }
        }
        if (empty($this->status) || ($this->status == 'M') || ($this->status == 'A')) {
            $toBlob = $this->GetToBlob();
            $toData = $toBlob->GetData(false);
            $isBinary = $isBinary || BinaryDetector::isBinary($toData);
            $toName = 'b/';
            if (!empty($file)) {
                $toName .= $file;
            } elseif (!empty($this->toFile)) {
                $toName .= $this->toFile;
            } else {
                $toName .= $this->toHash;
            }
        }
        $output = '';
        if ($isBinary) {
            $output = sprintf(dgettext("gitphp", 'Binary files %1$s and %2$s differ'), $fromName, $toName) . "\n";
        } else {
            if ($header) {
                $output = '--- ' . $fromName . "\n" . '+++ ' . $toName . "\n";
            }
            $output .= xdiff_string_diff($fromData, $toData, $context);
        }
        return $output;
    }

    /**
     * GetCommit
     *
     * Gets the commit for this filediff
     *
     * @access public
     * @return Commit object
     */
    public function GetCommit() // @codingStandardsIgnoreLine
    {
        return $this->commit;
    }

    /**
     * SetCommit
     *
     * Sets the commit for this filediff
     *
     * @access public
     * @param mixed $commit commit object
     */
    public function SetCommit($commit) // @codingStandardsIgnoreLine
    {
        $this->commit = $commit;
    }
}
