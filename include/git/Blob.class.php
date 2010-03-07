<?php
/**
 * GitPHP Blob
 *
 * Represents a single blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'GitObject.class.php');
require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

/**
 * Commit class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Blob extends GitPHP_GitObject
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
	 * @return string blob data
	 */
	public function GetData()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->data;
	}

	/**
	 * PipeData
	 *
	 * Pipes the blob data to a file
	 *
	 * @access public
	 * @param string $file file to pipe to
	 */
	public function PipeData($file)
	{
		if (empty($file))
			return;

		if (!$this->dataRead)
			$this->ReadData();

		file_put_contents($file, $this->data);
	}

	/**
	 * ReadData
	 *
	 * Reads the blob data
	 *
	 * @access private
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		$exe = new GitPHP_GitExe($this->project);

		$args = array();
		$args[] = 'blob';
		$args[] = $this->hash;

		$this->data = $exe->Execute(GIT_CAT_FILE, $args);
	}

	/**
	 * FileType
	 *
	 * Gets a file type from its octal mode
	 *
	 * @access public
	 * @static
	 * @param string $octMode octal mode
	 * @return string file type
	 */
	public static function FileType($octMode)
	{
		$mode = octdec($octMode);
		if (($mode & 0x4000) == 0x4000)
			return 'directory';
		else if (($mode & 0xA000) == 0xA000)
			return 'symlink';
		else if (($mode & 0x8000) == 0x8000)
			return 'file';
		return 'unknown';
	}

}
