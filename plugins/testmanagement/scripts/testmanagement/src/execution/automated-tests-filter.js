/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export default AutomatedTestsFilter;

AutomatedTestsFilter.$inject = [];

function AutomatedTestsFilter() {
    return function (list, are_auto_tests_shown) {
        if (!are_auto_tests_shown) {
            return removeAutomatedTests(list);
        }

        return list;
    };

    function removeAutomatedTests(all_results) {
        return all_results.reduce((not_auto_tests, test_exec) => {
            if (
                !test_exec.definition ||
                !test_exec.definition.automated_tests ||
                test_exec.definition.automated_tests === ""
            ) {
                not_auto_tests.push(test_exec);
            }

            return not_auto_tests;
        }, []);
    }
}
