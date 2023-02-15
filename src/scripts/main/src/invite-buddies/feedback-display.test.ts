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

import { displaySuccess } from "./feedback-display";
import { initGettextSync } from "@tuleap/gettext";

describe("feedback-display", () => {
    describe("displaySuccess", () => {
        beforeEach(() => {
            document.body.innerHTML = `
                <div id="invite-buddies-modal">
                    <div id="invite-buddies-modal-feedback">
                        <div class="tlp-alert-error">Previous error</div>
                    </div>
                    <div id="invite-buddies-modal-body"></div>
                    <div id="invite-buddies-modal-footer"></div>
                </div>
            `;
        });

        afterEach(() => {
            document.body.innerHTML = "";
        });

        it("Clear existing feedbacks, display the success one and inform that emails have been sent", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                { failures: [] },
                gettext_provider
            );

            expect(document.body).toMatchSnapshot();
        });

        it("Extracts emails that are in error to display a warning", () => {
            const gettext_provider = initGettextSync("invite-buddies", {}, "en_US");

            displaySuccess(
                ["wendy@example.com", "peter@example.com"],
                {
                    failures: ["peter@example.com"],
                },
                gettext_provider
            );

            expect(document.body).toMatchSnapshot();
        });
    });
});
