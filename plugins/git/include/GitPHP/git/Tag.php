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

/**
 * Tag class
 *
 */
class Tag extends Ref
{
    /**
     * dataRead
     *
     * Indicates whether data for this tag has been read
     *
     * @access protected
     */
    protected $dataRead = false;

    /**
     * object
     *
     * Stores the object internally
     *
     * @access protected
     */
    protected $object;

    /**
     * commit
     *
     * Stores the commit internally
     *
     * @access protected
     */
    protected $commit;

    /**
     * type
     *
     * Stores the type internally
     *
     * @access protected
     */
    protected $type;

    /**
     * tagger
     *
     * Stores the tagger internally
     *
     * @access protected
     */
    protected $tagger;

    /**
     * taggerEpoch
     *
     * Stores the tagger epoch internally
     *
     * @access protected
     */
    protected $taggerEpoch;

    /**
     * taggerTimezone
     *
     * Stores the tagger timezone internally
     *
     * @access protected
     */
    protected $taggerTimezone;

    /**
     * comment
     *
     * Stores the tag comment internally
     *
     * @access protected
     */
    protected $comment = [];

    /**
     * objectReferenced
     *
     * Stores whether the object has been referenced into a pointer
     *
     * @access private
     */
    private $objectReferenced = false;

    /**
     * commitReferenced
     *
     * Stores whether the commit has been referenced into a pointer
     *
     * @access private
     */
    private $commitReferenced = false;

    /**
     * __construct
     *
     * Instantiates tag
     *
     * @access public
     * @param mixed $project the project
     * @param string $tag tag name
     * @param string $tagHash tag hash
     * @return mixed tag object
     * @throws \Exception exception on invalid tag or hash
     */
    public function __construct($project, $tag, $tagHash = '')
    {
        parent::__construct($project, 'tags', $tag, $tagHash);
    }

    /**
     * GetObject
     *
     * Gets the object this tag points to
     *
     * @access public
     * @return mixed object for this tag
     */
    public function GetObject() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if ($this->objectReferenced) {
            $this->DereferenceObject();
        }

        return $this->object;
    }

    /**
     * GetCommit
     *
     * Gets the commit this tag points to
     *
     * @access public
     * @return mixed commit for this tag
     */
    public function GetCommit() // @codingStandardsIgnoreLine
    {
        if ($this->commitReferenced) {
            $this->DereferenceCommit();
        }

        if ($this->commit) {
            return $this->commit;
        }

        if (! $this->dataRead) {
            $this->ReadData();
            if ($this->commitReferenced) {
                $this->DereferenceCommit();
            }
        }

        if (! $this->commit) {
            if ($this->object instanceof Commit) {
                $this->commit = $this->object;
            } elseif ($this->object instanceof Tag) {
                if ($this->GetHash() !== $this->object->GetHash()) {
                    $this->commit = $this->GetProject()->GetCommit($this->object->GetName());
                } else {
                    $exe              = new GitExe($this->project);
                    $initial_git_hash = trim($exe->Execute(GitExe::REV_LIST, ['-n1', escapeshellarg($this->refName)]));

                    $this->commit = $this->GetProject()->GetCommit($initial_git_hash);
                }
            }
        }

        return $this->commit;
    }

    /**
     * SetCommit
     *
     * Sets the commit this tag points to
     *
     * @access public
     * @param mixed $commit commit object
     */
    public function SetCommit($commit) // @codingStandardsIgnoreLine
    {
        if ($this->commitReferenced) {
            $this->DereferenceCommit();
        }

        if (! $this->commit) {
            $this->commit = $commit;
        }
    }

    /**
     * GetType
     *
     * Gets the tag type
     *
     * @access public
     * @return string tag type
     */
    public function GetType() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->type;
    }

    /**
     * GetTagger
     *
     * Gets the tagger
     *
     * @access public
     * @return string tagger
     */
    public function GetTagger() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->tagger;
    }

    /**
     * GetTaggerEpoch
     *
     * Gets the tagger epoch
     *
     * @access public
     * @return string tagger epoch
     */
    public function GetTaggerEpoch() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->taggerEpoch;
    }

    /**
     * GetTaggerLocalEpoch
     *
     * Gets the tagger local epoch
     *
     * @access public
     * @return string tagger local epoch
     */
    public function GetTaggerLocalEpoch() // @codingStandardsIgnoreLine
    {
        $epoch = $this->GetTaggerEpoch();
        $tz    = $this->GetTaggerTimezone();
        if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
            $local = $epoch + ((((int) $regs[1]) + ($regs[2] / 60)) * 3600);
            return $local;
        }
        return $epoch;
    }

    /**
     * GetTaggerTimezone
     *
     * Gets the tagger timezone
     *
     * @access public
     * @return string tagger timezone
     */
    public function GetTaggerTimezone() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->taggerTimezone;
    }

    /**
     * GetAge
     *
     * Gets the tag age
     *
     * @access public
     * @return string age
     */
    public function GetAge() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return time() - $this->taggerEpoch;
    }

    /**
     * GetComment
     *
     * Gets the tag comment
     *
     * @access public
     * @return array comment lines
     */
    public function GetComment() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        return $this->comment;
    }

    /**
     * LightTag
     *
     * Tests if this is a light tag (tag without tag object)
     *
     * @access public
     * @return bool true if tag is light (has no object)
     */
    public function LightTag() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if ($this->objectReferenced) {
            $this->DereferenceObject();
        }

        if (! $this->object) {
            return true;
        }

        return $this->object->GetHash() === $this->GetHash();
    }

    /**
     * ReadData
     *
     * Reads the tag data
     *
     * @access protected
     */
    protected function ReadData() // @codingStandardsIgnoreLine
    {
        $this->dataRead = true;
        $this->ReadDataRaw();
    }

    /**
     * ReadDataRaw
     *
     * Reads the tag data using the raw git object
     *
     * @access private
     */
    private function ReadDataRaw() // @codingStandardsIgnoreLine
    {
        $type = 0;
        $data = $this->GetProject()->GetObject($this->GetHash(), $type);

        if ($type == Pack::OBJ_COMMIT) {
            /* light tag */
            $this->object = $this->GetProject()->GetCommit($this->GetHash());
            $this->commit = $this->object;
            $this->type   = 'commit';
            return;
        }

        $lines = explode("\n", $data);

        if (! isset($lines[0])) {
            return;
        }

        $objectHash = null;

        $readInitialData = false;
        foreach ($lines as $i => $line) {
            if (! $readInitialData) {
                if (preg_match('/^object ([0-9a-fA-F]{40})$/', $line, $regs)) {
                    $objectHash = $regs[1];
                    continue;
                } elseif (preg_match('/^type (.+)$/', $line, $regs)) {
                    $this->type = $regs[1];
                    continue;
                } elseif (preg_match('/^tag (.+)$/', $line, $regs)) {
                    continue;
                } elseif (preg_match('/^tagger (.*) ([0-9]+) (.*)$/', $line, $regs)) {
                    $this->tagger         = $regs[1];
                    $this->taggerEpoch    = $regs[2];
                    $this->taggerTimezone = $regs[3];
                    continue;
                }
            }

            $trimmed = trim($line);

            if ((strlen($trimmed) > 0) || ($readInitialData === true)) {
                $this->comment[] = $line;
            }
            $readInitialData = true;
        }
        switch ($this->type) {
            case 'commit':
                try {
                    $this->object = $this->GetProject()->GetCommit($objectHash ?? '');
                    $this->commit = $this->object;
                } catch (\Exception $e) {
                }
                break;
            case 'tag':
                $objectData = $this->GetProject()->GetObject($objectHash ?? '');
                $lines      = explode("\n", $objectData);
                foreach ($lines as $i => $line) {
                    if (preg_match('/^tag (.+)$/', $line, $regs)) {
                        $name         = trim($regs[1]);
                        $this->object = $this->GetProject()->GetTag($name);
                        if ($this->object) {
                            $this->object->SetHash($objectHash);
                        }
                    }
                }
                break;
            case 'blob':
                $this->object = $this->GetProject()->GetBlob($objectHash ?? '');
                break;
        }
    }

    /**
     * ReferenceObject
     *
     * Turns the object into a reference pointer
     *
     * @access private
     */
    private function ReferenceObject() // @codingStandardsIgnoreLine
    {
        if ($this->objectReferenced) {
            return;
        }

        if (! $this->object) {
            return;
        }

        if ($this->type == 'commit') {
            $this->object = $this->object->GetHash();
        } elseif ($this->type == 'tag') {
            $this->object = $this->object->GetName();
        } elseif ($this->type == 'blob') {
            $this->object = $this->object->GetHash();
        }

        $this->objectReferenced = true;
    }

    /**
     * DereferenceObject
     *
     * Turns the object pointer back into an object
     *
     * @access private
     */
    private function DereferenceObject() // @codingStandardsIgnoreLine
    {
        if (! $this->objectReferenced) {
            return;
        }

        if (empty($this->object)) {
            return;
        }

        if ($this->type == 'commit') {
            $this->object = $this->GetProject()->GetCommit($this->object);
        } elseif ($this->type == 'tag') {
            $this->object = $this->GetProject()->GetTag($this->object);
        } elseif ($this->type == 'blob') {
            $this->object = $this->GetProject()->GetBlob($this->object);
        }

        $this->objectReferenced = false;
    }

    /**
     * ReferenceCommit
     *
     * Turns the commit into a reference pointer
     *
     * @access private
     */
    private function ReferenceCommit() // @codingStandardsIgnoreLine
    {
        if ($this->commitReferenced) {
            return;
        }

        if (! $this->commit) {
            return;
        }

        $this->commit = $this->commit->GetHash();

        $this->commitReferenced = true;
    }

    /**
     * DereferenceCommit
     *
     * Turns the commit pointer back into an object
     *
     * @access private
     */
    private function DereferenceCommit() // @codingStandardsIgnoreLine
    {
        if (! $this->commitReferenced) {
            return;
        }

        if (empty($this->commit)) {
            return;
        }

        if ($this->type == 'commit') {
            $obj = $this->GetObject();
            if ($obj && ($obj->GetHash() == $this->commit)) {
                /*
                 * Light tags are type commit and the commit
                 * and object are the same, in which case
                 * no need to fetch the object again
                 */
                $this->commit           = $obj;
                $this->commitReferenced = false;
                return;
            }
        }

        $this->commit = $this->GetProject()->GetCommit($this->commit);

        $this->commitReferenced = false;
    }

    /**
     * GetCreationEpoch
     *
     * Gets tag's creation epoch
     * (tagger epoch, or committer epoch for light tags)
     *
     * @access public
     * @return string creation epoch
     */
    public function GetCreationEpoch() // @codingStandardsIgnoreLine
    {
        if (! $this->dataRead) {
            $this->ReadData();
        }

        if ($this->LightTag()) {
            $commit = $this->GetCommit();
            if ($commit) {
                return $commit->GetCommitterEpoch();
            } else {
                return $this->taggerEpoch;
            }
        } else {
            return $this->taggerEpoch;
        }
    }

    /**
     * CompareAge
     *
     * Compares two tags by age
     *
     * @access public
     * @static
     * @param mixed $a first tag
     * @param mixed $b second tag
     * @return int comparison result
     */
    public static function CompareAge($a, $b) // @codingStandardsIgnoreLine
    {
        $aObj = $a->GetObject();
        $bObj = $b->GetObject();
        if (($aObj instanceof Commit) && ($bObj instanceof Commit)) {
            return Commit::CompareAge($aObj, $bObj);
        }

        if ($aObj instanceof Commit) {
            return 1;
        }

        if ($bObj instanceof Commit) {
            return -1;
        }

        return strcmp($a->GetName(), $b->GetName());
    }

    /**
     * CompareCreationEpoch
     *
     * Compares to tags by creation epoch
     *
     * @access public
     * @static
     * @param mixed $a first tag
     * @param mixed $b second tag
     * @return int comparison result
     */
    public static function CompareCreationEpoch($a, $b) // @codingStandardsIgnoreLine
    {
        $aEpoch = $a->GetCreationEpoch();
        $bEpoch = $b->GetCreationEpoch();

        if ($aEpoch == $bEpoch) {
            return 0;
        }

        return ($aEpoch < $bEpoch ? 1 : -1);
    }
}
