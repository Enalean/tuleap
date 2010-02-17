<?php
/**
 * GitPHP GitExe
 *
 * Class to wrap git executable
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Git Executable class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_GitExe
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
	 * bin
	 *
	 * Stores the binary path internally
	 *
	 * @access protected
	 */
	protected $binary;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @param string $binary path to git binary
	 * @param mixed $project project to operate on
	 * @return mixed git executable class
	 */
	public function __construct($project = null)
	{
		$this->binary = GitPHP_Config::GetInstance()->GetValue('gitbin');
		if (empty($binary)) {
			// try to pick a reasonable default
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				$this->binary = 'C:\\Progra~1\\Git\\bin\\git.exe';
			} else {
				$this->binary = 'git';
			}
		} else {
			$this->binary = $binary;
		}

		$this->SetProject($project);
	}

	/**
	 * SetProject
	 *
	 * Sets the project for this executable
	 *
	 * @param mixed $project project to set
	 */
	public function SetProject($project = null)
	{
		$this->project = $project;
	}

	/**
	 * Execute
	 *
	 * Executes a command
	 *
	 * @param string $command the command to execute
	 * @param array $args arguments
	 * @return string result of command
	 */
	public function Execute($command, $args)
	{
		$gitDir = '';
		if ($this->project) {
			$gitDir = '--git-dir=' . $this->project->GetPath();
		}
		
		$fullCommand = $this->binary . ' ' . $gitDir . ' ' . $command . ' ' . implode(' ', $args);

		return shell_exec($fullCommand);
	}

	/**
	 * GetBinary
	 *
	 * Gets the binary for this executable
	 *
	 * @return string $param
	 * @access public
	 */
	public function GetBinary()
	{
		return $this->binary;
	}

}
