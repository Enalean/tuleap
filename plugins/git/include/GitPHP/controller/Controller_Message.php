<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
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
 * Message controller class
 *
 */
class Controller_Message extends ControllerBase // @codingStandardsIgnoreLine
{
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
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
        return 'tuleap/message.tpl';
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
        // This isn't a real controller
        return '';
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
        if (isset($this->params['statuscode']) && ! empty($this->params['statuscode'])) {
            $partialHeader = $this->StatusCodeHeader($this->params['statuscode']);
            if (! empty($partialHeader)) {
                if (substr(php_sapi_name(), 0, 8) == 'cgi-fcgi') {
                    /*
                     * FastCGI requires a different header
                     */
                    $this->headers[] = 'Status: ' . $partialHeader;
                } else {
                    $this->headers[] = 'HTTP/1.1 ' . $partialHeader;
                }
            }
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
        $this->tpl->assign('message', $this->params['message']);
        if (isset($this->params['error']) && ($this->params['error'])) {
            $this->tpl->assign('error', true);
        }
    }

    /**
     * StatusCodeHeader
     *
     * Gets the header for an HTTP status code
     *
     * @access private
     * @param int $code status code
     * @return string header
     */
    private function StatusCodeHeader($code) // @codingStandardsIgnoreLine
    {
        switch ($code) {
            case 500:
                return '500 Internal Server Error';
        }
    }
}
