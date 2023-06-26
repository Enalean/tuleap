<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
class Tracker_FormElement_Field_List_Bind_StaticValue extends Tracker_FormElement_Field_List_BindValue
{
    /**
     *
     * @var string
     */
    protected $label;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var int
     */
    protected $rank;

    public function __construct($id, $label, $description, $rank, $is_hidden)
    {
        parent::__construct($id, $is_hidden);
        $this->label       = $label;
        $this->description = $description;
        $this->rank        = $rank;
    }

    public function __toString(): string
    {
        return $this->label ? $this->label : '';
    }

    /**
     *
     * @return int
     *
     * @psalm-mutation-free
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @param int $id
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     *
     * @param string $label
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *
     * @param string $description
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     *
     * @param int $rank
     * @return \Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function setRank($rank)
    {
        $this->rank = (int) $rank;
        return $this;
    }

    public function getDataset(Tracker_FormElement_Field_List $field): array
    {
        $decorators = $field->getDecorators();
        $id         = $this->getId();

        if (! empty($decorators) && isset($decorators[$id])) {
            $purifier = Codendi_HTMLPurifier::instance();
            return [
                "data-color-value" => $purifier->purify($decorators[$id]->getCurrentColor()),
            ];
        }

        return [];
    }

    public function getFullRESTValue(Tracker_FormElement_Field $field)
    {
        $color          = null;
        $tlp_color_name = null;
        /** @var Tracker_FormElement_Field_List_BindDecorator[] $decorators */
        $decorators = $field->getDecorators();

        if (! empty($decorators) && isset($decorators[$this->getId()])) {
            $decorator = $decorators[$this->getId()];
            if ($decorator->isUsingOldPalette()) {
                $color = [
                    'r' => (int) $decorator->r,
                    'g' => (int) $decorator->g,
                    'b' => (int) $decorator->b,
                ];
            } else {
                $tlp_color_name = $decorator->tlp_color_name;
            }
        }

        return [
            'id'        => $this->getId(),
            'label'     => $this->getLabel(),
            'color'     => $color,
            'tlp_color' => $tlp_color_name,
        ];
    }
}
