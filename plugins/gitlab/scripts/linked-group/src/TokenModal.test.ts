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

import * as tlp_modal from "@tuleap/tlp-modal";
import { FEEDBACK_HIDDEN_CLASSNAME } from "./classnames";
import { EDIT_ICON_CLASSNAME, EDIT_TOKEN_BUTTON_SELECTOR, TokenModal } from "./TokenModal";
import { selectOrThrow } from "@tuleap/dom";

const noop = (): void => {
    // Do nothing;
};

describe(`TokenModal`, () => {
    let edit_button: HTMLButtonElement, modal_instance: tlp_modal.Modal;

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();

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
                <i id="token-icon" class="${EDIT_ICON_CLASSNAME}"></i>
              </button>
            </form>
          </div>`
        );

        modal_instance = {
            show: noop,
            hide: noop,
        } as tlp_modal.Modal;
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(modal_instance);

        TokenModal(doc).init();
        edit_button = selectOrThrow(doc, EDIT_TOKEN_BUTTON_SELECTOR, HTMLButtonElement);
    });

    it(`when I click on the "edit" button, it will show the modal`, () => {
        const show = jest.spyOn(modal_instance, "show");

        edit_button.click();

        expect(show).toHaveBeenCalled();
    });
});
