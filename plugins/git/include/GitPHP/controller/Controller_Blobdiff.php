<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller Blobdiff
 *
 * Controller for displaying a blobdiff
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * Blobdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Blobdiff extends Controller_DiffBase // @codingStandardsIgnoreLine
{

    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @return controller
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->project) {
            throw new MessageException(__('Project is required'), true);
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate() // @codingStandardsIgnoreLine
    {
        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return 'blobdiffplain.tpl';
        }
        return 'blobdiff.tpl';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false) // @codingStandardsIgnoreLine
    {
        if ($local) {
            return __('blobdiff');
        }
        return 'blobdiff';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() // @codingStandardsIgnoreLine
    {
        parent::ReadQuery();

        if (isset($_GET['f'])) {
            $this->params['file'] = $_GET['f'];
        }
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        }
        if (isset($_GET['hp'])) {
            $this->params['hashparent'] = $_GET['hp'];
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData() // @codingStandardsIgnoreLine
    {
        if (isset($this->params['file'])) {
            $this->tpl->assign('file', $this->params['file']);
        }

        $filediff = new FileDiff($this->project, $this->params['hashparent'], $this->params['hash']);
        $this->tpl->assign('filediff', $filediff);

        if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
            return;
        }

        if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
            $this->tpl->assign('sidebyside', true);
        }

        $commit = $this->project->GetCommit($this->params['hashbase']);
        $this->tpl->assign('commit', $commit);

        $blobparent = $this->project->GetBlob($this->params['hashparent']);
        $blobparent->SetCommit($commit);
        $blobparent->SetPath($this->params['file']);
        $this->tpl->assign('blobparent', $blobparent);

        $blob = $this->project->GetBlob($this->params['hash']);
        $blob->SetPath($this->params['file']);
        $this->tpl->assign('blob', $blob);

        $tree = $commit->GetTree();
        $this->tpl->assign('tree', $tree);
    }
}
