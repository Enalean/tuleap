<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_REST_FieldRepresentation {

    const BIND_TYPE  = 'type';
    const BIND_LIST  = 'list';

    const BIND_ID    = 'id';
    const BIND_LABEL = 'label';

    const PERM_READ   = 'read';
    const PERM_UPDATE = 'update';
    const PERM_CREATE = 'create';

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
     * @var array {@type Tuleap\Tracker\REST\FieldValueRepresentation }
     */
    public $values = array();

    /**
     *
     * @var boolean
     */
    public $required;

    /**
     *
     * @var boolean
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

    public function build(Tracker_FormElement $field, $type, array $permissions) {
        $this->field_id = JsonCast::toInt($field->getId());
        $this->name     = $field->getName();
        $this->label    = $field->getLabel();

        if ($field instanceof Tracker_FormElement_Field) {
            $this->required      = JsonCast::toBoolean($field->isRequired());
            $this->collapsed     = false;

        } else {
            $this->required      = false;
            $this->collapsed     = (bool) $field->isCollapsed();
        }

        $this->default_value = $field->getDefaultRESTValue();
        $this->type   = $type;

        $this->values = null;
        if ($field->getRESTAvailableValues()) {
            $this->values = $field->getRESTAvailableValues();
        }

        $bindings = $field->getRESTBindingProperties();
        $this->bindings = array(
            self::BIND_TYPE => $bindings[Tracker_FormElement_Field_List_Bind::REST_TYPE_KEY],
            self::BIND_LIST => array_map(
                function ($binding) {
                    return array(
                        Tracker_REST_FieldRepresentation::BIND_ID   => $binding[Tracker_FormElement_Field_List_Bind_Users::REST_BINDING_LIST_ID],
                        Tracker_REST_FieldRepresentation::BIND_LABEL=> $binding[Tracker_FormElement_Field_List_Bind_Users::REST_BINDING_LIST_LABEL]
                    );
                },
                $bindings[Tracker_FormElement_Field_List_Bind::REST_LIST_KEY]
            )
        );

        $this->permissions = array_map(
            function ($permission) {
                switch ($permission) {
                    case Tracker_FormElement::REST_PERMISSION_READ:
                        return Tracker_REST_FieldRepresentation::PERM_READ;
                    case Tracker_FormElement::REST_PERMISSION_UPDATE:
                        return Tracker_REST_FieldRepresentation::PERM_UPDATE;
                    case Tracker_FormElement::REST_PERMISSION_SUBMIT:
                        return Tracker_REST_FieldRepresentation::PERM_CREATE;
                }
            },
            $permissions
        );
    }
}
