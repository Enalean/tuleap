/*
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

const identity = i => i;

const factories = {
    tracker: {
        default: {
            id: identity,
            fields: [],
            workflow: null,
            transitions: []
        }
    },
    workflow: {
        default: {
            is_used: 1,
            field_id: 9
        },
        active: {
            is_used: 1,
            field_id: 3
        },
        inactive: {
            is_used: 0
        },
        field_not_defined: {
            is_used: 0,
            field_id: null
        },
        field_defined: {
            field_id: 3
        }
    },
    field: {
        default: {
            field_id: identity,
            label: "Field label",
            type: "sb",
            bindings: { type: "static" }
        }
    },
    field_value: {
        default: {
            id: identity,
            label: "Value label"
        }
    },
    transition: {
        default: {
            id: identity,
            from_id: 1,
            to_id: 2
        }
    },
    user_group: {
        default: {
            id: identity,
            label: "Group label"
        }
    }
};

let instance_index = 0;

const evaluateAttributesAsFunction = instance =>
    Object.keys(instance).reduce((evaluatedInstance, key) => {
        const attribute_or_function = instance[key];
        if (attribute_or_function && typeof attribute_or_function == "function") {
            evaluatedInstance[key] = attribute_or_function(instance_index++);
        } else {
            evaluatedInstance[key] = attribute_or_function;
        }
        return evaluatedInstance;
    }, {});

export function create(factory_name, trait_or_attributes) {
    if (!factories.hasOwnProperty(factory_name)) {
        throw new Error(
            `No factory found with name [${factory_name}]. Did you register this new factory?`
        );
    }
    const factory = factories[factory_name];
    if (!factory.hasOwnProperty("default")) {
        throw new Error(`No default trait found for factory [${factory_name}]`);
    }
    if (trait_or_attributes && typeof trait_or_attributes === "string") {
        const trait = trait_or_attributes;
        if (!factory.hasOwnProperty(trait)) {
            throw new Error(`No trait [${trait}] found for factory [${factory_name}]`);
        }
        return evaluateAttributesAsFunction({
            ...factories[factory_name].default,
            ...factories[factory_name][trait],
            ...trait_or_attributes
        });
    }
    return evaluateAttributesAsFunction({
        ...factories[factory_name].default,
        ...trait_or_attributes
    });
}

export function createList(factory, count, attributes) {
    return Array.from(Array(count)).map(() => create(factory, attributes));
}
