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
import * as tlp_modal from "@tuleap/tlp-modal";
import * as gitlab_querier from "@tuleap/plugin-git-gitlab-api-querier";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { GetText } from "@tuleap/gettext";
import {
    FEEDBACK_HIDDEN_CLASSNAME,
    FORM_ELEMENT_DISABLED_CLASSNAME,
    HIDDEN_ICON_CLASSNAME,
} from "./classnames";
import {
    EDIT_CONFIRM_ICON_SELECTOR,
    EDIT_CONFIRM_SELECTOR,
    EDIT_TOKEN_BUTTON_SELECTOR,
    FORM_ELEMENTS_SELECTOR,
    INPUTS_SELECTOR,
    MODAL_FEEDBACK_SELECTOR,
    TOKEN_INPUT_SELECTOR,
    TOKEN_MODAL_SELECTOR,
    TokenModal,
} from "./TokenModal";
import { selectOrThrow } from "@tuleap/dom";
import { rawUri, uri } from "@tuleap/fetch-result";

const noop = (): void => {
    // Do nothing;
};

vi.mock("@tuleap/plugin-git-gitlab-api-querier");
vi.mock("@tuleap/fetch-result");

const GROUP_LINK_ID = 89;
const GITLAB_GROUP_ID = 68;
const GITLAB_SERVER_URI = "https://gitlab.example.com";
const NEW_TOKEN = "de5f0a";

describe(`TokenModal`, () => {
    let edit_button: HTMLButtonElement, token_modal: HTMLElement, modal_instance: tlp_modal.Modal;

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        const gettext = {
            gettext: (msgid: string) => msgid,
        } as GetText;

        doc.body.insertAdjacentHTML(
            "afterbegin",
            `<button id="edit-token-button"></button>
          <div id="token-modal">
            <form  id="token-modal-form">
              <div id="token-modal-feedback" class="${FEEDBACK_HIDDEN_CLASSNAME}">
                <div id="token-modal-alert"></div>
              </div>
              <div data-form-element>
                <input type="password" id="token-modal-token-input">
              </div>
              <button type="submit" id="token-modal-confirm">
                <i id="token-icon" class="${HIDDEN_ICON_CLASSNAME}"></i>
              </button>
            </form>
          </div>`
        );

        modal_instance = {
            show: noop,
            hide: noop,
        } as tlp_modal.Modal;
        vi.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);

        TokenModal(doc, gettext, {
            id: GROUP_LINK_ID,
            gitlab_group_id: GITLAB_GROUP_ID,
            gitlab_server_uri: GITLAB_SERVER_URI,
        }).init();
        edit_button = selectOrThrow(doc, EDIT_TOKEN_BUTTON_SELECTOR, HTMLButtonElement);
        token_modal = selectOrThrow(doc, TOKEN_MODAL_SELECTOR);
    });

    it(`when I click on the "edit" button, it will show the modal`, () => {
        const show = vi.spyOn(modal_instance, "show");

        edit_button.click();

        expect(show).toHaveBeenCalled();
    });

    describe(`when I click the "confirm" button in the modal`, () => {
        let confirm_button: HTMLButtonElement, button_icon: HTMLElement, feedback: HTMLElement;
        beforeEach(() => {
            feedback = selectOrThrow(token_modal, MODAL_FEEDBACK_SELECTOR);
            confirm_button = selectOrThrow(token_modal, EDIT_CONFIRM_SELECTOR, HTMLButtonElement);
            button_icon = selectOrThrow(confirm_button, EDIT_CONFIRM_ICON_SELECTOR);
            const token_input = selectOrThrow(token_modal, TOKEN_INPUT_SELECTOR, HTMLInputElement);
            token_input.value = NEW_TOKEN;
        });

        function assertLoadingState(is_loading: boolean): void {
            const icon_classes = button_icon.classList;
            expect(icon_classes.contains(HIDDEN_ICON_CLASSNAME)).toBe(!is_loading);
            expect(confirm_button.disabled).toBe(is_loading);
            token_modal.querySelectorAll(FORM_ELEMENTS_SELECTOR).forEach((form_element) => {
                expect(form_element.classList.contains(FORM_ELEMENT_DISABLED_CLASSNAME)).toBe(
                    is_loading
                );
            });
            token_modal.querySelectorAll(INPUTS_SELECTOR).forEach((input) => {
                expect(input.disabled).toBe(is_loading);
            });
        }

        it(`will show a spinner, disable the form elements, call the REST endpoints and close the modal`, async () => {
            const modalHide = vi.spyOn(modal_instance, "hide");
            const validate_token_result = okAsync({} as Response);
            const save_token_result = okAsync(undefined);
            const validateTokenSpy = vi
                .spyOn(gitlab_querier, "get")
                .mockReturnValue(validate_token_result);
            const saveTokenSpy = vi
                .spyOn(fetch_result, "patchJSON")
                .mockReturnValue(save_token_result);

            confirm_button.click();

            expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(true);
            assertLoadingState(true);

            await validate_token_result;
            await save_token_result;

            assertLoadingState(false);
            expect(modalHide).toHaveBeenCalled();
            expect(validateTokenSpy).toHaveBeenCalledWith(
                uri`${rawUri(GITLAB_SERVER_URI)}/api/v4/groups/${GITLAB_GROUP_ID}`,
                { token: NEW_TOKEN }
            );
            expect(saveTokenSpy).toHaveBeenCalledWith(uri`/api/gitlab_groups/${GROUP_LINK_ID}`, {
                gitlab_token: NEW_TOKEN,
            });
        });

        it.each([
            [
                `and the GitLab endpoint returns code 401`,
                errAsync({
                    isUnauthenticated: () => true,
                    ...Fault.fromMessage("Bad credentials"),
                }),
                okAsync(undefined),
                "please check your credentials",
            ],
            [
                `and the GitLab endpoint returns another error`,
                errAsync({ isGitlabAPIFault: () => true, ...Fault.fromMessage("Not Found") }),
                okAsync(undefined),
                "Not Found",
            ],
            [
                `and the Tuleap endpoint returns an error`,
                okAsync({} as Response),
                errAsync(Fault.fromMessage("Tuleap error")),
                "Tuleap error",
            ],
        ])(
            `%s, it will show an error message in the modal feedback`,
            async (_precondition, validate_token_result, save_token_result, error_message) => {
                const modalHide = vi.spyOn(modal_instance, "hide");
                vi.spyOn(gitlab_querier, "get").mockReturnValue(validate_token_result);
                vi.spyOn(fetch_result, "patchJSON").mockReturnValue(save_token_result);

                confirm_button.click();
                await validate_token_result;
                await save_token_result;

                assertLoadingState(false);
                expect(feedback.classList.contains(FEEDBACK_HIDDEN_CLASSNAME)).toBe(false);
                expect(feedback.textContent).toContain(error_message);
                expect(modalHide).not.toHaveBeenCalled();
            }
        );
    });
});
