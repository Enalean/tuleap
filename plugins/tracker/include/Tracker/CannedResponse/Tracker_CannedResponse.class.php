<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_CannedResponse
{

    public $id;
    public $tracker;
    public $title;
    public $body;

    /**
     * Constructor
     *
     * @param int     $id      The Id
     * @param Tracker $tracker The tracker this canned response belongs to
     * @param string  $title   The title
     * @param string  $body    The body
     */
    public function __construct($id, $tracker, $title, $body)
    {
        $this->id      = $id;
        $this->tracker = $tracker;
        $this->title   = $title;
        $this->body    = $body;
    }

    /**
     * Returns the title
     *
     * @return string The title
     */
    public function getTitle()
    {
        return $this->title;
    }

     /**
     * Returns the body
     *
     * @return string The body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Transforms CannedResponse into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root the node to which the CannedResponse is attached (passed by reference)
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root)
    {
        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($root, 'title', $this->title);
        $cdata->insert($root, 'body', $this->body);
    }
}
