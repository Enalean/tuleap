<?php
/**
 * GitPHP Tag
 *
 * Represents a single tag object
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');
require_once(GITPHP_INCLUDEDIR . 'git/Ref.class.php');

/**
 * Tag class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tag extends GitPHP_Ref
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
	protected $comment = array();

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
	 * @throws Exception exception on invalid tag or hash
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
	public function GetObject()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->object;
	}

	/**
	 * GetType
	 *
	 * Gets the tag type
	 *
	 * @access public
	 * @return string tag type
	 */
	public function GetType()
	{
		if (!$this->dataRead)
			$this->ReadData();

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
	public function GetTagger()
	{
		if (!$this->dataRead)
			$this->ReadData();

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
	public function GetTaggerEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->taggerEpoch;
	}

	/**
	 * GetTaggerTimezone
	 *
	 * Gets the tagger timezone
	 *
	 * @access public
	 * @return string tagger timezone
	 */
	public function GetTaggerTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->taggerTimezone;
	}

	/**
	 * GetComment
	 *
	 * Gets the tag comment
	 *
	 * @access public
	 * @return array comment lines
	 */
	public function GetComment()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->comment;
	}

	/**
	 * LightTag
	 *
	 * Tests if this is a light tag (tag without tag object)
	 *
	 * @access public
	 * @return boolean true if tag is light (has no object)
	 */
	public function LightTag()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->object)
			return true;

		return $this->object->GetHash() === $this->hash;
	}

	/**
	 * ReadData
	 *
	 * Reads the tag data
	 *
	 * @access protected
	 */
	protected function ReadData()
	{
		$this->dataRead = true;

		$exe = new GitPHP_GitExe($this->project);
		$args = array();
		$args[] = '-t';
		$args[] = $this->hash;
		$ret = trim($exe->Execute(GIT_CAT_FILE, $args));
		
		if ($ret === 'commit') {
			/* light tag */
			$this->object = $this->project->GetCommit($this->hash);
			$this->type = 'commit';
			return;
		}

		/* get data from tag object */
		$args = array();
		$args[] = 'tag';
		$args[] = $this->GetName();
		$ret = $exe->Execute(GIT_CAT_FILE, $args);
		unset($exe);

		$lines = explode("\n", $ret);

		if (!isset($lines[0]))
			return;

		$objectHash = null;

		$readInitialData = false;
		foreach ($lines as $i => $line) {
			if (!$readInitialData) {
				if (preg_match('/^object ([0-9a-fA-F]{40})$/', $line, $regs)) {
					$objectHash = $regs[1];
					continue;
				} else if (preg_match('/^type (.+)$/', $line, $regs)) {
					$this->type = $regs[1];
					continue;
				} else if (preg_match('/^tag (.+)$/', $line, $regs)) {
					if (strcmp($this->refName, trim($regs[1])) !== 0) {
						/* Something is really wrong with your repo */
						throw new Exception('Ref for tag ' . $this->refName . ' points to tag ' . $regs[1]);
					}
					continue;
				} else if (preg_match('/^tagger (.*) ([0-9]+) (.*)$/', $line, $regs)) {
					$this->tagger = $regs[1];
					$this->taggerEpoch = $regs[2];
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
					$this->object = $this->project->GetCommit($objectHash);
				} catch (Exception $e) {
				}
				break;
			/* TODO: add other types */
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
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$aObj = $a->GetObject();
		$bObj = $b->GetObject();
		if (($aObj instanceof GitPHP_Commit) && ($bObj instanceof GitPHP_Commit)) {
			if ($aObj->GetAge() === $bObj->GetAge())
				return 0;
			return ($aObj->GetAge() < $bObj->GetAge() ? -1 : 1);
		}

		if ($aObj instanceof GitPHP_Commit)
			return 1;

		if ($bObj instanceof GitPHP_Commit)
			return -1;

		return strcmp($a->GetName(), $b->GetName());
	}

}
