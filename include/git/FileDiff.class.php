<?php
/**
 * GitPHP File Diff
 *
 * Represents a single file difference
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'Blob.class.php');

/**
 * Commit class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_FileDiff
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
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param string $fromHash source hash, can also be a diff-tree info line
	 * @param string $toHash target hash, required if $fromHash is a hash
	 * @return mixed FileDiff object
	 * @throws Exception on invalid parameters
	 */
	public function __construct($fromHash, $toHash = '')
	{
		if ($this->ParseDiffTreeLine($fromHash))
			return;

		if (!(preg_match('/^[0-9a-fA-F]{40}$/', $fromHash) && preg_match('/^[0-9]a-fA-F]{40}$/', $toHash))) {
			throw new Exception('Invalid parameters for FileDiff');
		}

		
	}

	/**
	 * ParseDiffTreeLine
	 *
	 * @access private
	 * @param string $diffTreeLine line from difftree
	 * @return boolean true if data was read from line
	 */
	private function ParseDiffTreeLine($diffTreeLine)
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
	protected function ReadDiffInfo()
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
	public function GetFromMode()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetFromModeShort()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetToMode()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetToModeShort()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetFromHash()
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
	public function GetToHash()
	{
		return $this->toHash;
	}

	/**
	 * GetStatus
	 *
	 * Gets the status of the change
	 *
	 * @access public
	 * @return string status
	 */
	public function GetStatus()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetSimilarity()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetFromFile()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

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
	public function GetToFile()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return $this->toFile;
	}

	/**
	 * GetFromFileType
	 *
	 * Gets the from file type
	 *
	 * @access public
	 * @return string from file type
	 */
	public function GetFromFileType()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return GitPHP_Blob::FileType($this->fromMode);
	}

	/**
	 * GetToFileType
	 *
	 * Gets the to file type
	 *
	 * @access public
	 * @return string to file type
	 */
	public function GetToFileType()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return GitPHP_Blob::FileType($this->toMode);
	}

	/**
	 * FileTypeChanged
	 *
	 * Tests if filetype changed
	 *
	 * @access public
	 * @return boolean true if file type changed
	 */
	public function FileTypeChanged()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->fromMode) & 0x17000) != (octdec($this->toMode) & 0x17000);
	}

	/**
	 * FileModeChanged
	 *
	 * Tests if file mode changed
	 *
	 * @access public
	 * @return boolean true if file mode changed
	 */
	public function FileModeChanged()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->fromMode) & 0777) != (octdec($this->toMode) & 0777);
	}

	/**
	 * FromFileIsRegular
	 *
	 * Tests if the from file is a regular file
	 *
	 * @access public
	 * @return boolean true if from file is regular
	 */
	public function FromFileIsRegular()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->fromMode) & 0x8000) == 0x8000;
	}

	/**
	 * ToFileIsRegular
	 *
	 * Tests if the to file is a regular file
	 *
	 * @access public
	 * @return boolean true if to file is regular
	 */
	public function ToFileIsRegular()
	{
		if (!$this->diffInfoRead)
			$this->ReadDiffInfo();

		return (octdec($this->toMode) & 0x8000) == 0x8000;
	}
}
