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
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $tag)
	{
		parent::__construct($project, 'tags', $tag);
	}

	/**
	 * GetObject
	 *
	 * Gets the object this tag points to
	 *
	 * @access public
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
	 */
	public function GetComment()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->comment;
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

		/* get data from tag object */
		$exe = new GitPHP_GitExe($this->project);
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

}
