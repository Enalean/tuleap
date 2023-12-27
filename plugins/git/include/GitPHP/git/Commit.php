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
 * Commit class
 *
 */
class Commit extends GitObject
{
    private const HEADER_SIGNATURE_SECTION = 'gpgsig';

    /**
     * dataRead
     *
     * Indicates whether data for this commit has been read
     *
     * @access protected
     */
    protected $dataRead = false;

    /**
     * parents
     *
     * Array of parent commits
     *
     * @access protected
     */
    protected $parents = [];

    /**
     * tree
     *
     * Tree object for this commit
     *
     * @access protected
     */
    protected $tree;

    /**
     * author
     *
     * Author for this commit
     *
     * @access protected
     */
    protected $author;

    /**
     * authorEpoch
     *
     * Author's epoch
     *
     * @access protected
     */
    protected $authorEpoch;

    /**
     * authorTimezone
     *
     * Author's timezone
     *
     * @access protected
     */
    protected $authorTimezone;

    /**
     * committer
     *
     * Committer for this commit
     *
     * @access protected
     */
    protected $committer;

    /**
     * committerEpoch
     *
     * Committer's epoch
     *
     * @access protected
     */
    protected $committerEpoch;

    /**
     * committerTimezone
     *
     * Committer's timezone
     *
     * @access protected
     */
    protected $committerTimezone;

    /**
     * title
     *
     * Stores the commit title
     *
     * @access protected
     */
    protected $title;

    /**
     * comment
     *
     * Stores the commit comment
     *
     * @access protected
     */
    protected $comment = [];

    /**
     * @var null|string
     */
    private $signature;

    /**
     * readTree
     *
     * Stores whether tree filenames have been read
     *
     * @access protected
     */
    protected $readTree = false;

    /**
     * blobPaths
     *
     * Stores blob hash to path mappings
     *
     * @access protected
     */
    protected $blobPaths = [];

    /**
     * treePaths
     *
     * Stores tree hash to path mappings
     *
     * @access protected
     */
    protected $treePaths = [];

    private $commit_paths = [];

    /**
     * hashPathsRead
     *
     * Stores whether hash paths have been read
     *
     * @access protected
     */
    protected $hashPathsRead = false;

    /**
     * containingTag
     *
     * Stores the tag containing the changes in this commit
     *
     * @access protected
     */
    protected $containingTag = null;

    /**
     * containingTagRead
     *
     * Stores whether the containing tag has been looked up
     *
     * @access public
     */
    protected $containingTagRead = false;

    /**
     * parentsReferenced
     *
     * Stores whether the parents have been referenced into pointers
     *
     * @access private
     */
    private $parentsReferenced = false;

    /**
     * treeReferenced
     *
     * Stores whether the tree has been referenced into a pointer
     *
     * @access private
     */
    private $treeReferenced = false;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash object hash
     * @return mixed git object
     * @throws \Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        parent::__construct($project, $hash);
    }

    /**
     * GetParent
     *
     * Gets the main parent of this commit
     *
     * @access public
     * @return mixed commit object for parent
     */
    public function GetParent() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if ($this->parentsReferenced) {
            $this->DereferenceParents();
        }

        if (isset($this->parents[0])) {
            return $this->parents[0];
        }
        return null;
    }

    /**
     * GetParents
     *
     * Gets an array of parent objects for this commit
     *
     * @access public
     * @return mixed array of commit objects
     */
    public function GetParents() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if ($this->parentsReferenced) {
            $this->DereferenceParents();
        }

        return $this->parents;
    }

    /**
     * GetTree
     *
     * Gets the tree for this commit
     *
     * @access public
     * @return mixed tree object
     */
    public function GetTree() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if ($this->treeReferenced) {
            $this->DereferenceTree();
        }

        return $this->tree;
    }

    /**
     * GetAuthor
     *
     * Gets the author for this commit
     *
     * @access public
     * @return string author
     */
    public function GetAuthor() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->author;
    }

    /**
     * GetAuthorName
     *
     * Gets the author's name only
     *
     * @access public
     * @return string author name
     */
    public function GetAuthorName() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return preg_replace('/ <.*/', '', $this->author);
    }

    /**
     * @return string
     */
    public function getAuthorEmail()
    {
        preg_match('/<(.*)>/', $this->GetAuthor(), $matches);

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * GetAuthorEpoch
     *
     * Gets the author's epoch
     *
     * @access public
     * @return string author epoch
     */
    public function GetAuthorEpoch() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->authorEpoch;
    }

    /**
     * GetAuthorLocalEpoch
     *
     * Gets the author's local epoch
     *
     * @access public
     * @return string author local epoch
     */
    public function GetAuthorLocalEpoch() // @codingStandardsIgnoreLine
    {
        $epoch = $this->GetAuthorEpoch();
        $tz    = $this->GetAuthorTimezone();
        if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
            $local = $epoch + ((((int) $regs[1]) + ($regs[2] / 60)) * 3600);
            return $local;
        }
        return $epoch;
    }

    /**
     * GetAuthorTimezone
     *
     * Gets the author's timezone
     *
     * @access public
     * @return string author timezone
     */
    public function GetAuthorTimezone() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->authorTimezone;
    }

    /**
     * GetCommitter
     *
     * Gets the author for this commit
     *
     * @access public
     * @return string author
     */
    public function GetCommitter() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->committer;
    }

    /**
     * @return string
     */
    public function getCommitterEmail()
    {
        preg_match('/<(.*)>/', $this->GetCommitter(), $matches);

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * GetCommitterName
     *
     * Gets the author's name only
     *
     * @access public
     * @return string author name
     */
    public function GetCommitterName() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return preg_replace('/ <.*/', '', $this->committer);
    }

    /**
     * GetCommitterEpoch
     *
     * Gets the committer's epoch
     *
     * @access public
     * @return string committer epoch
     */
    public function GetCommitterEpoch() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->committerEpoch;
    }

    /**
     * GetCommitterLocalEpoch
     *
     * Gets the committer's local epoch
     *
     * @access public
     * @return string committer local epoch
     */
    public function GetCommitterLocalEpoch() // @codingStandardsIgnoreLine
    {
        $epoch = $this->GetCommitterEpoch();
        $tz    = $this->GetCommitterTimezone();
        if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
            $local = $epoch + ((((int) $regs[1]) + ($regs[2] / 60)) * 3600);
            return $local;
        }
        return $epoch;
    }

    /**
     * GetCommitterTimezone
     *
     * Gets the author's timezone
     *
     * @access public
     * @return string author timezone
     */
    public function GetCommitterTimezone() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->committerTimezone;
    }

    /**
     * GetTitle
     *
     * Gets the commit title
     *
     * @access public
     * @param int $trim length to trim to (0 for no trim)
     * @return string | null
     */
    public function GetTitle($trim = 0) // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if (($trim > 0) && (mb_strlen($this->title) > $trim)) {
            return mb_substr($this->title, 0, $trim) . '…';
        }

        return $this->title;
    }

    /**
     * GetComment
     *
     * Gets the lines of comment
     *
     * @access public
     * @return array lines of comment
     */
    public function GetComment() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->comment;
    }

    public function getSignature(): ?string
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }
        return $this->signature;
    }

    /**
     * SearchComment
     *
     * Gets the lines of the comment matching the given pattern
     *
     * @access public
     * @param string $pattern pattern to find
     * @return array matching lines of comment
     */
    public function SearchComment($pattern) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return $this->GetComment();
        }

        if (! $this->dataRead) {
            $this->ReadData();
        }

        return preg_grep('/' . preg_quote($pattern, '/') . '/i', $this->comment);
    }

    /**
     * @return string[]
     */
    public function getDescriptionAsArray()
    {
        $comment = $this->GetComment();
        array_shift($comment);

        return $comment;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return trim(implode("\n", $this->getDescriptionAsArray()));
    }

    /**
     * @param string $pattern
     * @return string[]
     */
    public function searchDescription($pattern)
    {
        if (empty($pattern)) {
            return [];
        }

        return preg_grep('/' . preg_quote($pattern, '/') . '/i', $this->getDescriptionAsArray());
    }

    /**
     * GetAge
     *
     * Gets the age of the commit
     *
     * @access public
     * @return string age
     */
    public function GetAge() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if (! empty($this->committerEpoch)) {
            return time() - $this->committerEpoch;
        }

        return '';
    }

    /**
     * IsMergeCommit
     *
     * Returns whether this is a merge commit
     *
     * @access pubilc
     * @return bool true if merge commit
     */
    public function IsMergeCommit() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return count($this->parents) > 1;
    }

    /**
     * ReadData
     *
     * Read the data for the commit
     *
     * @access protected
     */
    protected function ReadData() // @codingStandardsIgnoreLine
    {
        $this->dataRead = true;

        $lines = null;
        $data  = $this->GetProject()->GetObject($this->hash);
        if (empty($data)) {
            return;
        }

        $lines = explode("\n", $data);

        $header               = true;
        $is_parsing_signature = false;

        foreach ($lines as $i => $line) {
            if ($is_parsing_signature) {
                if (strlen($line) > 0 && $line[0] === ' ') {
                    $this->signature .= ltrim($line, ' ') . "\n";
                    continue;
                }
                $this->signature      = rtrim($this->signature ?? '');
                $is_parsing_signature = false;
            }
            if ($header && preg_match('/^tree ([0-9a-fA-F]{40})$/', $line, $regs)) {
                /* Tree */
                try {
                    $tree = $this->GetProject()->GetTree($regs[1]);
                    if ($tree) {
                        $tree->SetCommit($this);
                        $this->tree = $tree;
                    }
                } catch (\Exception $e) {
                }
            } elseif ($header && preg_match('/^parent ([0-9a-fA-F]{40})$/', $line, $regs)) {
                /* Parent */
                try {
                    $this->parents[] = $this->GetProject()->GetCommit($regs[1]);
                } catch (\Exception $e) {
                }
            } elseif ($header && preg_match('/^author (.*) ([0-9]+) (.*)$/', $line, $regs)) {
                /* author data */
                $this->author         = $regs[1];
                $this->authorEpoch    = $regs[2];
                $this->authorTimezone = $regs[3];
            } elseif ($header && preg_match('/^committer (.*) ([0-9]+) (.*)$/', $line, $regs)) {
                /* committer data */
                $this->committer         = $regs[1];
                $this->committerEpoch    = $regs[2];
                $this->committerTimezone = $regs[3];
            } elseif ($header && str_starts_with($line, self::HEADER_SIGNATURE_SECTION . ' ')) {
                $this->signature      = substr($line, strlen(self::HEADER_SIGNATURE_SECTION . ' ')) . "\n";
                $is_parsing_signature = true;
            } else {
                /* commit comment */
                $header  = false;
                $trimmed = trim($line);
                if (empty($this->title) && (strlen($trimmed) > 0)) {
                    $this->title = $trimmed;
                }
                if (! empty($this->title)) {
                    if ((strlen($line) > 0) || ($i < (count($lines) - 1))) {
                        $this->comment[] = $line;
                    }
                }
            }
        }
    }

    /**
     * GetHeads
     *
     * Gets heads that point to this commit
     *
     * @access public
     * @return array array of heads
     */
    public function GetHeads() // @codingStandardsIgnoreLine
    {
        $heads = [];

        $projectRefs = $this->GetProject()->GetRefs('heads');

        foreach ($projectRefs as $ref) {
            if ($ref->GetHash() == $this->hash) {
                $heads[] = $ref;
            }
        }

        return $heads;
    }

    /**
     * GetTags
     *
     * Gets tags that point to this commit
     *
     * @access public
     * @return array array of tags
     */
    public function GetTags() // @codingStandardsIgnoreLine
    {
        $tags = [];

        $projectRefs = $this->GetProject()->GetRefs('tags');

        foreach ($projectRefs as $ref) {
            if (($ref->GetType() == 'tag') || ($ref->GetType() == 'commit')) {
                if ($ref->GetCommit()->GetHash() === $this->hash) {
                    $tags[] = $ref;
                }
            }
        }

        return $tags;
    }

    /**
     * GetContainingTag
     *
     * Gets the tag that contains the changes in this commit
     *
     * @access public
     * @return Tag object
     */
    public function GetContainingTag() // @codingStandardsIgnoreLine
    {
        if (! $this->containingTagRead) {
            $this->ReadContainingTag();
        }

        return $this->containingTag;
    }

    /**
     * ReadContainingTag
     *
     * Looks up the tag that contains the changes in this commit
     *
     * @access private
     */
    public function ReadContainingTag() // @codingStandardsIgnoreLine
    {
        $this->containingTagRead = true;

        $exe    = new GitExe($this->GetProject());
        $args   = [];
        $args[] = '--tags';
        $args[] = '--';
        $args[] = escapeshellarg($this->hash);
        $revs   = explode("\n", $exe->Execute(GitExe::NAME_REV, $args));

        foreach ($revs as $revline) {
            if (preg_match('/^([0-9a-fA-F]{40})\s+tags\/(.+)(\^[0-9]+|\~[0-9]+)$/', $revline, $regs)) {
                if ($regs[1] == $this->hash) {
                    $this->containingTag = $this->GetProject()->GetTag($regs[2]);
                    break;
                }
            }
        }
    }

    /**
     * DiffToParent
     *
     * Diffs this commit with its immediate parent
     *
     * @access public
     * @return mixed Tree diff
     */
    public function DiffToParent() // @codingStandardsIgnoreLine
    {
        return new TreeDiff($this->GetProject(), $this->hash);
    }

    /**
     * PathToHash
     *
     * Given a filepath, get its hash
     *
     * @access public
     * @param string $path path
     * @return string hash
     */
    public function PathToHash($path) // @codingStandardsIgnoreLine
    {
        if ($path === '') {
            return '';
        }

        if (! $this->hashPathsRead) {
            $this->ReadHashPaths();
        }

        if (isset($this->blobPaths[$path])) {
            return $this->blobPaths[$path];
        }

        if (isset($this->treePaths[$path])) {
            return $this->treePaths[$path];
        }

        if (isset($this->commit_paths[$path])) {
            return $this->commit_paths[$path];
        }

        return '';
    }

    /**
     * ReadHashPaths
     *
     * Read hash to path mappings
     *
     * @access private
     */
    private function ReadHashPaths() // @codingStandardsIgnoreLine
    {
        $this->hashPathsRead = true;
        $this->ReadHashPathsRaw($this->GetTree());
    }

    /**
     * ReadHashPathsRaw
     *
     * Reads hash to path mappings using raw objects
     *
     * @access private
     */
    private function ReadHashPathsRaw($tree) // @codingStandardsIgnoreLine
    {
        if (! $tree) {
            return;
        }

        $contents = $tree->GetContents();

        foreach ($contents as $obj) {
            if ($obj instanceof Blob) {
                $hash                         = $obj->GetHash();
                $path                         = $obj->GetPath();
                $this->blobPaths[trim($path)] = $hash;
            } elseif ($obj instanceof Tree) {
                $hash                         = $obj->GetHash();
                $path                         = $obj->GetPath();
                $this->treePaths[trim($path)] = $hash;
                $this->ReadHashPathsRaw($obj);
            } elseif ($obj instanceof Submodule) {
                $hash                            = $obj->GetHash();
                $path                            = $obj->getPath();
                $this->commit_paths[trim($path)] = $hash;
            }
        }
    }

    /**
     * SearchFilenames
     *
     * Returns array of objects matching pattern
     *
     * @access public
     * @param string $pattern pattern to find
     * @return array array of objects
     */
    public function SearchFilenames($pattern) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return;
        }

        if (! $this->hashPathsRead) {
            $this->ReadHashPaths();
        }

        $results = [];

        foreach ($this->treePaths as $path => $hash) {
            if (preg_match('/' . $pattern . '/i', $path)) {
                $obj = $this->GetProject()->GetTree($hash);
                $obj->SetCommit($this);
                $results[$path] = $obj;
            }
        }

        foreach ($this->blobPaths as $path => $hash) {
            if (preg_match('/' . $pattern . '/i', $path)) {
                $obj = $this->GetProject()->GetBlob($hash);
                $obj->SetCommit($this);
                $results[$path] = $obj;
            }
        }

        ksort($results);

        return $results;
    }

    /**
     * SearchFileContents
     *
     * Searches for a pattern in file contents
     *
     * @access public
     * @param string $pattern pattern to search for
     * @return array multidimensional array of results
     */
    public function SearchFileContents($pattern) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return;
        }

        $exe = new GitExe($this->GetProject());

        $args   = [];
        $args[] = '-I';
        $args[] = '--full-name';
        $args[] = '--ignore-case';
        $args[] = '-n';
        $args[] = '-e';
        $args[] = escapeshellarg($pattern);
        $args[] = escapeshellarg($this->hash);

        $lines = explode("\n", $exe->Execute(GitExe::GREP, $args));

        $results = [];

        foreach ($lines as $line) {
            if (preg_match('/^[^:]+:([^:]+):([0-9]+):(.+)$/', $line, $regs)) {
                if (! isset($results[$regs[1]]['object'])) {
                    $hash = $this->PathToHash($regs[1]);
                    if (! empty($hash)) {
                        $obj = $this->GetProject()->GetBlob($hash);
                        $obj->SetCommit($this);
                        $results[$regs[1]]['object'] = $obj;
                    }
                }
                $results[$regs[1]]['lines'][(int) ($regs[2])] = $regs[3];
            }
        }

        return $results;
    }

    /**
     * SearchFiles
     *
     * Searches filenames and file contents for a pattern
     *
     * @access public
     * @param string $pattern pattern to search
     * @param int $count number of results to get
     * @param int $skip number of results to skip
     * @return array array of results
     */
    public function SearchFiles($pattern, $count = 100, $skip = 0) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return;
        }

        $grepresults = $this->SearchFileContents($pattern);

        $nameresults = $this->SearchFilenames($pattern);

        /* Merge the results together */
        foreach ($nameresults as $path => $obj) {
            if (! isset($grepresults[$path]['object'])) {
                $grepresults[$path]['object'] = $obj;
            }
        }

        ksort($grepresults);

        return array_slice($grepresults, $skip, $count, true);
    }

    /**
     * ReferenceParents
     *
     * Turns the list of parents into reference pointers
     *
     * @access private
     */
    private function ReferenceParents() // @codingStandardsIgnoreLine
    {
        if ($this->parentsReferenced) {
            return;
        }

        if ((! isset($this->parents)) || (count($this->parents) < 1)) {
            return;
        }

        for ($i = 0; $i < count($this->parents); $i++) {
            $this->parents[$i] = $this->parents[$i]->GetHash();
        }

        $this->parentsReferenced = true;
    }

    /**
     * DereferenceParents
     *
     * Turns the list of parent pointers back into objects
     *
     * @access private
     */
    private function DereferenceParents() // @codingStandardsIgnoreLine
    {
        if (! $this->parentsReferenced) {
            return;
        }

        if ((! $this->parents) || (count($this->parents) < 1)) {
            return;
        }

        for ($i = 0; $i < count($this->parents); $i++) {
            $this->parents[$i] = $this->GetProject()->GetCommit($this->parents[$i]);
        }

        $this->parentsReferenced = false;
    }

    /**
     * ReferenceTree
     *
     * Turns the tree into a reference pointer
     *
     * @access private
     */
    private function ReferenceTree() // @codingStandardsIgnoreLine
    {
        if ($this->treeReferenced) {
            return;
        }

        if (! $this->tree) {
            return;
        }

        $this->tree = $this->tree->GetHash();

        $this->treeReferenced = true;
    }

    /**
     * DereferenceTree
     *
     * Turns the tree pointer back into an object
     *
     * @access private
     */
    private function DereferenceTree() // @codingStandardsIgnoreLine
    {
        if (! $this->treeReferenced) {
            return;
        }

        if (empty($this->tree)) {
            return;
        }

        $this->tree = $this->GetProject()->GetTree($this->tree);

        if ($this->tree) {
            $this->tree->SetCommit($this);
        }

        $this->treeReferenced = false;
    }

    /**
     * CompareAge
     *
     * Compares two commits by age
     *
     * @access public
     * @static
     * @param mixed $a first commit
     * @param mixed $b second commit
     * @return int comparison result
     */
    public static function CompareAge($a, $b) // @codingStandardsIgnoreLine
    {
        if ($a->GetAge() === $b->GetAge()) {
            return 0;
        }
        return ($a->GetAge() < $b->GetAge() ? -1 : 1);
    }
}
