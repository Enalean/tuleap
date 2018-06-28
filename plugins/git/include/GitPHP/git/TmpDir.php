<?php
/**
 * GitPHP Tmp Dir
 *
 * Temporary directory class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * TmpDir class
 *
 * Class to handle managing files in a temporary directory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TmpDir
{
	
	/**
	 * instance
	 *
	 * Stores the singleton instance
	 *
	 * @access protected
	 * @static
	 */
	protected static $instance;

	/**
	 * dir
	 *
	 * Stores the directory
	 *
	 * @access protected
	 */
	protected $dir = null;

	/**
	 * files
	 *
	 * Stores a list of files in this tmpdir
	 *
	 * @access protected
	 */
	protected $files = array();

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of tmpdir class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_TmpDir();
		}
		return self::$instance;
	}

	/**
	 * SystemTmpDir
	 *
	 * Gets the system defined temporary directory
	 *
	 * @access public
	 * @static
	 * @return string temp dir
	 */
	public static function SystemTmpDir()
	{
		$tmpdir = '';

		if (function_exists('sys_get_temp_dir')) {
			$tmpdir = sys_get_temp_dir();
		}

		if (empty($tmpdir)) {
			$tmpdir = getenv('TMP');
		}

		if (empty($tmpdir)) {
			$tmpdir = getenv('TEMP');
		}

		if (empty($tmpdir)) {
			$tmpdir = getenv('TMPDIR');
		}

		if (empty($tmpdir)) {
			$tmpfile = tempnam(__FILE__, '');
			if (file_exists($tmpfile)) {
				unlink($tmpfile);
				$tmpdir = dirname($temp);
			}
		}

		if (empty($tmpdir)) {
			// ultimate default - should never get this far
			if (GitPHP_Util::IsWindows()) {
				$tmpdir = 'C:\\Windows\\Temp';
			} else {
				$tmpdir = '/tmp';
			}
		}

		return GitPHP_Util::AddSlash(realpath($tmpdir));
	}

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->dir = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('gittmp'));

		if (empty($this->dir)) {
			$this->dir = GitPHP_TmpDir::SystemTmpDir();
		}

		if (empty($this->dir)) {
			throw new Exception(__('No tmpdir defined'));
		}

		if (file_exists($this->dir)) {
			if (is_dir($this->dir)) {
				if (!is_writeable($this->dir)) {
					throw new Exception(sprintf(__('Specified tmpdir %1$s is not writable'), $this->dir));
				}
			} else {
				throw new Exception(sprintf(__('Specified tmpdir %1$s is not a directory'), $this->dir));
			}
		} else if (!mkdir($this->dir, 0700)) {
			throw new Exception(sprintf(__('Could not create tmpdir %1$s'), $this->dir));
		}
	}

	/**
	 * __destruct
	 *
	 * Destructor
	 *
	 * @access public
	 */
	public function __destruct()
	{
		$this->Cleanup();
	}

	/**
	 * GetDir
	 *
	 * Gets the temp dir
	 *
	 * @return string temp dir
	 */
	public function GetDir()
	{
		return $this->dir;
	}

	/**
	 * SetDir
	 *
	 * Sets the temp dir
	 *
	 * @param string $dir new temp dir
	 */
	public function SetDir($dir)
	{
		$this->Cleanup();
		$this->dir = $dir;
	}

	/**
	 * AddFile
	 *
	 * Adds a file to the temp dir
	 *
	 * @param string $filename file name
	 * @param string $content file content
	 */
	public function AddFile($filename, $content)
	{
		if (empty($filename)) {
			return;
		}

		file_put_contents($this->dir . $filename, $content);

		if (!in_array($filename, $this->files)) {
			$this->files[] = $filename;
		}
	}

	/**
	 * RemoveFile
	 *
	 * Removes a file from the temp dir
	 *
	 * @param string $filename file name
	 */
	public function RemoveFile($filename)
	{
		if (empty($filename)) {
			return;
		}

		unlink($this->dir . $filename);

		$idx = array_search($filename, $this->files);
		if ($idx !== false) {
			unset($this->files[$idx]);
		}
	}

	/**
	 * Cleanup
	 *
	 * Cleans up any temporary files
	 */
	public function Cleanup()
	{
		if (!empty($this->dir) && (count($this->files) > 0)) {
			foreach ($this->files as $file) {
				$this->RemoveFile($file);
			}
		}
	}
}
