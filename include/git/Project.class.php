<?php
/**
 * GitPHP
 * Project class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

require_once(GITPHP_INCLUDEDIR . 'defs.constants.php');

/**
 * Project class
 *
 * @package GitPHP
 */
class GitPHP_Project
{

	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * owner
	 *
	 * Stores the owner internally
	 *
	 * @access protected
	 */
	protected $owner = "";

	/**
	 * readOwner
	 *
	 * Stores whether the file owner has been read
	 *
	 * @access protected
	 */
	protected $readOwner = false;

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

	/**
	 * __construct
	 *
	 * Class constructor
	 *
	 * @access public
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	public function __construct($project)
	{
		$this->SetProject($project);
	}

	/**
	 * SetProject
	 *
	 * Attempts to set the project
	 *
	 * @access private
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	private function SetProject($project)
	{
		$projectRoot = GitPHP_Config::GetInstance()->GetValue('projectroot');
		$realProjectRoot = realpath($projectRoot);
		$fullPath = realpath($projectRoot . $project);

		if (!is_dir($fullPath)) {
			throw new Exception($project . ' is not a directory.');
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new Exception($project . ' is not a git repository.');
		}

		$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

		if (strcmp($pathPiece, $realProjectRoot) !== 0) {
			throw new Exception('Project ' . $project . ' is outside of projectroot.');
		}

		$this->project = $project;

	}

	/**
	 * GetOwner
	 *
	 * Gets the project's owner
	 *
	 * @access public
	 * @return string project owner
	 */
	public function GetOwner()
	{
		if (empty($this->owner) && !$this->readOwner) {
			$uid = fileowner($this->GetPath());
			if ($uid > 0) {
				$data = posix_getpwuid($uid);
				if (isset($data['gecos']) && !empty($data['gecos'])) {
					$this->owner = $data['gecos'];
				} elseif (isset($data['name']) && !empty($data['name'])) {
					$this->owner = $data['name'];
				}
			}
			$this->readOwner = true;
		}
	
		return $this->owner;
	}

	/**
	 * SetOwner
	 *
	 * Sets the project's owner (from an external source)
	 *
	 * @access public
	 * @param string $owner the owner
	 */
	public function SetOwner($owner)
	{
		$this->owner = $owner;
	}

	/**
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return string the project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * GetPath
	 *
	 * Gets the full project path
	 *
	 * @access public
	 * @return string project path
	 */
	public function GetPath()
	{
		return GitPHP_Config::GetInstance()->GetValue('projectroot') . $this->project;
	}

	/**
	 * GetDescription
	 *
	 * Gets the project description
	 *
	 * @access public
	 * @param $trim true to trim the description length
	 * @return string project description
	 */
	public function GetDescription($trim = false)
	{
		if (!$this->readDescription) {
			$this->description = file_get_contents($this->GetPath() . '/description');
		}

		if ((!$trim) || (strlen($this->description) < GITPHP_TRIM_LENGTH)) {
			return $this->description;
		}

		return substr($this->description, 0, GITPHP_TRIM_LENGTH) . '...';
	}

	/**
	 * GetAge
	 *
	 * Gets the project's age (last change time)
	 *
	 * @access public
	 * @return mixed date
	 */
	public function GetAge()
	{
	}

}
