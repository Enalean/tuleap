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
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync, errAsync } from "neverthrow";
import { selectOrThrow } from "@tuleap/dom";
import type { GetText } from "@tuleap/gettext";
import { Fault } from "@tuleap/fault";
import {
    BADGE_SELECTOR,
    FEEDBACK_HIDDEN_CLASSNAME,
    PAGE_ALERT_SELECTOR,
    SYNCHRONIZE_BUTTON_ICON_SELECTOR,
    SYNCHRONIZE_BUTTON_SELECTOR,
    SynchronizeButton,
} from "./SynchronizeButton";
import {
    BADGE_ERROR_CLASSNAME,
    BADGE_SUCCESS_CLASSNAME,
    FEEDBACK_ERROR_CLASSNAME,
    FEEDBACK_SUCCESS_CLASSNAME,
    SPIN_CLASSNAME,
} from "./classnames";
import { uri } from "@tuleap/fetch-result";

const GROUP_LINK_ID = 33;

vi.mock("@tuleap/fetch-result");

describe(`SynchronizeButton`, () => {
    describe(`when I click the synchronize button`, () => {
        let button: HTMLButtonElement,
            button_icon: HTMLElement,
            feedback: HTMLElement,
            badge: HTMLElement;

        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();
            const gettext = {
                gettext: (msgid: string) => msgid,
                // eslint-disable-next-line @typescript-eslint/no-unused-vars
                ngettext: (msgid: string, msgidplural: string, count: number) => msgidplural,
            } as GetText;

            doc.body.insertAdjacentHTML(
                "afterbegin",
                `<div id="linked-group-alert" class="${FEEDBACK_HIDDEN_CLASSNAME}"></div>
                <span id="last-sync-badge">
                  <i data-test="badge-icon"></i>
                </span>
                <button type="button" id="synchronize-button">
                  <i class="fa-rotate" id="synchronize-icon"></i>
                </button>`,
            );

            SynchronizeButton(doc, gettext, GROUP_LINK_ID).init();

            button = selectOrThrow(doc, SYNCHRONIZE_BUTTON_SELECTOR, HTMLButtonElement);
            button_icon = selectOrThrow(button, SYNCHRONIZE_BUTTON_ICON_SELECTOR);
            feedback = selectOrThrow(doc, PAGE_ALERT_SELECTOR);
            badge = selectOrThrow(doc, BADGE_SELECTOR);
        });

        function assertLoadingState(is_loading: boolean): void {
            expect(button_icon.classList.contains(SPIN_CLASSNAME)).toBe(is_loading);
            expect(button.disabled).toBe(is_loading);
        }

        it(`will show a spinner, disable the button, call the REST route,
            show a success feedback,
            show a success badge,
            and update the last synchronization badge`, async () => {
            const number_of_integrations = 7;
            const result = okAsync({ number_of_integrations });
            const postSpy = vi.spyOn(fetch_result, "postJSON").mockReturnValue(result);

            button.click();
            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(true);
            assertLoadingState(true);

            await result;

            assertLoadingState(false);
            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(false);
            expect(feedback.classList.contains(FEEDBACK_SUCCESS_CLASSNAME)).toBe(true);
            expect(feedback.textContent).toContain(String(number_of_integrations));

            expect(badge.classList.contains(BADGE_SUCCESS_CLASSNAME)).toBe(true);
            expect(badge.textContent).toContain(String(number_of_integrations));
            const badge_icon = badge.querySelector("[data-test=badge-icon]");
            expect(badge_icon).not.toBeNull();

            expect(postSpy).toHaveBeenCalledWith(
                uri`/api/gitlab_groups/${GROUP_LINK_ID}/synchronize`,
                undefined,
            );
        });

        it(`and there is a REST error, it will show an error feedback
            and an error badge`, async () => {
            const error_message = "Something went wrong";
            const result = errAsync(Fault.fromMessage(error_message));
            vi.spyOn(fetch_result, "postJSON").mockReturnValue(result);

            button.click();
            await result;

            assertLoadingState(false);
            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(false);
            expect(feedback.classList.contains(FEEDBACK_ERROR_CLASSNAME)).toBe(true);
            expect(feedback.textContent).toContain(error_message);

            expect(badge.classList.contains(BADGE_ERROR_CLASSNAME)).toBe(true);
            expect(badge.textContent).toContain("In error, just now");
            const badge_icon = badge.querySelector("[data-test=badge-icon]");
            expect(badge_icon).not.toBeNull();
        });
    });
});
