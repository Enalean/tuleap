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

            searchItemInLazyboxDropdown(
                query: string,
                dropdown_item_label: string
            ): Chainable<JQuery<HTMLElement>>;

            searchItemInListPickerDropdown(
                dropdown_item_label: string
            ): Chainable<JQuery<HTMLElement>>;
        }
    }
}

const LINK_SELECTOR_TRIGGER_CALLBACK_DELAY_IN_MS = 250;

Cypress.Commands.add("dragAndDrop", (source: string, destination: string, position: string) => {
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(source).trigger("mousedown", { which: 1 });
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(destination).trigger("mousemove", { position: position }).trigger("mouseup");
});

Cypress.Commands.add("searchItemInLazyboxDropdown", (query, dropdown_item_label) => {
    cy.get("[data-test=lazybox]").click();
    // Use Cypress.$ to escape from cy.within(), see https://github.com/cypress-io/cypress/issues/6666
    return cy.wrap(Cypress.$("body")).then((body) => {
        cy.wrap(body)
            .find("[data-test=lazybox-search-field]", { includeShadowDom: true })
            .type(query);
        // Lazybox waits a delay before loading items
        // eslint-disable-next-line cypress/no-unnecessary-waiting
        cy.wait(LINK_SELECTOR_TRIGGER_CALLBACK_DELAY_IN_MS);
        cy.wrap(body).find("[data-test=lazybox-loading-group-spinner]").should("not.exist");
        return cy
            .wrap(body)
            .find("[data-test=lazybox-item]")
            .contains(dropdown_item_label)
            .first()
            .parents("[data-test=lazybox-item]");
    });
});

Cypress.Commands.add("searchItemInListPickerDropdown", (dropdown_item_label) => {
    cy.get("[data-test=list-picker-selection]").click();
    // Use Cypress.$ to escape from cy.within(), see https://github.com/cypress-io/cypress/issues/6666
    return cy.wrap(Cypress.$("body")).then((body) => {
        cy.wrap(body)
            .find("[data-test-list-picker-dropdown-open]")
            .then((dropdown) =>
                cy.wrap(dropdown).find("[data-test=list-picker-item]").contains(dropdown_item_label)
            );
    });
});

export {};
