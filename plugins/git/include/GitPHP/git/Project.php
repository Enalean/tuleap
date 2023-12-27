<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Git_Exec;

/**
 * Project class
 *
 */
class Project
{
/* internal variables {{{1*/

    /**
     * projectRoot
     *
     * Stores the project root internally
     *
     * @access protected
     */
    protected $projectRoot;

    /**
     * project
     *
     * Stores the project internally
     *
     * @access protected
     */
    protected $project;

/* owner internal variables {{{2*/

    /**
     * owner
     *
     * Stores the owner internally
     *
     * @access protected
     */
    protected $owner = "";

    /**
     * ownerRead
     *
     * Stores whether the file owner has been read
     *
     * @access protected
     */
    protected $ownerRead = false;

/*}}}2*/

/* description internal variables {{{2*/

    /**
     * description
     *
     * Stores the description internally
     *
     * @access protected
     */
    protected $description;

    /**
     * readDescription
     *
     * Stores whether the description has been
     * read from the file yet
     *
     * @access protected
     */
    protected $readDescription = false;

/*}}}2*/

/* epoch internal variables {{{2*/

    /**
     * epoch
     *
     * Stores the project epoch internally
     *
     * @access protected
     */
    protected $epoch;

    /**
     * epochRead
     *
     * Stores whether the project epoch has been read yet
     *
     * @access protected
     */
    protected $epochRead = false;

/*}}}2*/

/* HEAD internal variables {{{2*/

    /**
     * head
     *
     * Stores the head hash internally
     *
     * @access protected
     */
    protected $head;

    /**
     * readHeadRef
     *
     * Stores whether the head ref has been read yet
     *
     * @access protected
     */
    protected $readHeadRef = false;

/*}}}*/

/* ref internal variables {{{2*/

    /**
     * tags
     *
     * Stores the tags for the project
     *
     * @access protected
     */
    protected $tags = [];

    /**
     * heads
     *
     * Stores the heads for the project
     *
     * @access protected
     */
    protected $heads = [];

    /**
     * readRefs
     *
     * Stores whether refs have been read yet
     *
     * @access protected
     */
    protected $readRefs = false;

/*}}}2*/

/* url internal variables {{{2*/

    /**
     * cloneUrl
     *
     * Stores the clone url internally
     *
     * @access protected
     */
    protected $cloneUrl = null;

    /**
     * pushUrl
     *
     * Stores the push url internally
     *
     * @access protected
     */
    protected $pushUrl = null;

/*}}}2*/

/* bugtracker internal variables {{{2*/

    /**
     * bugUrl
     *
     * Stores the bug url internally
     *
     * @access protected
     */
    protected $bugUrl = null;

    /**
     * bugPattern
     *
     * Stores the bug pattern internally
     *
     * @access protected
     */
    protected $bugPattern = null;

/*}}}2*/

    /**
     * website
     *
     * Stores the website url internally
     *
     * @access protected
     */
    protected $website = null;

    /**
     * commitCache
     *
     * Caches fetched commit objects in case of
     * repeated requests for the same object
     *
     * @access protected
     */
    protected $commitCache = [];

/* packfile internal variables {{{2*/

    /**
     * packs
     *
     * Stores the list of packs
     *
     * @access protected
     */
    protected $packs = [];

    /**
     * packsRead
     *
     * Stores whether packs have been read
     *
     * @access protected
     */
    protected $packsRead = false;

/*}}}2*/

    /**
     * compat
     *
     * Stores whether this project is running
     * in compatibility mode
     *
     * @access protected
     */
    protected $compat = null;

    /**
     * @var Git_Exec
     */
    private $git_exec;

    /**
     * __construct
     *
     *
     *
     * @access public
     * @param string $projectRoot project root
     * @param string $project project
     * @throws \Exception if project is invalid or outside of projectroot
     */
    public function __construct($projectRoot, $project, Git_Exec $git_exec)
    {
        $this->projectRoot = Util::AddSlash($projectRoot);
        $this->SetProject($project);
        $this->git_exec = $git_exec;
    }

/*}}}1*/

/* accessors {{{1*/

/* project accessors {{{2*/

    /**
     * GetProject
     *
     * Gets the project
     *
     * @access public
     * @return string the project
     */
    public function GetProject() // @codingStandardsIgnoreLine
    {
        return $this->project;
    }

    /**
     * SetProject
     *
     * Attempts to set the project
     *
     * @access private
     * @throws \Exception if project is invalid or outside of projectroot
     */
    private function SetProject($project) // @codingStandardsIgnoreLine
    {
        $realProjectRoot = realpath($this->projectRoot);
        $path            = $this->projectRoot . $project;
        $fullPath        = realpath($path);

        if (! is_dir($fullPath)) {
            throw new RepositoryNotExistingException(sprintf(dgettext("gitphp", '%1$s is not a directory'), $project));
        }

        if (! is_file($fullPath . '/HEAD')) {
            throw new RepositoryAccessException(sprintf(dgettext("gitphp", '%1$s is not a git repository'), $project));
        }

        if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project)) {
            throw new RepositoryAccessException(sprintf(dgettext("gitphp", '%1$s is attempting directory traversal'), $project));
        }

        $pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

        if ((! is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0)) {
            throw new RepositoryAccessException(sprintf(dgettext("gitphp", '%1$s is outside of the projectroot'), $project));
        }

        $this->project = $project;
    }

/*}}}2*/

    /**
     * GetSlug
     *
     * Gets the project as a filename/url friendly slug
     *
     * @access public
     * @return string the slug
     */
    public function GetSlug() // @codingStandardsIgnoreLine
    {
        $project = $this->project;

        if (substr($project, -4) == '.git') {
            $project = substr($project, 0, -4);
        }

        return Util::MakeSlug($project);
    }

    /**
     * GetPath
     *
     * Gets the full project path
     *
     * @access public
     * @return string project path
     */
    public function GetPath() // @codingStandardsIgnoreLine
    {
        return $this->projectRoot . $this->project;
    }

/* projectroot accessors {{{2*/

    /**
     * GetProjectRoot
     *
     * Gets the project root
     *
     * @access public
     * @return string the project root
     */
    public function GetProjectRoot() // @codingStandardsIgnoreLine
    {
        return $this->projectRoot;
    }

/*}}}2*/

    /**
     * GetDaemonEnabled
     *
     * Returns whether gitdaemon is allowed for this project
     *
     * @access public
     * @return bool git-daemon-export-ok?
     */
    public function GetDaemonEnabled() // @codingStandardsIgnoreLine
    {
        return file_exists($this->GetPath() . '/git-daemon-export-ok');
    }

/* clone url accessors {{{2*/

    /**
     * GetCloneUrl
     *
     * Gets the clone URL for this repository, if specified
     *
     * @access public
     * @return string clone url
     */
    public function GetCloneUrl() // @codingStandardsIgnoreLine
    {
        if ($this->cloneUrl !== null) {
            return $this->cloneUrl;
        }

        $cloneurl = Util::AddSlash(Config::GetInstance()->GetValue('cloneurl', ''));
        if (! empty($cloneurl)) {
            $cloneurl .= $this->project;
        }

        return $cloneurl;
    }

    /**
     * SetCloneUrl
     *
     * Overrides the clone URL for this repository
     *
     * @access public
     * @param string $cUrl clone url
     */
    public function SetCloneUrl($cUrl) // @codingStandardsIgnoreLine
    {
        $this->cloneUrl = $cUrl;
    }

/*}}}2*/

/* push url accessors {{{2*/

    /**
     * GetPushUrl
     *
     * Gets the push URL for this repository, if specified
     *
     * @access public
     * @return string push url
     */
    public function GetPushUrl() // @codingStandardsIgnoreLine
    {
        if ($this->pushUrl !== null) {
            return $this->pushUrl;
        }

        $pushurl = Util::AddSlash(Config::GetInstance()->GetValue('pushurl', ''));
        if (! empty($pushurl)) {
            $pushurl .= $this->project;
        }

        return $pushurl;
    }

    /**
     * SetPushUrl
     *
     * Overrides the push URL for this repository
     *
     * @access public
     * @param string $pUrl push url
     */
    public function SetPushUrl($pUrl) // @codingStandardsIgnoreLine
    {
        $this->pushUrl = $pUrl;
    }

/*}}}2*/

/* bugtracker accessors {{{2*/

    /**
     * GetBugUrl
     *
     * Gets the bug URL for this repository, if specified
     *
     * @access public
     * @return string bug url
     */
    public function GetBugUrl() // @codingStandardsIgnoreLine
    {
        if ($this->bugUrl != null) {
            return $this->bugUrl;
        }

        return Config::GetInstance()->GetValue('bugurl', '');
    }

    /**
     * SetBugUrl
     *
     * Overrides the bug URL for this repository
     *
     * @access public
     * @param string $bUrl bug url
     */
    public function SetBugUrl($bUrl) // @codingStandardsIgnoreLine
    {
        $this->bugUrl = $bUrl;
    }

    /**
     * GetBugPattern
     *
     * Gets the bug pattern for this repository, if specified
     *
     * @access public
     * @return string bug pattern
     */
    public function GetBugPattern() // @codingStandardsIgnoreLine
    {
        if ($this->bugPattern != null) {
            return $this->bugPattern;
        }

        return Config::GetInstance()->GetValue('bugpattern', '');
    }

    /**
     * SetBugPattern
     *
     * Overrides the bug pattern for this repository
     *
     * @access public
     * @param string $bPat bug pattern
     */
    public function SetBugPattern($bPat) // @codingStandardsIgnoreLine
    {
        $this->bugPattern = $bPat;
    }

/*}}}2*/

/* website accessors {{{2*/

    /**
     * GetWebsite
     *
     * Gets the website for this repository, if specified
     *
     * @access public
     * @return string website
     */
    public function GetWebsite() // @codingStandardsIgnoreLine
    {
        return $this->website;
    }

    /**
     * SetWebsite
     *
     * Sets the website for this repository
     *
     * @access public
     * @param string $site website
     */
    public function SetWebsite($site) // @codingStandardsIgnoreLine
    {
        $this->website = $site;
    }

/*}}}2*/

/* HEAD accessors {{{2*/

    /**
     * GetHeadCommit
     *
     * Gets the head commit for this project
     * Shortcut for getting the tip commit of the HEAD branch
     *
     * @access public
     * @return mixed head commit
     */
    public function GetHeadCommit() // @codingStandardsIgnoreLine
    {
        if (! $this->readHeadRef) {
            $this->ReadHeadCommit();
        }

        return $this->GetCommit($this->head);
    }

    /**
     * ReadHeadCommit
     *
     * Reads the head commit hash
     *
     * @access protected
     */
    public function ReadHeadCommit() // @codingStandardsIgnoreLine
    {
        $this->readHeadRef = true;
        $this->ReadHeadCommitRaw();
    }

    /**
     * ReadHeadCommitRaw
     *
     * Read head commit using raw git head pointer
     *
     * @access private
     */
    private function ReadHeadCommitRaw() // @codingStandardsIgnoreLine
    {
        $head = trim(file_get_contents($this->GetPath() . '/HEAD'));
        if (preg_match('/^([0-9A-Fa-f]{40})$/', $head, $regs)) {
            /* Detached HEAD */
            $this->head = $regs[1];
        } elseif (preg_match('/^ref: (.+)$/', $head, $regs)) {
            /* standard pointer to head */
            if (! $this->readRefs) {
                $this->ReadRefList();
            }

            if (isset($this->heads[$regs[1]])) {
                $this->head = $this->heads[$regs[1]]->GetHash();
            }
        }
    }

/*}}}2*/

/* epoch accessors {{{2*/

    /**
     * GetEpoch
     *
     * Gets this project's epoch
     * (time of last change)
     *
     * @access public
     * @return int timestamp
     */
    public function GetEpoch() // @codingStandardsIgnoreLine
    {
        if (! $this->epochRead) {
            $this->ReadEpoch();
        }

        return $this->epoch;
    }

    /**
     * GetAge
     *
     * Gets this project's age
     * (time since most recent change)
     *
     * @access public
     * @return int age
     */
    public function GetAge() // @codingStandardsIgnoreLine
    {
        if (! $this->epochRead) {
            $this->ReadEpoch();
        }

        return time() - $this->epoch;
    }

    /**
     * ReadEpoch
     *
     * Reads this project's epoch
     * (timestamp of most recent change)
     *
     * @access private
     */
    private function ReadEpoch() // @codingStandardsIgnoreLine
    {
        $this->epochRead = true;
        $this->ReadEpochRaw();
    }

    /**
     * ReadEpochRaw
     *
     * Reads this project's epoch using raw objects
     *
     * @access private
     */
    private function ReadEpochRaw() // @codingStandardsIgnoreLine
    {
        if (! $this->readRefs) {
            $this->ReadRefList();
        }

        $epoch = 0;
        foreach ($this->heads as $head) {
            $commit = $head->GetCommit();
            if ($commit) {
                if ($commit->GetCommitterEpoch() > $epoch) {
                    $epoch = $commit->GetCommitterEpoch();
                }
            }
        }
        if ($epoch > 0) {
            $this->epoch = $epoch;
        }
    }

/*}}}2*/

/*}}}1*/

/* data loading methods {{{1*/

/* commit loading methods {{{2*/

    /**
     * GetCommit
     *
     * Get a commit for this project
     *
     * @access public
     *
     * @return Commit|null
     */
    public function GetCommit($hash) // @codingStandardsIgnoreLine
    {
        if ($hash === '') {
            return null;
        }

        if ($hash === 'HEAD') {
            return $this->GetHeadCommit();
        }

        if (substr_compare($hash, 'refs/heads/', 0, 11) === 0) {
            $head = $this->GetHead(substr($hash, 11));
            if ($head != null) {
                return $head->GetCommit();
            }
            return null;
        } elseif (substr_compare($hash, 'refs/tags/', 0, 10) === 0) {
            $tag = $this->GetTag(substr($hash, 10));
            if ($tag != null) {
                $obj = $tag->GetCommit();
                if ($obj != null) {
                    return $obj;
                }
            }
            return null;
        }
        if (preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            if (! isset($this->commitCache[$hash])) {
                $this->commitCache[$hash] = new Commit($this, $hash);
            }

            return $this->commitCache[$hash];
        }

        if (! $this->readRefs) {
            $this->ReadRefList();
        }

        if (isset($this->heads['refs/heads/' . $hash])) {
            return $this->heads['refs/heads/' . $hash]->GetCommit();
        }

        if (isset($this->tags['refs/tags/' . $hash])) {
            return $this->tags['refs/tags/' . $hash]->GetCommit();
        }

        return null;
    }

/*}}}2*/

/* ref loading methods {{{2*/

    /**
     * GetRefs
     *
     * Gets the list of refs for the project
     *
     * @access public
     * @param string $type type of refs to get
     * @return array array of refs
     */
    public function GetRefs($type = '') // @codingStandardsIgnoreLine
    {
        if (! $this->readRefs) {
            $this->ReadRefList();
        }

        if ($type == 'tags') {
            return $this->tags;
        } elseif ($type == 'heads') {
            return $this->heads;
        }

        return array_merge($this->heads, $this->tags);
    }

    /**
     * ReadRefList
     *
     * Reads the list of refs for this project
     *
     * @access protected
     */
    protected function ReadRefList() // @codingStandardsIgnoreLine
    {
        $this->readRefs = true;
        $this->ReadRefListRaw();
    }

    /**
     * ReadRefListRaw
     *
     * Reads the list of refs for this project using the raw git files
     *
     * @access private
     */
    private function ReadRefListRaw() // @codingStandardsIgnoreLine
    {
        $pathlen = strlen($this->GetPath()) + 1;

        // read loose heads
        $heads = $this->ListDir($this->GetPath() . '/refs/heads');
        for ($i = 0; $i < count($heads); $i++) {
            $key = trim(substr($heads[$i], $pathlen), "/\\");

            if (isset($this->heads[$key])) {
                continue;
            }

            $hash = trim(file_get_contents($heads[$i]));
            if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
                $head              = substr($key, strlen('refs/heads/'));
                $this->heads[$key] = new Head($this, $head, $hash);
            }
        }

        // read loose tags
        $tags = $this->ListDir($this->GetPath() . '/refs/tags');
        for ($i = 0; $i < count($tags); $i++) {
            $key = trim(substr($tags[$i], $pathlen), "/\\");

            if (isset($this->tags[$key])) {
                continue;
            }

            $hash = trim(file_get_contents($tags[$i]));
            if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
                $tag              = substr($key, strlen('refs/tags/'));
                $this->tags[$key] = $this->LoadTag($tag, $hash);
            }
        }

        // check packed refs
        if (file_exists($this->GetPath() . '/packed-refs')) {
            $packedRefs = explode("\n", file_get_contents($this->GetPath() . '/packed-refs'));

            $lastRef = null;
            foreach ($packedRefs as $ref) {
                if (preg_match('/^\^([0-9A-Fa-f]{40})$/', $ref, $regs)) {
                    // dereference of previous ref
                    if (($lastRef != null) && ($lastRef instanceof Tag)) {
                        $derefCommit = $this->GetCommit($regs[1]);
                        if ($derefCommit) {
                            $lastRef->SetCommit($derefCommit);
                        }
                    }
                }

                $lastRef = null;

                if (preg_match('/^([0-9A-Fa-f]{40}) refs\/(tags|heads)\/(.+)$/', $ref, $regs)) {
                    // standard tag/head
                    $key = 'refs/' . $regs[2] . '/' . $regs[3];
                    if ($regs[2] == 'tags') {
                        if (! isset($this->tags[$key])) {
                            $lastRef          = $this->LoadTag($regs[3], $regs[1]);
                            $this->tags[$key] = $lastRef;
                        }
                    } elseif ($regs[2] == 'heads') {
                        if (! isset($this->heads[$key])) {
                            $this->heads[$key] = new Head($this, $regs[3], $regs[1]);
                        }
                    }
                }
            }
        }
    }

    /**
     * ListDir
     *
     * Recurses into a directory and lists files inside
     *
     * @access private
     * @param string $dir directory
     * @return array array of filenames
     */
    private function ListDir($dir) // @codingStandardsIgnoreLine
    {
        $files = [];
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (($file == '.') || ($file == '..')) {
                    continue;
                }
                $fullFile = $dir . '/' . $file;
                if (is_dir($fullFile)) {
                    $subFiles = $this->ListDir($fullFile);
                    if (count($subFiles) > 0) {
                        $files = array_merge($files, $subFiles);
                    }
                } else {
                    $files[] = $fullFile;
                }
            }
        }
        return $files;
    }

/*}}}2*/

/* tag loading methods {{{2*/

    /**
     * GetTags
     *
     * Gets list of tags for this project by age descending
     *
     * @access public
     * @param int $count number of tags to load
     * @return array array of tags
     */
    public function GetTags($count = 0) // @codingStandardsIgnoreLine
    {
        if (! $this->readRefs) {
            $this->ReadRefList();
        }
        return $this->GetTagsRaw($count);
    }

    /**
     * GetTagsRaw
     *
     * Gets list of tags for this project by age descending using raw git objects
     *
     * @access private
     * @param int $count number of tags to load
     * @return array array of tags
     */
    private function GetTagsRaw($count = 0) // @codingStandardsIgnoreLine
    {
        $tags = $this->git_exec->getAllTagsSortedByCreationDate();

        if (($count > 0) && (count($tags) > $count)) {
            $tags = array_slice($tags, 0, $count);
        }

        return $tags;
    }

    /**
     * GetTag
     *
     * Gets a single tag
     *
     * @access public
     * @param string $tag tag to find
     * @return mixed tag object
     */
    public function GetTag($tag) // @codingStandardsIgnoreLine
    {
        if ($tag === '') {
            return null;
        }

        if (! $this->readRefs) {
            $this->ReadRefList();
        }

        $key = 'refs/tags/' . $tag;

        if (! isset($this->tags[$key])) {
            $this->tags[$key] = $this->LoadTag($tag);
        }

        return $this->tags[$key];
    }

    /**
     * LoadTag
     *
     * Attempts to load a cached tag, or creates a new object
     *
     * @access private
     * @param string $tag tag to find
     * @return mixed tag object
     */
    private function LoadTag($tag, $hash = '') // @codingStandardsIgnoreLine
    {
        if ($tag === '') {
            return;
        }

        return new Tag($this, $tag, $hash);
    }

/*}}}2*/

/* head loading methods {{{2*/

    /**
     * GetHeads
     *
     * Gets list of heads for this project by age descending
     *
     * @access public
     * @param int $count number of tags to load
     * @return array array of heads
     */
    public function GetHeads($count = 0) // @codingStandardsIgnoreLine
    {
        if (! $this->readRefs) {
            $this->ReadRefList();
        }
        return $this->GetHeadsRaw($count);
    }

    /**
     * GetHeadsRaw
     *
     * Gets the list of sorted heads using raw git objects
     *
     * @access private
     * @param int $count number of tags to load
     * @return array array of heads
     */
    private function GetHeadsRaw($count = 0) // @codingStandardsIgnoreLine
    {
        $heads = $this->git_exec->getAllBranchesSortedByCreationDate();

        if (($count > 0) && (count($heads) > $count)) {
            $heads = array_slice($heads, 0, $count);
        }
        return $heads;
    }

    /**
     * GetHead
     *
     * Gets a single head
     *
     * @access public
     * @param string $head head to find
     * @return mixed head object
     */
    public function GetHead($head) // @codingStandardsIgnoreLine
    {
        if (empty($head)) {
            return null;
        }

        if (! $this->readRefs) {
            $this->ReadRefList();
        }

        $key = 'refs/heads/' . $head;

        if (! isset($this->heads[$key])) {
            $this->heads[$key] = new Head($this, $head);
        }

        return $this->heads[$key];
    }

/*}}}2*/

/* log methods {{{2*/

    /**
     * GetLogHash
     *
     * Gets log entries as an array of hashes
     *
     * @access private
     * @param string $hash hash to start the log at
     * @param int $count number of entries to get
     * @param int $skip number of entries to skip
     * @return array array of hashes
     */
    private function GetLogHash($hash, $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        return $this->RevList($hash, $count, $skip);
    }

    /**
     * GetLog
     *
     * Gets log entries as an array of commit objects
     *
     * @access public
     * @param string $hash hash to start the log at
     * @param int $count number of entries to get
     * @param int $skip number of entries to skip
     * @return array array of commit objects
     */
    public function GetLog($hash, $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        if ($skip > Config::GetInstance()->GetValue('largeskip', 200)) {
            return $this->GetLogGit($hash, $count, $skip);
        } else {
            return $this->GetLogRaw($hash, $count, $skip);
        }
    }

    /**
     * GetLogGit
     *
     * Gets log entries using git exe
     *
     * @access private
     * @param string $hash hash to start the log at
     * @param int $count number of entries to get
     * @param int $skip number of entries to skip
     * @return array array of commit objects
     */
    private function GetLogGit($hash, $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        $log = $this->GetLogHash($hash, $count, $skip);
        $len = count($log);
        for ($i = 0; $i < $len; ++$i) {
            $log[$i] = $this->GetCommit($log[$i]);
        }
        return $log;
    }

    /**
     * GetLogRaw
     *
     * Gets log entries using raw git objects
     * Based on history walking code from glip
     *
     * @access private
     */
    private function GetLogRaw($hash, $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        $total = $count + $skip;

        $inc   = [];
        $num   = 0;
        $queue = [$this->GetCommit($hash)];
        while (($commit = array_shift($queue)) !== null) {
            $parents = $commit->GetParents();
            foreach ($parents as $parent) {
                if (! isset($inc[$parent->GetHash()])) {
                    $inc[$parent->GetHash()] = 1;
                    $queue[]                 = $parent;
                    $num++;
                } else {
                    $inc[$parent->GetHash()]++;
                }
            }
            if ($num >= $total) {
                break;
            }
        }

        $queue = [$this->GetCommit($hash)];
        $log   = [];
        $num   = 0;
        while (($commit = array_pop($queue)) !== null) {
            array_push($log, $commit);
            $num++;
            if ($num == $total) {
                break;
            }
            $parents = $commit->GetParents();
            foreach ($parents as $parent) {
                if (isset($inc[$parent->GetHash()])) {
                    if (--$inc[$parent->GetHash()] == 0) {
                        $queue[] = $parent;
                    }
                }
            }
        }

        if ($skip > 0) {
            $log = array_slice($log, $skip, $count);
        }
        return $log;
    }

/*}}}2*/

/* blob loading methods {{{2*/

    /**
     * GetBlob
     *
     * Gets a blob from this project
     *
     * @access public
     * @param string $hash blob hash
     * @return Blob
     */
    public function GetBlob($hash) // @codingStandardsIgnoreLine
    {
        if (empty($hash)) {
            return null;
        }

        return new Blob($this, $hash);
    }

/*}}}2*/

/* tree loading methods {{{2*/

    /**
     * GetTree
     *
     * Gets a tree from this project
     *
     * @access public
     * @param string $hash tree hash
     */
    public function GetTree($hash) // @codingStandardsIgnoreLine
    {
        if (empty($hash)) {
            return null;
        }

        return new Tree($this, $hash);
    }

/*}}}2*/

/* raw object loading methods {{{2*/

    /**
     * GetObject
     *
     * Gets the raw content of an object
     *
     * @access public
     * @param string $hash object hash
     * @return string object data
     */
    public function GetObject($hash, &$type = 0) // @codingStandardsIgnoreLine
    {
        if (! preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
            return false;
        }

        // first check if it's unpacked
        /**
         * @psalm-taint-escape file
         */
        $path = $this->GetPath() . '/objects/' . substr($hash, 0, 2) . '/' . substr($hash, 2);
        if (file_exists($path)) {
            list($header, $data) = explode("\0", gzuncompress(file_get_contents($path)), 2);
            sscanf($header, "%s %d", $typestr, $size);
            switch ($typestr) {
                case 'commit':
                    $type = Pack::OBJ_COMMIT;
                    break;
                case 'tree':
                    $type = Pack::OBJ_TREE;
                    break;
                case 'blob':
                    $type = Pack::OBJ_BLOB;
                    break;
                case 'tag':
                    $type = Pack::OBJ_TAG;
                    break;
            }
            return $data;
        }

        if (! $this->packsRead) {
            $this->ReadPacks();
        }

        // then try packs
        foreach ($this->packs as $pack) {
            $data = $pack->GetObject($hash, $type);
            if ($data !== false) {
                return $data;
            }
        }

        return false;
    }

    /**
     * ReadPacks
     *
     * Read the list of packs in the repository
     *
     * @access private
     */
    private function ReadPacks() // @codingStandardsIgnoreLine
    {
        $dh = opendir($this->GetPath() . '/objects/pack');
        if ($dh !== false) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/^pack-([0-9A-Fa-f]{40})\.idx$/', $file, $regs)) {
                    $this->packs[] = new Pack($this, $regs[1]);
                }
            }
        }
        $this->packsRead = true;
    }

/*}}}2*/

/*}}}1*/

/* search methods {{{1*/

    /**
     * SearchCommit
     *
     * Gets a list of commits with commit messages matching the given pattern
     *
     * @access public
     * @param string $pattern search pattern
     * @param string $hash hash to start searching from
     * @param int $count number of results to get
     * @param int $skip number of results to skip
     * @return array array of matching commits
     */
    public function SearchCommit($pattern, $hash = 'HEAD', $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return;
        }

        $args = [];

        $args[] = '--regexp-ignore-case';
        $args[] = '--grep=' . escapeshellarg($pattern);

        $ret = $this->RevList($hash, $count, $skip, $args);
        $len = count($ret);

        for ($i = 0; $i < $len; ++$i) {
            $ret[$i] = $this->GetCommit($ret[$i]);
        }
        return $ret;
    }

    /**
     * SearchAuthor
     *
     * Gets a list of commits with authors matching the given pattern
     *
     * @access public
     * @param string $pattern search pattern
     * @param string $hash hash to start searching from
     * @param int $count number of results to get
     * @param int $skip number of results to skip
     * @return array array of matching commits
     */
    public function SearchAuthor($pattern, $hash = 'HEAD', $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return;
        }

        $args = [];

        $args[] = '--regexp-ignore-case';
        $args[] = '--author=' . escapeshellarg($pattern);

        $ret = $this->RevList($hash, $count, $skip, $args);
        $len = count($ret);

        for ($i = 0; $i < $len; ++$i) {
            $ret[$i] = $this->GetCommit($ret[$i]);
        }
        return $ret;
    }

    /**
     * SearchCommitter
     *
     * Gets a list of commits with committers matching the given pattern
     *
     * @access public
     * @param string $pattern search pattern
     * @param string $hash hash to start searching from
     * @param int $count number of results to get
     * @param int $skip number of results to skip
     * @return array array of matching commits
     */
    public function SearchCommitter($pattern, $hash = 'HEAD', $count = 50, $skip = 0) // @codingStandardsIgnoreLine
    {
        if (empty($pattern)) {
            return;
        }

        $args = [];

        $args[] = '--regexp-ignore-case';
        $args[] = '--committer=' . escapeshellarg($pattern);

        $ret = $this->RevList($hash, $count, $skip, $args);
        $len = count($ret);

        for ($i = 0; $i < $len; ++$i) {
            $ret[$i] = $this->GetCommit($ret[$i]);
        }
        return $ret;
    }

/*}}}1*/

/* private utilities {{{1*/

    /**
     * RevList
     *
     * Common code for using rev-list command
     *
     * @access private
     * @param string $hash hash to list from
     * @param int $count number of results to get
     * @param int $skip number of results to skip
     * @param array $args args to give to rev-list
     * @return array array of hashes
     */
    private function RevList($hash, $count = 50, $skip = 0, $args = array()) // @codingStandardsIgnoreLine
    {
        if ($count < 1) {
            return;
        }

        $exe = new GitExe($this);

        $args[] = '--max-count=' . escapeshellarg($count);
        if ($skip > 0) {
            $args[] = '--skip=' . escapeshellarg($skip);
        }

        $args[] = escapeshellarg($hash);

        $revlist = explode("\n", $exe->Execute(GitExe::REV_LIST, $args));

        if (! $revlist[count($revlist) - 1]) {
            /* the last newline creates a null entry */
            array_splice($revlist, -1, 1);
        }

        return $revlist;
    }

/*}}}1*/
}
