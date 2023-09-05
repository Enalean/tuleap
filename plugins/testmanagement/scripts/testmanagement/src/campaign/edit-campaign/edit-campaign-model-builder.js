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

import { sortAlphabetically } from "../../ksort.js";

export { buildInitialTestsList, buildCategory, buildTest };

function buildInitialTestsList(definitions, executions) {
    const tests_collection = buildCollectionOfTests(definitions, executions);
    const tests_grouped_by_category = buildObjectOfCategories(tests_collection);

    return sortAlphabetically(tests_grouped_by_category);
}

function buildObjectOfCategories(tests) {
    return tests.reduce((accumulator, test) => {
        const category = test.definition.category;
        if (!Object.prototype.hasOwnProperty.call(accumulator, category)) {
            accumulator[category] = buildCategory(category);
        }
        accumulator[category].tests[test.definition.id] = test;
        return accumulator;
    }, {});
}

function buildCollectionOfTests(definitions, executions) {
    return definitions.map((definition) => {
        const corresponding_execution = executions.find(
            (execution) => execution.definition.id === definition.id,
        );
        if (corresponding_execution) {
            return buildTest(definition, corresponding_execution, true);
        }
        return buildTest(definition, null, false);
    });
}

function buildCategory(category) {
    return {
        tests: {},
        label: category,
    };
}

function buildTest(definition, execution, selected) {
    return {
        definition,
        execution,
        selected,
    };
}
