<?php
/**
 * GitPHP Filesystem Object
 *
 * Base class for all git objects that represent
 * a filesystem item
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Git Filesystem object class
 *
 * @abstract
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_FilesystemObject extends GitPHP_GitObject
{

	/**
	 * name
	 *
	 * Stores the object name
	 *
	 * @access protected
	 */
	protected $name = '';

	/**
	 * mode
	 *
	 * Stores the object mode
	 *
	 * @access protected
	 */
	protected $mode;

	/**
	 * parent
	 *
	 * Stores the object parent
	 *
	 * @access protected
	 */
	protected $parent;

	/**
	 * commit
	 *
	 * Stores the commit this object belongs to
	 *
	 * @access protected
	 */
	protected $commit;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git filesystem object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetName
	 *
	 * Gets the object name
	 *
	 * @access public
	 * @return string name
	 */
	public function GetName()
	{
		return $this->name;
	}

	/**
	 * GetPath
	 *
	 * Gets the full path
	 *
	 * @access public
	 * @return string path
	 */
	public function GetPath()
	{
		if ($this->commit) {
			$name = $this->commit->HashToPath($this->hash);
			if (!empty($name)) {
				return $name;
			}
		}

		$name = $this->name;

		if ($this->parent) {
			$parentname = $this->parent->GetPath();
			if (!empty($parentname)) {
				$name = $parentname . '/' . $this->name;
			}
		}

		return $name;
	}

	/**
	 * SetName
	 *
	 * Sets the object name
	 *
	 * @access public
	 * @param string $name tree name
	 */
	public function SetName($name)
	{
		$this->name = $name;
	}

	/**
	 * GetMode
	 *
	 * Gets the object mode
	 *
	 * @access public
	 * @return string mode
	 */
	public function GetMode()
	{
		return $this->mode;
	}

	/**
	 * GetModeString
	 *
	 * Gets the mode as a readable string
	 *
	 * @access public
	 * @return string mode string
	 */
	public function GetModeString()
	{
		if (empty($this->mode))
			return '';

		$mode = octdec($this->mode);

		/*
		 * Git doesn't store full ugo modes,
		 * it only knows if something is a directory,
		 * symlink, or an executable or non-executable file
		 */
		if (($mode & 0x4000) == 0x4000)
			return 'drwxr-xr-x';
		else if (($mode & 0xA000) == 0xA000)
			return 'lrwxrwxrwx';
		else if (($mode & 0x8000) == 0x8000) {
			if (($mode & 0x0040) == 0x0040)
				return '-rwxr-xr-x';
			else
				return '-rw-r--r--';
		}
		return '----------';
	}

	/**
	 * SetMode
	 *
	 * Sets the object mode
	 *
	 * @access public
	 * @param string $mode tree mode
	 */
	public function SetMode($mode)
	{
		$this->mode = $mode;
	}

	/**
	 * GetParent
	 *
	 * Gets the parent
	 *
	 * @access public
	 * @return mixed parent object
	 */
	public function GetParent()
	{
		return $this->parent;
	}

	/**
	 * SetParent
	 *
	 * Sets the parent
	 *
	 * @access public
	 * @param mixed $parent parent object
	 */
	public function SetParent($parent)
	{
		$this->parent = $parent;
	}

	/**
	 * GetCommit
	 *
	 * Gets the commit this object belongs to
	 *
	 * @access public
	 * @return mixed commit object
	 */
	public function GetCommit()
	{
		return $this->commit;
	}

	/**
	 * SetCommit
	 *
	 * Sets the commit this object belongs to
	 *
	 * @access public
	 * @param mixed $commit commit object
	 */
	public function SetCommit($commit)
	{
		$this->commit = $commit;
	}

}
