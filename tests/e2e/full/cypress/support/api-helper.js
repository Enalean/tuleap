/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

Cypress.Commands.add("getFromTuleapAPI", (url) => {
    return cy.request({
        url,
        headers: {
            accept: "application/json",
            referer: Cypress.config("baseUrl"),
        },
    });
});

Cypress.Commands.add("postFromTuleapApi", (url, payload) => {
    cy.request({
        method: "POST",
        url: url,
        body: payload,
        headers: {
            accept: "application/json",
            referer: Cypress.config("baseUrl"),
        },
    });
});
