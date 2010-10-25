<?php
/**
 * GitPHP Ref
 *
 * Base class for ref objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'GitObject.class.php');

/**
 * Git Ref class
 *
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_Ref extends GitPHP_GitObject
{
	
	/**
	 * refName
	 *
	 * Stores the ref name
	 *
	 * @access protected
	 */
	protected $refName;

	/**
	 * refDir
	 *
	 * Stores the ref directory
	 *
	 * @access protected
	 */
	protected $refDir;

	/**
	 * __construct
	 *
	 * Instantiates ref
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $refDir the ref directory
	 * @param string $refName the ref name
	 * @param string $refHash the ref hash
	 * @throws Exception if not a valid ref
	 * @return mixed git ref
	 */
	public function __construct($project, $refDir, $refName, $refHash = '')
	{
		$this->project = $project;
		$this->refDir = $refDir;
		$this->refName = $refName;
		if (!empty($refHash)) {
			$this->SetHash($refHash);
		}
	}

	/**
	 * GetHash
	 *
	 * Gets the hash for this ref (overrides base)
	 *
	 * @access public
	 * @return string object hash
	 */
	public function GetHash()
	{
		if (empty($this->hash))
			$this->FindHash();

		return parent::GetHash();
	}

	/**
	 * FindHash
	 *
	 * Looks up the hash for the ref
	 *
	 * @access protected
	 * @throws Exception if hash is not found
	 */
	protected function FindHash()
	{
		$exe = new GitPHP_GitExe($this->GetProject());
		$args = array();
		$args[] = '--hash';
		$args[] = '--verify';
		$args[] = $this->GetRefPath();
		$hash = trim($exe->Execute(GIT_SHOW_REF, $args));

		if (empty($hash))
			throw new Exception('Invalid ref ' . $this->GetRefPath());

		$this->SetHash($hash);
	}

	/**
	 * GetName
	 *
	 * Gets the ref name
	 *
	 * @access public
	 * @return string ref name
	 */
	public function GetName()
	{
		return $this->refName;
	}

	/**
	 * GetDirectory
	 *
	 * Gets the ref directory
	 *
	 * @access public
	 * @return string ref directory
	 */
	public function GetDirectory()
	{
		return $this->refDir;
	}

	/**
	 * GetRefPath
	 *
	 * Gets the path to the ref within the project
	 *
	 * @access public
	 * @return string ref path
	 */
	public function GetRefPath()
	{
		return 'refs/' . $this->refDir . '/' . $this->refName;
	}

	/**
	 * GetFullPath
	 *
	 * Gets the path to the ref including the project path
	 *
	 * @access public
	 * @return string full ref path
	 */
	public function GetFullPath()
	{
		return $this->GetProject()->GetPath() . '/' . $this->GetRefPath();
	}

	/**
	 * __sleep
	 *
	 * Called to prepare the object for serialization
	 *
	 * @access public
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		$properties = array('refName', 'refDir');

		return array_merge($properties, parent::__sleep());
	}

}
