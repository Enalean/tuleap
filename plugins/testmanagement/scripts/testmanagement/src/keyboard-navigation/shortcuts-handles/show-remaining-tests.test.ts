/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { showRemainingTests } from "./show-remaining-tests";

describe("showNonPassedTests", () => {
    let doc: Document;
    let passed_filter: HTMLInputElement;
    let failed_filter: HTMLInputElement;
    let blocked_filter: HTMLInputElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupFiltersDocument(doc);
    });

    it("Checks all non-passed filters checkbox inputs", () => {
        showRemainingTests(doc);

        expect(failed_filter.checked).toBe(true);
        expect(blocked_filter.checked).toBe(true);
    });

    it("Unchecks passed filter checkbox input", () => {
        passed_filter.checked = true;
        showRemainingTests(doc);

        expect(passed_filter.checked).toBe(false);
    });

    function setupFiltersDocument(doc: Document): void {
        passed_filter = doc.createElement("input");
        passed_filter.type = "checkbox";
        passed_filter.setAttribute("data-shortcut-filter-passed", "");

        failed_filter = doc.createElement("input");
        failed_filter.type = "checkbox";
        failed_filter.setAttribute("data-shortcut-filter-non-passed", "");

        blocked_filter = doc.createElement("input");
        blocked_filter.type = "checkbox";
        blocked_filter.setAttribute("data-shortcut-filter-non-passed", "");

        doc.body.append(passed_filter, failed_filter, blocked_filter);
    }
});
