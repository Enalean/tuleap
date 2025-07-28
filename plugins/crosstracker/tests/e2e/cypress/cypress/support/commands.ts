/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

declare global {
    // Be consistent with Cypress declaration
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            createNewXTSWidget(new_tab_date: number): void;
        }
    }
}

Cypress.Commands.add("createNewXTSWidget", (new_tab_date: number) => {
    cy.get("[data-test=dashboard-add-button]").click();
    cy.get("[data-test=dashboard-add-input-name]").type(`tab-${new_tab_date}`);
    cy.get("[data-test=dashboard-add-button-submit]").click();

    cy.get("[data-test=dashboard-configuration-button]").click();
    cy.get("[data-test=dashboard-add-widget-button]").click();
    cy.get("[data-test=crosstrackersearch]").click();
    cy.get("[data-test=dashboard-add-widget-button-submit]").click();
});

export {};
