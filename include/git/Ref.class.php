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

require_once(GITPHP_INCLUDEDIR . 'git/GitObject.class.php');

/**
 * Git Ref class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Ref extends GitPHP_GitObject
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
	 * @throws Exception if not a valid ref
	 * @return mixed git ref
	 */
	public function __construct($project, $refDir, $refName)
	{
		$this->project = $project;
		$this->refDir = $refDir;
		$this->refName = $refName;
		$this->FindHash();
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
		/* Regular ref */
		if (is_file($this->GetFullPath())) {
			$hash = file_get_contents($this->GetFullPath());
			try {
				$this->SetHash($hash);
				return;
			} catch (Exception $e) {
			}
		}

		/* Packed ref */
		if (is_file($this->project->GetPath() . '/packed-refs')) {
			$packedRefs = explode("\n", file_get_contents($this->project->GetPath() . '/packed-refs'));
			foreach ($packedRefs as $refLine) {
				if (preg_match('/^([0-9a-f]{40}) (.*)$/i', trim($refLine), $regs)) {
					if (strcmp($regs[2], $this->GetRefPath()) === 0) {
						try {
							$this->SetHash($regs[1]);
							return;
						} catch (Exception $e) {
						}
					}
				}
			}
		}

		/* Didn't find anything */
		throw new Exception('Invalid ref ' . $this->GetRefPath());
	}

	/**
	 * GetName()
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
		return $this->project->GetPath() . '/' . $this->GetRefPath();
	}

}
