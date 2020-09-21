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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare global {
    // Be consistent with Cypress declaration
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            dragAndDrop(source: string, destination: string, position: string): void;
        }
    }
}

Cypress.Commands.add("dragAndDrop", (source: string, destination: string, position: string) => {
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(source).trigger("mousedown", { which: 1 });
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(destination).trigger("mousemove", { position: position }).trigger("mouseup");
});

export {};
