<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller Feed
 *
 * Controller for displaying a project's feed
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @author Christian Weiske <cweiske@cweiske.de>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Feed controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Feed extends ControllerBase // @codingStandardsIgnoreLine
{
    const FEED_ITEMS       = 150;
    const FEED_FORMAT_RSS  = 'rss';
    const FEED_FORMAT_ATOM = 'atom';

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
        if ($this->params['format'] === self::FEED_FORMAT_RSS) {
            return 'rss.tpl';
        } elseif ($this->params['format'] === self::FEED_FORMAT_ATOM) {
            return 'atom.tpl';
        }
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
        if ($this->params['format'] === self::FEED_FORMAT_RSS) {
            if ($local) {
                return dgettext("gitphp", 'rss');
            } else {
                return 'rss';
            }
        } elseif ($this->params['format'] === self::FEED_FORMAT_ATOM) {
            if ($local) {
                return dgettext("gitphp", 'atom');
            } else {
                return 'atom';
            }
        }
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
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders() // @codingStandardsIgnoreLine
    {
        if ((!isset($this->params['format'])) || empty($this->params['format'])) {
            throw new \Exception('A feed format is required');
        }

        if ($this->params['format'] === self::FEED_FORMAT_RSS) {
            $this->headers[] = "Content-type: text/xml; charset=UTF-8";
        } elseif ($this->params['format'] === self::FEED_FORMAT_ATOM) {
            $this->headers[] = "Content-type: application/atom+xml; charset=UTF-8";
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
        $log = $this->project->GetLog('HEAD', self::FEED_ITEMS);

        $entries = count($log);

        if ($entries > 20) {
            /*
             * Don't show commits older than 48 hours,
             * but show a minimum of 20 entries
             */
            for ($i = 20; $i < $entries; ++$i) {
                if ((time() - $log[$i]->GetCommitterEpoch()) > 48*60*60) {
                    $log = array_slice($log, 0, $i);
                    break;
                }
            }
        }

        $this->tpl->assign('log', $log);
    }
}
