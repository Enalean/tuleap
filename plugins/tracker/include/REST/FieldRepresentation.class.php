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

class Tracker_REST_FieldRepresentation {

    const BIND_TYPE  = 'type';
    const BIND_LIST  = 'list';

    const BIND_ID    = 'id';
    const BIND_LABEL = 'label';

    const PERM_READ   = 'read';
    const PERM_UPDATE = 'update';
    const PERM_SUBMIT = 'submit';

    /** @var int */
    public $field_id;

    /** @var string */
    public $label;

    /** @var string */
    public $name;

    /** @var string (string|text|sb|msb|cb|date|file|int|float|tbl|art_link|perm|shared|aid|atid|lud|subby|subon|cross|burndown|computed) */
    public $type;

    /** @var array {@type Tracker_REST_FieldValueRepresentation }*/
    public $values      = array();

    /** @var array */
    public $bindings    = array();

    /** @var array {@type string} One of (read, update, submit) */
    public $permissions = array();

    public function __construct(Tracker_FormElement_Field $field, $type, array $permissions) {
        $this->field_id    = $field->getId();
        $this->name  = $field->getName();
        $this->label = $field->getLabel();
        $this->type  = $type;

        if ($field->getSoapAvailableValues()) {
            foreach ($field->getSoapAvailableValues() as $value) {
                $this->values[] = new Tracker_REST_FieldValueRepresentation($value);
            }
        }

        $bindings = $field->getSoapBindingProperties();
        $this->bindings = array(
            self::BIND_TYPE => $bindings[Tracker_FormElement_Field_List_Bind::SOAP_TYPE_KEY],
            self::BIND_LIST => array_map(
                function ($binding) {
                    return array(
                        Tracker_REST_FieldRepresentation::BIND_ID   => $binding[Tracker_FormElement_Field_List_Bind_Users::SOAP_BINDING_LIST_ID],
                        Tracker_REST_FieldRepresentation::BIND_LABEL=> $binding[Tracker_FormElement_Field_List_Bind_Users::SOAP_BINDING_LIST_LABEL]
                    );
                },
                $bindings[Tracker_FormElement_Field_List_Bind::SOAP_LIST_KEY]
            )
        );

        $this->permissions = array_map(
            function ($permission) {
                switch ($permission) {
                    case Tracker_FormElement::SOAP_PERMISSION_READ:
                        return Tracker_REST_FieldRepresentation::PERM_READ;
                    case Tracker_FormElement::SOAP_PERMISSION_UPDATE:
                        return Tracker_REST_FieldRepresentation::PERM_UPDATE;
                    case Tracker_FormElement::SOAP_PERMISSION_SUBMIT:
                        return Tracker_REST_FieldRepresentation::PERM_SUBMIT;
                }
            },
            $permissions
        );
    }
}
