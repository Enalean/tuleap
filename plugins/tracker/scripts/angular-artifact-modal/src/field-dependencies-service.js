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

import _ from "lodash";

export default function TuleapArtifactModalFieldDependenciesService() {
    var self = this;
    Object.assign(self, {
        getTargetFieldPossibleValues,
        setUpFieldDependenciesActions,
    });

    function setUpFieldDependenciesActions(tracker, callback) {
        var field_dependencies_rules = getFieldDependenciesRules(tracker);

        _(field_dependencies_rules)
            .unique(false, "target_field_id")
            .forEach(function (rule) {
                var target_field = tracker.fields.find(
                    (field) => field.field_id === rule.target_field_id
                );

                if (_.isFunction(callback)) {
                    callback(rule.source_field_id, target_field, field_dependencies_rules);
                }
            });
    }

    function getFieldDependenciesRules(tracker) {
        if (
            _.has(tracker, "workflow") &&
            _.has(tracker.workflow, "rules") &&
            _.has(tracker.workflow.rules, "lists")
        ) {
            return tracker.workflow.rules.lists;
        }

        return undefined;
    }

    function getTargetFieldPossibleValues(
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

    function getPossibleTargetValueIds(
        source_value_ids,
        target_field_id,
        field_dependencies_rules
    ) {
        return _(field_dependencies_rules)
            .filter(function (rule) {
                return (
                    _(source_value_ids).contains(rule.source_value_id) &&
                    rule.target_field_id === target_field_id
                );
            })
            .pluck("target_value_id")
            .value();
    }
}
