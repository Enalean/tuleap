/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
            dragAndDrop(
                source_selector: string,
                source_label: string,
                destination_selector: string,
                destination_label: string,
                drop_zone_selector?: string,
            ): void;
        }
    }
}

Cypress.Commands.add(
    "dragAndDrop",
    (
        source_selector: string,
        source_label: string,
        destination_selector: string,
        destination_label: string,
        drop_zone_selector?: string,
    ) => {
        cy.getContains(source_selector, source_label).trigger("mousedown", { button: 1 });
        // eslint-disable-next-line cypress/no-force -- the drop zone might not be considered visible
        cy.getContains(destination_selector, destination_label)
            .then((destination) => {
                if (drop_zone_selector === undefined) {
                    return destination;
                }
                return cy.wrap(destination).find(drop_zone_selector);
            })
            .trigger("mousemove", { position: "top", force: true })
            .trigger("mouseup", { force: true });
    },
);

export {};
