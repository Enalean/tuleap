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

import { callDashboardShortcut } from "./handle-dashboard-shortcut";

describe("callCreateShortcut", () => {
    let button: HTMLElement;
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        button = doc.createElement("button");
        button.dataset.shortcutMydashboard = "true";
        doc.body.appendChild(button);
    });

    it("Goes directly to the dashboard if the user has only one", () => {
        const link = doc.createElement("a");
        link.dataset.shortcutMydashboardOption = "true";
        doc.body.appendChild(link);

        const click = jest.spyOn(link, "click");

        callDashboardShortcut(doc);

        expect(click).toHaveBeenCalled();
    });

    it("Clicks on the avatar to open the user menu when there are more than one dashboard", () => {
        const first_dashboard = doc.createElement("a");
        first_dashboard.dataset.shortcutMydashboardOption = "true";
        doc.body.appendChild(first_dashboard);

        const another_dashboard = doc.createElement("a");
        another_dashboard.dataset.shortcutMydashboardOption = "true";
        doc.body.appendChild(another_dashboard);

        const focus = jest.spyOn(first_dashboard, "focus");
        const click = jest.spyOn(button, "click");

        callDashboardShortcut(doc);

        expect(click).toHaveBeenCalled();
        expect(focus).toHaveBeenCalled();
    });
});
