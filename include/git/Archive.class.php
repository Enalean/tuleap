<?php
/**
 * GitPHP Archive
 *
 * Represents an archive (snapshot)
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Commit.class.php');

define('GITPHP_COMPRESS_TAR', 'tar');
define('GITPHP_COMPRESS_BZ2', 'tbz2');
define('GITPHP_COMPRESS_GZ', 'tgz');
define('GITPHP_COMPRESS_ZIP', 'zip');

/**
 * Archive class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Archive
{
	/**
	 * gitObject
	 *
	 * Stores the object for this archive internally
	 *
	 * @access protected
	 */
	protected $gitObject = null;

	/**
	 * project
	 *
	 * Stores the project for this archive internally
	 *
	 * @access protected
	 */
	protected $project = null;

	/**
	 * format
	 *
	 * Stores the archive format internally
	 *
	 * @access protected
	 */
	protected $format;

	/**
	 * fileName
	 *
	 * Stores the archive filename internally
	 *
	 * @access protected
	 */
	protected $fileName = '';

	/**
	 * path
	 *
	 * Stores the archive path internally
	 *
	 * @access protected
	 */
	protected $path = '';

	/**
	 * prefix
	 *
	 * Stores the archive prefix internally
	 *
	 * @access protected
	 */
	protected $prefix = '';

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $gitObject the object
	 * @param integer $format the format for the archive
	 * @return mixed git archive
	 */
	public function __construct($project, $gitObject, $format = GITPHP_FORMAT_ZIP, $path = '', $prefix = '')
	{
		$this->SetProject($project);
		$this->SetObject($gitObject);
		$this->SetFormat($format);
		$this->SetPath($path);
		$this->SetPrefix($prefix);
	}

	/**
	 * GetFormat
	 *
	 * Gets the archive format
	 *
	 * @access public
	 * @return integer archive format
	 */
	public function GetFormat()
	{
		return $this->format;
	}

	/**
	 * SetFormat
	 *
	 * Sets the archive format
	 *
	 * @access public
	 * @param integer $format archive format
	 */
	public function SetFormat($format)
	{
		if ((($format == GITPHP_COMPRESS_BZ2) && (!function_exists('bzcompress'))) ||
		    (($format == GITPHP_COMPRESS_GZ) && (!function_exists('gzencode')))) {
		    /*
		     * Trying to set a format but doesn't have the appropriate
		     * compression function, fall back to tar
		     */
		    $format = GITPHP_COMPRESS_TAR;
		}

		$this->format = $format;
	}

	/**
	 * GetObject
	 *
	 * Gets the object for this archive
	 *
	 * @access public
	 * @return mixed the git object
	 */
	public function GetObject()
	{
		return $this->gitObject;
	}

	/**
	 * SetObject
	 *
	 * Sets the object for this archive
	 *
	 * @access public
	 * @param mixed $object the git object
	 */
	public function SetObject($object)
	{
		// Archive only works for commits and trees
		if (($object != null) && (!(($object instanceof GitPHP_Commit) || ($object instanceof GitPHP_Tree)))) {
			throw new Exception('Invalid source object for archive');
		}

		$this->gitObject = $object;
	}

	/**
	 * GetProject
	 *
	 * Gets the project for this archive
	 *
	 * @access public
	 * @return mixed the project
	 */
	public function GetProject()
	{
		if ($this->project)
			return $this->project;

		if ($this->gitObject)
			return $this->gitObject->GetProject();

		return null;
	}

	/**
	 * SetProject
	 *
	 * Sets the project for this archive
	 *
	 * @access public
	 * @param mixed $project the project
	 */
	public function SetProject($project)
	{
		$this->project = $project;
	}

	/**
	 * GetExtension
	 *
	 * Gets the extension to use for this archive
	 *
	 * @access public
	 * @return string extension for the archive
	 */
	public function GetExtension()
	{
		return GitPHP_Archive::FormatToExtension($this->format);
	}

	/**
	 * GetFilename
	 *
	 * Gets the filename for this archive
	 *
	 * @access public
	 * @return string filename
	 */
	public function GetFilename()
	{
		if (!empty($this->fileName)) {
			return $this->fileName;
		}

		$fname = $this->GetProject()->GetSlug();

		if (!empty($this->path)) {
			$fname .= '-' . GitPHP_Util::MakeSlug($this->path);
		}

		$fname .= '.' . $this->GetExtension();

		return $fname;
	}

	/**
	 * SetFilename
	 *
	 * Sets the filename for this archive
	 *
	 * @access public
	 * @param string $name filename
	 */
	public function SetFilename($name = '')
	{
		$this->fileName = $name;
	}

	/**
	 * GetPath
	 *
	 * Gets the path to restrict this archive to
	 *
	 * @access public
	 * @return string path
	 */
	public function GetPath()
	{
		return $this->path;
	}

	/**
	 * SetPath
	 *
	 * Sets the path to restrict this archive to
	 *
	 * @access public
	 * @param string $path path to restrict
	 */
	public function SetPath($path = '')
	{
		$this->path = $path;
	}

	/**
	 * GetPrefix
	 *
	 * Gets the directory prefix to use for files in this archive
	 *
	 * @access public
	 * @return string prefix
	 */
	public function GetPrefix()
	{
		if (!empty($this->prefix)) {
			return $this->prefix;
		}

		$pfx = $this->GetProject()->GetSlug() . '/';

		if (!empty($this->path))
			$pfx .= $this->path . '/';

		return $pfx;
	}

	/**
	 * SetPrefix
	 *
	 * Sets the directory prefix to use for files in this archive
	 *
	 * @access public
	 * @param string $prefix prefix to use
	 */
	public function SetPrefix($prefix = '')
	{
		if (empty($prefix)) {
			$this->prefix = $prefix;
			return;
		}

		if (substr($prefix, -1) != '/') {
			$prefix .= '/';
		}

		$this->prefix = $prefix;
	}

	/**
	 * GetHeaders
	 *
	 * Gets the headers to send to the browser for this file
	 *
	 * @access public
	 * @return array header strings
	 */
	public function GetHeaders()
	{
		$headers = array();

		switch ($this->format) {
			case GITPHP_COMPRESS_TAR:
				$headers[] = 'Content-Type: application/x-tar';
				break;
			case GITPHP_COMPRESS_BZ2:
				$headers[] = 'Content-Type: application/x-bzip2';
				break;
			case GITPHP_COMPRESS_GZ:
				$headers[] = 'Content-Type: application/x-gzip';
				break;
			case GITPHP_COMPRESS_ZIP:
				$headers[] = 'Content-Type: application/x-zip';
				break;
			default:
				throw new Exception('Unknown compression type');
		}

		if (count($headers) > 0) {
			$headers[] = 'Content-Disposition: attachment; filename=' . $this->GetFilename();
		}

		return $headers;
	}

	/**
	 * GetData
	 *
	 * Gets the archive data
	 *
	 * @access public
	 * @return string archive data
	 */
	public function GetData()
	{
		if (!$this->gitObject)
		{
			throw new Exception('Invalid object for archive');
		}

		$exe = new GitPHP_GitExe($this->GetProject());

		$args = array();

		switch ($this->format) {
			case GITPHP_COMPRESS_ZIP:
				$args[] = '--format=zip';
				break;
			case GITPHP_COMPRESS_TAR:
			case GITPHP_COMPRESS_BZ2:
			case GITPHP_COMPRESS_GZ:
				$args[] = '--format=tar';
				break;
		}

		$args[] = '--prefix=' . $this->GetPrefix();
		$args[] = $this->gitObject->GetHash();

		$data = $exe->Execute(GIT_ARCHIVE, $args);
		unset($exe);

		switch ($this->format) {
			case GITPHP_COMPRESS_BZ2:
				$data = bzcompress($data, GitPHP_Config::GetInstance()->GetValue('compresslevel', 4));
				break;
			case GITPHP_COMPRESS_GZ:
				$data = gzencode($data, GitPHP_Config::GetInstance()->GetValue('compresslevel', -1));
				break;
		}

		return $data;
	}

	/**
	 * FormatToExtension
	 *
	 * Gets the extension to use for a particular format
	 *
	 * @access public
	 * @static
	 * @param string $format format to get extension for
	 * @return string file extension
	 */
	public static function FormatToExtension($format)
	{
		switch ($format) {
			case GITPHP_COMPRESS_TAR:
				return 'tar';
				break;
			case GITPHP_COMPRESS_BZ2:
				return 'tar.bz2';
				break;
			case GITPHP_COMPRESS_GZ:
				return 'tar.gz';
				break;
			case GITPHP_COMPRESS_ZIP:
				return 'zip';
				break;
		}
	}

	/**
	 * SupportedFormats
	 *
	 * Gets the supported formats for the archiver
	 *
	 * @access public
	 * @static
	 * @return array array of formats mapped to extensions
	 */
	public static function SupportedFormats()
	{
		$formats = array();

		$formats[GITPHP_COMPRESS_TAR] = GitPHP_Archive::FormatToExtension(GITPHP_COMPRESS_TAR);
		
		// TODO check for git > 1.4.3 for zip
		$formats[GITPHP_COMPRESS_ZIP] = GitPHP_Archive::FormatToExtension(GITPHP_COMPRESS_ZIP);

		if (function_exists('bzcompress'))
			$formats[GITPHP_COMPRESS_BZ2] = GitPHP_Archive::FormatToExtension(GITPHP_COMPRESS_BZ2);

		if (function_exists('gzencode'))
			$formats[GITPHP_COMPRESS_GZ] = GitPHP_Archive::FormatToExtension(GITPHP_COMPRESS_GZ);

		return $formats;
	}

}
