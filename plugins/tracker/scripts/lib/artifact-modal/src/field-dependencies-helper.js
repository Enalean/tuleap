/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

export function setUpFieldDependenciesActions(tracker, callback) {
    const field_dependencies_rules = getFieldDependenciesRules(tracker);
    if (!field_dependencies_rules) {
        return;
    }
    const filtered_field_dependencies_rules = field_dependencies_rules.filter((rule, index) => {
        return field_dependencies_rules.indexOf(rule) === index;
    });

    filtered_field_dependencies_rules.forEach((rule) => {
        const target_field = tracker.fields.find(
            (field) => field.field_id === rule.target_field_id
        );

        if (typeof callback === "function" && target_field !== undefined) {
            callback(rule.source_field_id, target_field, field_dependencies_rules);
        }
    });
}

function getFieldDependenciesRules(tracker) {
    if (
        tracker === undefined ||
        tracker.workflow === undefined ||
        tracker.workflow.rules === undefined
    ) {
        return undefined;
    }
    return tracker.workflow.rules.lists;
}

export function getTargetFieldPossibleValues(
    source_value_ids,
    target_field,
    field_dependencies_rules
) {
    var possible_value_ids = getPossibleTargetValueIds(
        source_value_ids,
        target_field.field_id,
        field_dependencies_rules
    );

    return target_field.values.filter((value) => possible_value_ids.includes(value.id));
}

function getPossibleTargetValueIds(source_value_ids, target_field_id, field_dependencies_rules) {
    if (!field_dependencies_rules) {
        return [];
    }

    return field_dependencies_rules
        .filter((rule) => {
            return (
                source_value_ids.includes(rule.source_value_id) &&
                rule.target_field_id === target_field_id
            );
        })
        .map((target) => {
            return target.target_value_id;
        });
}
