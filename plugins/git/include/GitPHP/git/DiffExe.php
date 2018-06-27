<?php
/**
 * GitPHP Diff Exe
 *
 * Diff executable class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * DiffExe class
 *
 * Class to handle working with the diff executable
 */
class GitPHP_DiffExe
{
	
	/**
	 * binary
	 *
	 * Stores the binary path internally
	 *
	 * @access protected
	 */
	protected $binary;

	/**
	 * unified
	 *
	 * Stores whether diff creates unified patches
	 *
	 * @access protected
	 */
	protected $unified = true;

	/**
	 * showFunction
	 *
	 * Stores whether to show the function each change is in
	 *
	 * @access protected
	 */
	protected $showFunction = true;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$binary = GitPHP_Config::GetInstance()->GetValue('diffbin');
		if (empty($binary)) {
			$this->binary = GitPHP_DiffExe::DefaultBinary();
		} else {
			$this->binary = $binary;
		}

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
	 * GetUnified
	 *
	 * Gets whether diff is running in unified mode
	 *
	 * @access public
	 * @return mixed boolean or number of context lines
	 */
	public function GetUnified()
	{
		return $this->unified;
	}

	/**
	 * SetUnified
	 *
	 * Sets whether this diff is running in unified mode
	 *
	 * @access public
	 * @param mixed $unified true or false, or number of context lines
	 */
	public function SetUnified($unified)
	{
		$this->unified = $unified;
	}

	/**
	 * GetShowFunction
	 *
	 * Gets whether this diff is showing the function
	 *
	 * @access public
	 * @return boolean true if showing function
	 */
	public function GetShowFunction()
	{
		return $this->showFunction;
	}

	/**
	 * SetShowFunction
	 *
	 * Sets whether this diff is showing the function
	 *
	 * @access public
	 * @param boolean $show true to show
	 */
	public function SetShowFunction($show)
	{
		$this->showFunction = $show;
	}

	/**
	 * Execute
	 *
	 * Runs diff
	 *
	 * @access public
	 * @param string $fromFile source file
	 * @param string $fromName source file display name
	 * @param string $toFile destination file
	 * @param string $toName destination file display name
	 * @return string diff output
	 */
	public function Execute($fromFile = null, $fromName = null, $toFile = null, $toName = null)
	{
		if (empty($fromFile) && empty($toFile)) {
			return '';
		}

		if (empty($fromFile)) {
			$fromFile = '/dev/null';
		}

		if (empty($toFile)) {
			$toFile = '/dev/null';
		}

		$args = array();
		if ($this->unified) {
			if (is_numeric($this->unified)) {
				$args[] = '-U';
				$args[] = $this->unified;
			} else {
				$args[] = '-u';
			}

			$args[] = '-L';
			if (empty($fromName)) {
				$args[] = '"' . $fromFile . '"';
			} else {
				$args[] = '"' . $fromName . '"';
			}

			$args[] = '-L';
			if (empty($toName)) {
				$args[] = '"' . $toFile . '"';
			} else {
				$args[] = '"' . $toName . '"';
			}
		}
		if ($this->showFunction) {
			$args[] = '-p';
		}

		$args[] = $fromFile;
		$args[] = $toFile;

		return shell_exec($this->binary . ' ' . implode(' ', $args));
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
	 * Diff
	 *
	 * Convenience function to run diff with the default settings
	 * and immediately discard the object
	 *
	 * @access public
	 * @static
	 * @param string $fromFile source file
	 * @param string $fromName source file display name
	 * @param string $toFile destination file
	 * @param string $toName destination file display name
	 * @return string diff output
	 */
	public static function Diff($fromFile = null, $fromName = null, $toFile = null, $toName = null)
	{
		$obj = new GitPHP_DiffExe();
		$ret = $obj->Execute($fromFile, $fromName, $toFile, $toName);
		unset($obj);
		return $ret;
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
				// C:\Program Files (x86)\Git\bin\diff.exe
				return 'C:\\Progra~2\\Git\\bin\\diff.exe';
			} else {
				// 32 bit
				// C:\Program Files\Git\bin\diff.exe
				return 'C:\\Progra~1\\Git\\bin\\diff.exe';
			}
		} else {
			// *nix, just use PATH
			return 'diff';
		}
	}
}
