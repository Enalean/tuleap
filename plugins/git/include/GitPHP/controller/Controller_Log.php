<?php


namespace Tuleap\Git\GitPHP;

use GitPHP\Commit\CommitPresenter;
use GitPHP\Shortlog\ShortlogPresenterBuilder;
use UserManager;

/**
 * GitPHP Controller Log
 *
 * Controller for displaying a log
 *
 * @author Christopher Han
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * Log controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Log extends ControllerBase // @codingStandardsIgnoreLine
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
        if (\ForgeConfig::get('git_repository_bp')) {
            return 'tuleap/shortlog.tpl';
        }

        return 'shortlog.tpl';
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
            return dgettext("gitphp", 'log');
        }
        return 'log';
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
        if (isset($_GET['hb'])) {
            $this->params['hashbase'] = $_GET['hb'];
        } else {
            $this->params['hashbase'] = 'HEAD';
        }
        if (isset($_GET['pg'])) {
            $this->params['page'] = $_GET['pg'];
        } else {
            $this->params['page'] = 0;
        }
        if (isset($_GET['m'])) {
            $this->params['mark'] = $_GET['m'];
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
        $this->tpl->assign('commit', $this->project->GetCommit($this->params['hashbase']));
        $this->tpl->assign('hashbase', $this->params['hashbase']);
        $this->tpl->assign('head', $this->project->GetHeadCommit());
        $this->tpl->assign('page', $this->params['page']);

        $revlist = $this->project->GetLog($this->params['hashbase'], 101, ($this->params['page'] * 100));
        if ($revlist) {
            if (count($revlist) > 100) {
                $this->tpl->assign('hasmorerevs', true);
                $revlist = array_slice($revlist, 0, 100);
            }
            $this->tpl->assign('revlist', $revlist);

            if (\ForgeConfig::get('git_repository_bp')) {
                $builder = new ShortlogPresenterBuilder(UserManager::instance());
                $this->tpl->assign('shortlog_presenter', $builder->getShortlogPresenter($revlist));
            }
        }

        if (isset($this->params['mark'])) {
            $this->tpl->assign('mark', $this->project->GetCommit($this->params['mark']));
        }
    }
}
