<?php
/**
 * Copyright (c) Enalean, 2016-Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HudsonBuild
{
    protected $hudson_build_url;

    /**
     * @var SimpleXMLElement
     */
    protected $dom_build;
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;

    /**
     * Construct an Hudson build from a build URL
     */
    public function __construct(
        string $hudson_build_url,
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        ?SimpleXMLElement $dom_build = null,
    ) {
        $parsed_url = parse_url($hudson_build_url);

        if (! $parsed_url || ! array_key_exists('scheme', $parsed_url)) {
            throw new HudsonJobURLMalformedException(sprintf(dgettext('tuleap-hudson', 'Wrong Job URL: %1$s'), $hudson_build_url));
        }

        $this->hudson_build_url = $hudson_build_url . "/api/xml";
        $this->http_client      = $http_client;
        $this->request_factory  = $request_factory;

        if ($dom_build !== null) {
            $this->dom_build = $dom_build;
        } else {
            $this->dom_build = $this->_getXMLObject($this->hudson_build_url);
        }
    }

    protected function _getXMLObject(string $hudson_build_url)
    {
        $response = $this->http_client->sendRequest(
            $this->request_factory->createRequest('GET', $hudson_build_url)
        );
        if ($response->getStatusCode() !== 200) {
            throw new HudsonJobURLFileNotFoundException(sprintf(dgettext('tuleap-hudson', 'File not found at URL: %1$s'), $hudson_build_url));
        }

        $xmlobj = simplexml_load_string($response->getBody()->getContents());
        if ($xmlobj !== false) {
            return $xmlobj;
        }
        throw new HudsonJobURLFileException(sprintf(dgettext('tuleap-hudson', 'Unable to read file at URL: %1$s'), $hudson_build_url));
    }

    public function getDom()
    {
        return $this->dom_build;
    }

    public function getBuildStyle()
    {
        return $this->dom_build->getName();
    }

    public function isBuilding()
    {
        return ($this->dom_build->building == "true");
    }

    public function getUrl()
    {
        return (string) $this->dom_build->url;
    }

    public function getResult()
    {
        return (string) $this->dom_build->result;
    }

    public function getNumber()
    {
        return (int) $this->dom_build->number;
    }

    public function getDuration()
    {
        return (int) $this->dom_build->duration;
    }

    public function getTimestamp()
    {
        return (int) $this->dom_build->timestamp;
    }

    public function getBuildTime()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), substr($this->getTimestamp(), 0, -3));
    }

    public function getStatusIcon()
    {
        $color = 'red';
        if ($this->getResult() == 'SUCCESS') {
            $color = 'blue';
        } elseif ($this->getResult() == 'UNSTABLE') {
            $color = 'yellow';
        }
        return hudsonPlugin::ICONS_PATH . 'status_' . $color . '.png';
    }
}
