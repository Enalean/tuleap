<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller Tag
 *
 * Controller for displaying a tag
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * Tag controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Tag extends ControllerBase // @codingStandardsIgnoreLine
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
            throw new MessageException(dgettext("gitphp", 'Project is required'), true);
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
        if (isset($this->params['jstip']) && $this->params['jstip']) {
            return 'tagtip.tpl';
        }
        return 'tag.tpl';
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
            return dgettext("gitphp", 'tag');
        }
        return 'tag';
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
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }

        if (isset($_GET['o']) && ($_GET['o'] == 'jstip')) {
            $this->params['jstip'] = true;
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
        $head = $this->project->GetHeadCommit();
        $this->tpl->assign('head', $head);

        $tag = $this->project->GetTag($this->params['hash']);

        $this->tpl->assign("tag", $tag);
    }
}
