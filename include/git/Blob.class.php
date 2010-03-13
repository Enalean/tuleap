<?php
/**
 * GitPHP Blob
 *
 * Represents a single blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'FilesystemObject.class.php');
require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

/**
 * Commit class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Blob extends GitPHP_FilesystemObject
{

	/**
	 * data
	 *
	 * Stores the file data
	 *
	 * @access protected
	 */
	protected $data;

	/**
	 * dataRead
	 *
	 * Stores whether data has been read
	 *
	 * @access protected
	 */
	protected $dataRead = false;

	/**
	 * size
	 *
	 * Stores the size
	 *
	 * @access protected
	 */
	protected $size;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed blob object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetData
	 *
	 * Gets the blob data
	 *
	 * @access public
	 * @return string blob data
	 */
	public function GetData()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->data;
	}

	/**
	 * PipeData
	 *
	 * Pipes the blob data to a file
	 *
	 * @access public
	 * @param string $file file to pipe to
	 */
	public function PipeData($file)
	{
		if (empty($file))
			return;

		if (!$this->dataRead)
			$this->ReadData();

		file_put_contents($file, $this->data);
	}

	/**
	 * ReadData
	 *
	 * Reads the blob data
	 *
	 * @access private
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		$exe = new GitPHP_GitExe($this->project);

		$args = array();
		$args[] = 'blob';
		$args[] = $this->hash;

		$this->data = $exe->Execute(GIT_CAT_FILE, $args);
	}

	/**
	 * FileType
	 *
	 * Gets a file type from its octal mode
	 *
	 * @access public
	 * @static
	 * @param string $octMode octal mode
	 * @return string file type
	 */
	public static function FileType($octMode)
	{
		$mode = octdec($octMode);
		if (($mode & 0x4000) == 0x4000)
			return 'directory';
		else if (($mode & 0xA000) == 0xA000)
			return 'symlink';
		else if (($mode & 0x8000) == 0x8000)
			return 'file';
		return 'unknown';
	}

	/**
	 * GetSize
	 *
	 * Gets the blob size
	 *
	 * @access public
	 * @return integer size
	 */
	public function GetSize()
	{
		return $this->size;
	}

	/**
	 * SetSize
	 *
	 * Sets the blob size
	 *
	 * @access public
	 * @param integer $size size
	 */
	public function SetSize($size)
	{
		$this->size = $size;
	}

	/**
	 * FileMime
	 *
	 * Get the file mimetype
	 *
	 * @access public
	 * @param boolean $short true to only the type group
	 * @return string mime
	 */
	public function FileMime($short = false)
	{
		$mime = $this->FileMime_Fileinfo();

		if (empty($mime))
			$mime = $this->FileMime_File();

		if (empty($mime))
			$mime = $this->FileMime_Extension();

		if ((!empty($mime)) && $short) {
			$mime = strtok($mime, '/');
		}

		return $mime;
	}

	/** 
	 * FileMime_Fileinfo
	 *
	 * Get the file mimetype using fileinfo
	 *
	 * @access private
	 * @return string mimetype
	 */
	private function FileMime_Fileinfo()
	{
		if (!function_exists('finfo_buffer'))
			return '';

		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->data)
			return '';

		$mime = '';

		$finfo = finfo_open(FILEINFO_MIME, GitPHP_Config::GetInstance()->GetValue('magicdb', null));
		if ($finfo) {
			$mime = finfo_buffer($finfo, $this->data, FILEINFO_MIME);
			if ($mime && strpos($mime, '/')) {
				if (strpos($mime, ';')) {
					$mime = strtok($mime, ';');
				}
			}
		}
		finfo_close($finfo);

		return $mime;
	}

	/**
	 * FileMime_File
	 *
	 * Get the file mimetype using file command
	 *
	 * @access private
	 * @return string mimetype
	 */
	private function FileMime_File()
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return '';
		}

		if (!$this->dataRead)
			$this->ReadData();

		if (!$this->data)
			return '';

		$descspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w')
		);

		$proc = proc_open('file -b --mime -', $descspec, $pipes);
		if (is_resource($proc)) {
			fwrite($pipes[0], $this->data);
			fclose($pipes[0]);
			$mime = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);

			if ($mime && strpos($mime, '/')) {
				if (strpos($mime, ';')) {
					$mime = strtok($mime, ';');
				}
				return $mime;
			}
		}

		return '';
	}

	/**
	 * FileMime_Extension
	 *
	 * Get the file mimetype using the file extension
	 *
	 * @access private
	 * @return string mimetype
	 */
	private function FileMime_Extension()
	{
		$file = $this->GetName();

		if (empty($file))
			$file = $this->GetPath();

		if (empty($file))
			return '';

		$dotpos = strrpos($file, '.');
		if ($dotpos !== FALSE)
			$file = substr($file, $dotpos+1);
		switch ($file) {
			case 'jpg':
			case 'jpeg':
			case 'jpe':
				return 'image/jpeg';
				break;
			case 'gif':
				return 'image/gif';
				break;
			case 'png';
				return 'image/png';
				break;
		}

		return '';
	}

}
