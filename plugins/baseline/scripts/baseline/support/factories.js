/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

const factories = {
    milestone: {
        default: {
            id: identity,
            label: "Milestone label",
        },
    },
    baseline: {
        default: {
            id: identity,
            name: "Baseline label",
            artifact_id: 9,
            snapshot_date: "2019-03-22T10:01:48+00:00",
            author_id: 3,
        },
    },
    tracker: {
        default: {
            id: identity,
            item_name: "features",
            color_name: "blue_ocean",
            fields: [
                {
                    field_id: 22,
                    label: "Description",
                },
            ],
            semantics: {
                description: {
                    field_id: 22,
                },
            },
        },
        without_semantic: {
            semantics: {},
        },
    },
    field: {
        default: {
            field_id: 1,
            label: "Description",
        },
    },
    artifact: {
        default: {
            id: 1,
            tracker: {
                id: 9,
            },
        },
    },
    baseline_artifact: {
        default: {
            id: identity,
            title: "Sprint-1",
            status: "Planned",
            tracker_id: 1,
            initial_effort: null,
            tracker_name: "Sprint",
            description:
                "Lorem ipsum dolor sit amet, consectetur adipiscing elit labore et dolore magna aliqua",
            linked_artifact_ids: [],
        },
        without_linked_artifacts: {
            linked_artifact_ids: [],
            linked_artifacts: [],
        },
    },
    user: {
        default: {
            id: identity,
            display_name: "John Doe",
            has_avatar: false,
            is_anonymous: false,
            user_url: "http://example.com/user/1",
        },
    },
    notification: {
        default: {
            text: "This is a failure notification",
            class: "danger",
        },
    },
    comparison: {
        default: {
            base_baseline_id: 1,
            compared_to_baseline_id: 2,
        },
        saved: {
            id: identity,
            name: null,
            comment: null,
            author_id: 1,
            creation_date: "2019-03-22T10:01:48+00:00",
        },
    },
    artifacts_comparison: {
        default: {
            added: associationList("baseline_artifact"),
            removed: associationList("baseline_artifact"),
            modified: () => [
                {
                    base: create("baseline_artifact"),
                    compared_to: create("baseline_artifact"),
                },
            ],
            identical_or_modified: () => [
                {
                    base: create("baseline_artifact"),
                    compared_to: create("baseline_artifact"),
                },
            ],
        },
        empty: {
            added: [],
            removed: [],
            modified: [],
            identical_or_modified: [],
        },
    },
};

let instance_index = 1;

function associationList(factory_name, ...trait_or_attributes) {
    return () => createList(factory_name, 2, ...trait_or_attributes);
}

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
