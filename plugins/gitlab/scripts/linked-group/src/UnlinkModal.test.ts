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

import {
    HIDDEN_CLASSNAME,
    SPIN_CLASSNAME,
    SPINNER_CLASSNAME,
    UNLINK_CONFIRM_ICON_SELECTOR,
    UNLINK_CONFIRM_SELECTOR,
    UNLINK_ICON_CLASSNAME,
    UNLINK_MODAL_FEEDBACK_SELECTOR,
    UnlinkModal,
} from "./UnlinkModal";
import type { GetText } from "@tuleap/gettext";
import * as fetch_result from "@tuleap/fetch-result";
import { selectOrThrow } from "@tuleap/dom";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

const GROUP_ID = 77;

describe(`UnlinkModal`, () => {
    let doc: Document, loc: Location;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        loc = {
            reload: jest.fn(),
        } as unknown as Location;
    });

    describe(`when I click the "confirm" button in the modal`, () => {
        let button: HTMLButtonElement, icon: HTMLElement;

        const clickOnConfirm = (): void => {
            const gettext = {
                gettext: (msgid: string) => msgid,
            } as GetText;

            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<div id="unlink-modal">
                        <div id="unlink-modal-feedback">
                          <div id="unlink-modal-alert" class="${HIDDEN_CLASSNAME}"></div>
                        </div>
                        <button type="button" id="unlink-confirm" data-group-id="${GROUP_ID}">
                            <i id="unlink-icon" class="fa-solid fa-link-slash"></i>
                        </button>
                    </div>`
            );

            UnlinkModal(loc, doc, gettext).init();

            button = selectOrThrow(doc.body, UNLINK_CONFIRM_SELECTOR, HTMLButtonElement);
            icon = selectOrThrow(doc.body, UNLINK_CONFIRM_ICON_SELECTOR);
            button.click();
        };

        it(`will call the REST route, reload the page
            and will keep the button disabled with a spinner
            to prevent user from triggering delete again while the page is reloading`, async () => {
            const reload = jest.spyOn(loc, "reload");
            const result = okAsync({} as Response);
            const delSpy = jest.spyOn(fetch_result, "del");
            delSpy.mockReturnValue(result);

            clickOnConfirm();
            await result;

            expect(delSpy).toHaveBeenCalledWith(`/api/gitlab_groups/${GROUP_ID}`);
            expect(icon.classList.contains(SPINNER_CLASSNAME)).toBe(true);
            expect(icon.classList.contains(SPIN_CLASSNAME)).toBe(true);
            expect(icon.classList.contains(UNLINK_ICON_CLASSNAME)).toBe(false);
            expect(button.hasAttribute("disabled")).toBe(true);
            expect(reload).toHaveBeenCalled();
        });

        it(`and there is a REST error,
            it will show an error message in the modal feedback`, async () => {
            const error_message = "Forbidden";
            const reload = jest.spyOn(loc, "reload");
            const result = errAsync(Fault.fromMessage(error_message));
            const delSpy = jest.spyOn(fetch_result, "del");
            delSpy.mockReturnValue(result);

            clickOnConfirm();

            const feedback = selectOrThrow(doc, UNLINK_MODAL_FEEDBACK_SELECTOR);

            expect(icon.classList.contains(SPINNER_CLASSNAME)).toBe(true);
            expect(icon.classList.contains(SPIN_CLASSNAME)).toBe(true);
            expect(icon.classList.contains(UNLINK_ICON_CLASSNAME)).toBe(false);
            expect(button.hasAttribute("disabled")).toBe(true);

            await result;

            expect(reload).not.toHaveBeenCalled();
            expect(icon.classList.contains(SPINNER_CLASSNAME)).toBe(false);
            expect(icon.classList.contains(SPIN_CLASSNAME)).toBe(false);
            expect(icon.classList.contains(UNLINK_ICON_CLASSNAME)).toBe(true);
            expect(button.hasAttribute("disabled")).toBe(false);

            expect(feedback.classList.contains(HIDDEN_CLASSNAME)).toBe(false);
            expect(feedback.textContent).toContain(error_message);
        });
    });
});
