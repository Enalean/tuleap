<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\Report\Renderer;

class ImportRendererFromXmlEvent implements \Tuleap\Event\Dispatchable
{

    const NAME = 'importRendererFromXmlEvent';
    /**
     * @var array
     */
    private $row;
    /**
     * @var string
     */
    private $type;
    /**
     * @var \SimpleXMLElement
     */
    private $xml;
    /**
     * @var array
     */
    private $xml_mapping;

    public function __construct(array $row, $type, \SimpleXMLElement $xml, array $xml_mapping)
    {
        $this->row         = $row;
        $this->type        = $type;
        $this->xml         = $xml;
        $this->xml_mapping = $xml_mapping;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setRowKey($key, $value)
    {
        $this->row[$key] = $value;
    }

    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * @return array
     */
    public function getXmlMapping()
    {
        return $this->xml_mapping;
    }

    /**
     * @param string $reference
     * @return \Tracker_FormElement_Field
     */
    public function getFieldFromXMLReference($reference)
    {
        return $this->xml_mapping[$reference];
    }
}
