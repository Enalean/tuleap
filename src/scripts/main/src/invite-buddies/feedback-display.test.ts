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

describe("feedback-display", () => {
    describe("displaySuccess", () => {
        beforeEach(() => {
            document.body.innerHTML = `
                <div id="invite-buddies-modal">
                    <div id="invite-buddies-modal-feedback"
                        data-success-feedback-message="Invitation has been successfully sent to:"
                        data-failure-feedback-message="Invitation could not be sent to:"
                    >
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
            displaySuccess(["wendy@example.com", "peter@example.com"], { failures: [] });

            expect(document.body).toMatchSnapshot();
        });

        it("Extracts emails that are in error to display a warning", () => {
            displaySuccess(["wendy@example.com", "peter@example.com"], {
                failures: ["peter@example.com"],
            });

            expect(document.body).toMatchSnapshot();
        });
    });
});
