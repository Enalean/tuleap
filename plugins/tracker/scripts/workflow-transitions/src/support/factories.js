/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

const identity = (i) => i;
const identityAsString = (i) => `${i}`;

const factories = {
    tracker: {
        default: {
            id: identity,
            fields: [],
            workflow: null,
        },
    },
    workflow: {
        default: {
            is_used: 1,
            field_id: 9,
        },
        active: {
            is_used: 1,
            field_id: 3,
        },
        inactive: {
            is_used: 0,
        },
        field_not_defined: {
            is_used: 0,
            field_id: null,
        },
        field_defined: {
            field_id: 3,
        },
    },
    field: {
        default: {
            field_id: identity,
            label: "Field label",
            type: "sb",
            bindings: { type: "static" },
        },
        workflow_compliant: {
            type: "sb",
            bindings: { type: "static" },
        },
        selectbox_users: {
            type: "sb",
            bindings: { type: "users" },
        },
    },
    field_value: {
        default: {
            id: identity,
            label: "Value label",
            is_hidden: false,
        },
        hidden: {
            is_hidden: true,
        },
    },
    transition: {
        default: {
            id: identity,
            from_id: 1,
            to_id: 2,
            authorized_user_group_ids: [],
            not_empty_field_ids: [],
            is_comment_required: false,
        },
        presented: {
            updated: false,
        },
    },
    user_group: {
        default: {
            id: identity,
            label: "Group label",
        },
    },
    post_action: {
        default: {
            id: identity,
            type: "run_job",
            job_url: (index) => `http://ci.example${index}.test`,
        },
        presented: {
            unique_id: identityAsString,
            field_ids: [],
            fieldset_ids: [],
        },
    },
};

let instance_index = 0;

const evaluateAttributesAsFunction = (instance) =>
    Object.keys(instance).reduce((evaluatedInstance, key) => {
        const attribute_or_function = instance[key];
        if (attribute_or_function && typeof attribute_or_function === "function") {
            evaluatedInstance[key] = attribute_or_function(instance_index++);
        } else {
            evaluatedInstance[key] = attribute_or_function;
        }
        return evaluatedInstance;
    }, {});

function getDefaultAttributes(factory_name) {
    if (!Object.prototype.hasOwnProperty.call(factories, factory_name)) {
        throw new Error(
            `No factory found with name [${factory_name}]. Did you register this new factory?`,
        );
    }
    const factory = factories[factory_name];
    if (!Object.prototype.hasOwnProperty.call(factory, "default")) {
        throw new Error(`No default trait found for factory [${factory_name}]`);
    }
    return factories[factory_name].default;
}

function getTraitAttributes(factory_name, trait) {
    if (!Object.prototype.hasOwnProperty.call(factories[factory_name], trait)) {
        throw new Error(`No trait [${trait}] found for factory [${factory_name}]`);
    }
    return factories[factory_name][trait];
}

export function create(factory_name, ...trait_or_attributes) {
    const attributes = [getDefaultAttributes(factory_name)];
    trait_or_attributes.forEach((trait_or_attribute, index) => {
        if (index < trait_or_attributes.length - 1 || typeof trait_or_attribute === "string") {
            attributes.push(getTraitAttributes(factory_name, trait_or_attribute));
        } else {
            attributes.push(trait_or_attribute);
        }
    });

    return evaluateAttributesAsFunction(Object.assign({}, ...attributes));
}

export function createList(factory, count, trait_or_attributes) {
    return Array.from(Array(count)).map(() => create(factory, trait_or_attributes));
}
