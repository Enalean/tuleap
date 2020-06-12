/*
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

// <reference path="../support/index.d.ts" />

describe("SVN", () => {
    before(() => {
        cy.login();
    });

    it("should display display repository content", () => {
        cy.getProjectId("svn-project-01")
            .as("project_id")
            .then(function () {
                cy.visit(`/plugins/svn/?group_id=${this.project_id}`);
                cy.title().should("contain", "SVN");

                // ignore rule for viewvc content
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("a", { timeout: 30000 }).contains("sample").click();
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get(".tuleap-viewvc-body")
                    .should("be.visible")
                    .within(() => {
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("a").contains("branches");
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("a").contains("tags");
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("a").contains("trunk");
                    });
            });
    });
});
