<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller Feed
 *
 * Controller for displaying a project's feed
 *
 */

/**
 * Feed controller class
 *
 */
class Controller_Feed extends ControllerBase // @codingStandardsIgnoreLine
{
    public const FEED_ITEMS       = 150;
    public const FEED_FORMAT_RSS  = 'rss';
    public const FEED_FORMAT_ATOM = 'atom';

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
     * @param bool $local true if caller wants the localized action name
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
