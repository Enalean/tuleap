<?php

namespace Tuleap\Git\GitPHP;

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
class GitExe
{
    const CAT_FILE     = 'cat-file';
    const DIFF_TREE    = 'diff-tree';
    const LS_TREE      = 'ls-tree';
    const REV_LIST     = 'rev-list';
    const REV_PARSE    = 'rev-parse';
    const SHOW_REF     = 'show-ref';
    const ARCHIVE      = 'archive';
    const GREP         = 'grep';
    const BLAME        = 'blame';
    const NAME_REV     = 'name-rev';
    const FOR_EACH_REF = 'for-each-ref';
    const DIFF         = 'diff';

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

	public function __construct($project = null)
	{
		$this->binary = \Git_Exec::getGitCommand();

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
		$fullCommand = $this->CreateCommand($command, $args);

		return shell_exec($fullCommand);
	}

	/**
	 * Open
	 *
	 * Opens a resource to a command
	 *
	 * @param string $command the command to execute
	 * @param array $args arguments
	 * @return resource process handle
	 */
	public function Open($command, $args, $mode = 'r')
	{
		$fullCommand = $this->CreateCommand($command, $args);

		return popen($fullCommand, $mode);
	}

	/**
	 * BuildCommand
	 *
	 * Creates a command
	 *
	 * @access protected
	 *
	 * @param string $command the command to execute
	 * @param array $args arguments
	 * @return string result of command
	 */
	protected function CreateCommand($command, $args)
	{
		$gitDir = '';
		if ($this->project) {
			$gitDir = '--git-dir=' . $this->project->GetPath();
		}
		
		return $this->binary . ' ' . $gitDir . ' ' . $command . ' ' . implode(' ', $args);
	}

	/**
	 * GetBinary
	 *
	 * Gets the binary for this executable
	 *
	 * @return string binary
	 * @access public
	 */
	public function GetBinary()
	{
		return $this->binary;
	}
}
