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
 * Constants for git commands
 */
define('GIT_CAT_FILE','cat-file');
define('GIT_DIFF_TREE','diff-tree');
define('GIT_LS_TREE','ls-tree');
define('GIT_REV_LIST','rev-list');
define('GIT_REV_PARSE','rev-parse');
define('GIT_SHOW_REF','show-ref');
define('GIT_ARCHIVE','archive');
define('GIT_GREP','grep');
define('GIT_BLAME','blame');
define('GIT_NAME_REV','name-rev');
define('GIT_FOR_EACH_REF','for-each-ref');
define('GIT_CONFIG','config');
define('GIT_DIFF','diff');

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
		$binary = GitPHP_Config::GetInstance()->GetValue('gitbin');
		if (empty($binary)) {
			$this->binary = GitPHP_GitExe::DefaultBinary();
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
		$fullCommand = $this->CreateCommand($command, $args);

		GitPHP_Log::GetInstance()->Log('Begin executing "' . $fullCommand . '"');

		$ret = shell_exec($fullCommand);

		GitPHP_Log::GetInstance()->Log('Finish executing "' . $fullCommand . '"' .
			"\nwith result: " . $ret);

		return $ret;
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

	/**
	 * GetVersion
	 *
	 * Gets the version of the git binary
	 *
	 * @return string version
	 * @access public
	 */
	public function GetVersion()
	{
		$versionCommand = $this->binary . ' --version';
		$ret = trim(shell_exec($versionCommand));
		if (preg_match('/^git version ([0-9\.]+)$/i', $ret, $regs)) {
			return $regs[1];
		}
		return '';
	}

	/**
	 * CanSkip
	 *
	 * Tests if this version of git can skip through the revision list
	 *
	 * @access public
	 * @return boolean true if we can skip
	 */
	public function CanSkip()
	{
		$version = $this->GetVersion();
		if (!empty($version)) {
			$splitver = explode('.', $version);

			/* Skip only appears in git >= 1.5.0 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * CanShowSizeInTree
	 *
	 * Tests if this version of git can show the size of a blob when listing a tree
	 *
	 * @access public
	 * @return true if we can show sizes
	 */
	public function CanShowSizeInTree()
	{
		$version = $this->GetVersion();
		if (!empty($version)) {
			$splitver = explode('.', $version);

			/*
			 * ls-tree -l only appears in git 1.5.3
			 * (technically 1.5.3-rc0 but i'm not getting that fancy)
			 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5)) || (($splitver[0] == 1) && ($splitver[1] == 5) && ($splitver[2] < 3))) {
				return false;
			}
		}

		return true;

	}

	/**
	 * CanIgnoreRegexpCase
	 *
	 * Tests if this version of git has the regexp tuning option to ignore regexp case
	 *
	 * @access public
	 * @return true if we can ignore regexp case
	 */
	public function CanIgnoreRegexpCase()
	{
		$version = $this->GetVersion();
		if (!empty($version)) {
			$splitver = explode('.', $version);

			/*
			 * regexp-ignore-case only appears in git 1.5.3
			 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5)) || (($splitver[0] == 1) && ($splitver[1] == 5) && ($splitver[2] < 3))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Valid
	 *
	 * Tests if this executable is valid
	 *
	 * @access public
	 * @return boolean true if valid
	 */
	public function Valid()
	{
		if (empty($this->binary))
			return false;

		$code = 0;
		$out = exec($this->binary . ' --version', $tmp, $code);

		return $code == 0;
	}

	/**
	 * DefaultBinary
	 *
	 * Gets the default binary for the platform
	 *
	 * @access public
	 * @static
	 * @return string binary
	 */
	public static function DefaultBinary()
	{
		if (GitPHP_Util::IsWindows()) {
			// windows

			if (GitPHP_Util::Is64Bit()) {
				// match x86_64 and x64 (64 bit)
				// C:\Program Files (x86)\Git\bin\git.exe
				return 'C:\\Progra~2\\Git\\bin\\git.exe';
			} else {
				// 32 bit
				// C:\Program Files\Git\bin\git.exe
				return 'C:\\Progra~1\\Git\\bin\\git.exe';
			}
		} else {
			// *nix, just use PATH
			return 'git';
		}
	}

}
