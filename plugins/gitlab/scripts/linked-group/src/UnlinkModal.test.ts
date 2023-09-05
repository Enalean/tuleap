/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, it, beforeEach, vi, expect } from "vitest";
import {
    UNLINK_CONFIRM_ICON_SELECTOR,
    UNLINK_CONFIRM_SELECTOR,
    UNLINK_MODAL_FEEDBACK_SELECTOR,
    UnlinkModal,
} from "./UnlinkModal";
import type { GetText } from "@tuleap/gettext";
import * as fetch_result from "@tuleap/fetch-result";
import { selectOrThrow } from "@tuleap/dom";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { FEEDBACK_HIDDEN_CLASSNAME, HIDDEN_ICON_CLASSNAME } from "./classnames";
import { uri } from "@tuleap/fetch-result";

const GROUP_LINK_ID = 77;

vi.mock("@tuleap/fetch-result");

describe(`UnlinkModal`, () => {
    let doc: Document, loc: Location;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        loc = {
            replace: vi.fn(),
            href: "https://gitlab.example.com/plugins/git/project_101/administration/gitlab/",
        } as unknown as Location;
    });

    describe(`when I click the "confirm" button in the modal`, () => {
        let button: HTMLButtonElement, icon: HTMLElement, feedback: HTMLElement;

        const clickOnConfirm = (): void => {
            const gettext = {
                gettext: (msgid: string) => msgid,
            } as GetText;

            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<div id="unlink-modal">
                        <div id="unlink-modal-feedback">
                          <div id="unlink-modal-alert" class="${FEEDBACK_HIDDEN_CLASSNAME}"></div>
                        </div>
                        <button type="button" id="unlink-confirm">
                            <i id="unlink-icon" class="fa-solid ${HIDDEN_ICON_CLASSNAME}"></i>
                        </button>
                    </div>`,
            );

            UnlinkModal(loc, doc, gettext, GROUP_LINK_ID).init();

            button = selectOrThrow(doc.body, UNLINK_CONFIRM_SELECTOR, HTMLButtonElement);
            icon = selectOrThrow(doc.body, UNLINK_CONFIRM_ICON_SELECTOR);
            feedback = selectOrThrow(doc, UNLINK_MODAL_FEEDBACK_SELECTOR);
            button.click();
        };

        function assertLoadingState(is_loading: boolean): void {
            expect(icon.classList.contains(HIDDEN_ICON_CLASSNAME)).toBe(!is_loading);
            expect(button.disabled).toBe(is_loading);
        }

        it(`will call the REST route, reload the page
            and will keep the button disabled with a spinner
            to prevent user from triggering delete again while the page is reloading`, async () => {
            const replace = vi.spyOn(loc, "replace");
            const result = okAsync({} as Response);
            const delSpy = vi.spyOn(fetch_result, "del").mockReturnValue(result);

            clickOnConfirm();

            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(true);

            await result;

            expect(delSpy).toHaveBeenCalledWith(uri`/api/gitlab_groups/${GROUP_LINK_ID}`);
            assertLoadingState(true);

            const expected_url = new URL(
                "https://gitlab.example.com/plugins/git/project_101/administration/gitlab/",
            );
            expected_url.searchParams.append("unlink_group", "1");

            expect(replace).toHaveBeenCalledWith(expected_url);
        });

        it(`and there is a REST error,
            it will show an error message in the modal feedback`, async () => {
            const error_message = "Forbidden";
            const replace = vi.spyOn(loc, "replace");
            const result = errAsync(Fault.fromMessage(error_message));
            vi.spyOn(fetch_result, "del").mockReturnValue(result);

            clickOnConfirm();

            assertLoadingState(true);

            await result;

            expect(replace).not.toHaveBeenCalled();
            assertLoadingState(false);
            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(false);
            expect(feedback.textContent).toContain(error_message);
        });
    });
});
