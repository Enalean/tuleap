<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Tracker_FormElement_Field_List_Value implements Tracker_IProvideJsonFormatOfMyself
{
    public const XML_ID_PREFIX = 'V';

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var bool
     */
    protected $is_hidden = false;

    public function __construct($id, $is_hidden = false)
    {
        $this->id        = $id;
        $this->is_hidden = $is_hidden;
    }

    /**
     * @psalm-mutation-free
    */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * JSon representation of the object
     *
     * @return array
     */
    public function fetchFormattedForJson()
    {
        return [
            'id'        => $this->getId(),
            'label'     => $this->getLabel(),
            'is_hidden' => (bool) $this->isHidden(),
        ];
    }

    /**
     * Specific JSon format for OpenList fields
     *
     * ProtoMultiSelect JS library used for requires a special format for JSON
     * (caption, value) so a specific method for that.
     *
     * If you are looking for a JSon representation of the current object,
     * @see Tracker_FormElement_Field_List_Value::fetchFormattedForJson
     */
    public function fetchForOpenListJson(): array
    {
        return [
            'id'         => $this->getId(),
            'value'      => $this->getJsonId(),
            'caption'    => $this->getLabel(),
            'rest_value' => $this->getAPIValue(),
        ];
    }

    abstract public function getJsonId();

    abstract public function __toString(): string;

    abstract public function getLabel(): string;

    /**
     * Get the data to bind on select <option> through a data- attribute
     */
    public function getDataset(Tracker_FormElement_Field_List $field): array
    {
        return [];
    }

    public function fetchFormatted()
    {
        return Codendi_HTMLPurifier::instance()->purify(html_entity_decode($this->getLabel()));
    }

    public function fetchFormattedForCSV()
    {
        return $this->getLabel();
    }

    public function isHidden()
    {
        return $this->is_hidden;
    }

    public function getAPIValue()
    {
        return $this->getLabel();
    }

    public function getJsonValue()
    {
        return $this->getId();
    }

    public function getXMLId()
    {
        return self::XML_ID_PREFIX . $this->getId();
    }

    public function getFullRESTValue(Tracker_FormElement_Field $field)
    {
        return [
            'label' => $this->getLabel(),
        ];
    }

    public function getRESTId()
    {
        return intval($this->getId());
    }
}
