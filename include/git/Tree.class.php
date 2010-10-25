<?php
/**
 * GitPHP Tree
 *
 * Represents a single tree
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackge Git
 */

require_once(GITPHP_GITOBJECTDIR . 'FilesystemObject.class.php');
require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

/**
 * Tree class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Tree extends GitPHP_FilesystemObject
{

	/**
	 * contents
	 *
	 * Tree contents
	 *
	 * @access protected
	 */
	protected $contents = array();

	/**
	 * contentsRead
	 *
	 * Stores whether contents were read
	 *
	 * @access protected
	 */
	protected $contentsRead = false;

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash tree hash
	 * @return mixed tree object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetContents
	 *
	 * Gets the tree contents
	 *
	 * @access public
	 * @return array array of objects for contents
	 */
	public function GetContents()
	{
		if (!$this->contentsRead)
			$this->ReadContents();

		return $this->contents;
	}

	/**
	 * ReadContents
	 *
	 * Reads the tree contents
	 *
	 * @access protected
	 */
	protected function ReadContents()
	{
		$this->contentsRead = true;

		$exe = new GitPHP_GitExe($this->project);

		$args = array();
		$args[] = '--full-name';
		if ($exe->CanShowSizeInTree())
			$args[] = '-l';
		$args[] = '-t';
		$args[] = $this->hash;
		
		$lines = explode("\n", $exe->Execute(GIT_LS_TREE, $args));

		foreach ($lines as $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})(\s+[0-9]+|\s+-)?\t(.+)$/", $line, $regs)) {
				switch($regs[2]) {
					case 'tree':
						$t = $this->GetProject()->GetTree($regs[3]);
						$t->SetMode($regs[1]);
						$path = $regs[5];
						if (!empty($this->path))
							$path = $this->path . '/' . $path;
						$t->SetPath($path);
						if ($this->commit)
							$t->SetCommit($this->commit);
						$this->contents[] = $t;
						break;
					case 'blob':
						$b = $this->GetProject()->GetBlob($regs[3]);
						$b->SetMode($regs[1]);
						$path = $regs[5];
						if (!empty($this->path))
							$path = $this->path . '/' . $path;
						$b->SetPath($path);
						$size = trim($regs[4]);
						if (!empty($size))
							$b->SetSize($regs[4]);
						if ($this->commit)
							$b->SetCommit($this->commit);
						$this->contents[] = $b;
						break;
				}
			}
		}
	}

}
