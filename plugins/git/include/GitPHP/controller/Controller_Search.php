<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller Search
 *
 * Controller for running a search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * Search controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Search extends ControllerBase // @codingStandardsIgnoreLine
{
    const SEARCH_COMMIT    = 'commit';
    const SEARCH_AUTHOR    = 'author';
    const SEARCH_COMMITTER = 'committer';

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
        if (! Config::GetInstance()->GetValue('search', true)) {
            throw new MessageException(__('Search has been disabled'), true);
        }

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
        return 'search.tpl';
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
            return __('search');
        }
        return 'search';
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
        if (!isset($this->params['searchtype'])) {
            $this->params['searchtype'] = self::SEARCH_COMMIT;
        }

        if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
            throw new  MessageException(sprintf(__n('You must enter search text of at least %1$d character', 'You must enter search text of at least %1$d characters', 2), 2), true);
        }

        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        } else {
            $this->params['hash'] = 'HEAD';
        }
        if (isset($_GET['pg'])) {
            $this->params['page'] = $_GET['pg'];
        } else {
            $this->params['page'] = 0;
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
        $co = $this->project->GetCommit($this->params['hash']);
        $this->tpl->assign('commit', $co);

        $results = array();
        if ($co) {
            switch ($this->params['searchtype']) {
                case self::SEARCH_COMMIT:
                    $results = $this->project->SearchCommit($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::SEARCH_AUTHOR:
                    $results = $this->project->SearchAuthor($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;

                case self::SEARCH_COMMITTER:
                    $results = $this->project->SearchCommitter($this->params['search'], $co->GetHash(), 101, ($this->params['page'] * 100));
                    break;
                default:
                    throw new MessageException(__('Invalid search type'));
            }
        }

        if (count($results) < 1) {
            throw new MessageException(sprintf(__('No matches for "%1$s"'), $this->params['search']), false);
        }

        if (count($results) > 100) {
            $this->tpl->assign('hasmore', true);
            $results = array_slice($results, 0, 100, true);
        }
        $this->tpl->assign('results', $results);

        $this->tpl->assign('tree', $co->GetTree());

        $this->tpl->assign('page', $this->params['page']);
    }
}
