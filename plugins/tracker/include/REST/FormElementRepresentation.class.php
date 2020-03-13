<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsRepresentation;

class Tracker_REST_FormElementRepresentation //phpcs:ignore
{

    public const BIND_TYPE  = 'type';
    public const BIND_LIST  = 'list';

    public const BIND_ID    = 'id';
    public const BIND_LABEL = 'label';

    public const PERM_READ   = 'read';
    public const PERM_UPDATE = 'update';
    public const PERM_CREATE = 'create';

    /**
     * @var int
     */
    public $field_id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string (string|text|sb|msb|cb|date|file|int|float|tbl|art_link|perm|shared|aid|atid|lud|subby|subon|cross|burndown|computed|fieldset|column|linebreak|separator|staticrichtext)
     */
    public $type;

    /**
     * @var array | null {@type Tuleap\Tracker\REST\FieldValueRepresentation }
     */
    public $values = array();

    /**
     *
     * @var bool
     */
    public $required;

    /**
     *
     * @var bool
     */
    public $collapsed;

    /**
     * @var array
     */
    public $bindings = array();

    /**
     * @var array {@type string} One of (read, update, submit)
     */
    public $permissions = array();

    /**
     * @var PermissionsForGroupsRepresentation | null
     */
    public $permissions_for_groups;

    /**
     * @var mixed
     */
    public $default_value;

    public function build(Tracker_FormElement $form_element, $type, array $permissions, ?PermissionsForGroupsRepresentation $permissions_for_groups)
    {
        $this->field_id = JsonCast::toInt($form_element->getId());
        $this->name     = $form_element->getName();
        $this->label    = $form_element->getLabel();

        if ($form_element instanceof Tracker_FormElement_Field) {
            $this->required  = JsonCast::toBoolean($form_element->isRequired());
            $this->collapsed = false;
        } else {
            assert($form_element instanceof Tracker_FormElement_Container);
            $this->required  = false;
            $this->collapsed = (bool) $form_element->isCollapsed();
        }

        $this->default_value = $form_element->getDefaultRESTValue();
        $this->type   = $type;

        $this->values = null;
        if ($form_element->getRESTAvailableValues()) {
            $this->values = $form_element->getRESTAvailableValues();
        }

        $bindings = $form_element->getRESTBindingProperties();
        $this->bindings = array(
            self::BIND_TYPE => $bindings[Tracker_FormElement_Field_List_Bind::REST_TYPE_KEY],
            self::BIND_LIST => array_map(
                function ($binding) {
                    return array(
                        Tracker_REST_FormElementRepresentation::BIND_ID   => $binding[Tracker_FormElement_Field_List_Bind_Users::REST_BINDING_LIST_ID],
                        Tracker_REST_FormElementRepresentation::BIND_LABEL => $binding[Tracker_FormElement_Field_List_Bind_Users::REST_BINDING_LIST_LABEL]
                    );
                },
                $bindings[Tracker_FormElement_Field_List_Bind::REST_LIST_KEY]
            )
        );

        $this->permissions = array_map(
            function ($permission) {
                switch ($permission) {
                    case Tracker_FormElement::REST_PERMISSION_READ:
                        return Tracker_REST_FormElementRepresentation::PERM_READ;
                    case Tracker_FormElement::REST_PERMISSION_UPDATE:
                        return Tracker_REST_FormElementRepresentation::PERM_UPDATE;
                    case Tracker_FormElement::REST_PERMISSION_SUBMIT:
                        return Tracker_REST_FormElementRepresentation::PERM_CREATE;
                }
            },
            $permissions
        );

        $this->permissions_for_groups = $permissions_for_groups;
    }
}
