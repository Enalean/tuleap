/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { type Modal, createModal, EVENT_TLP_MODAL_WILL_HIDE } from "@tuleap/tlp-modal";

class ModalWrapper extends HTMLElement {
    button: Element | null = null;
    select_element: Element | null = null;
    modal_instance: Modal | undefined = undefined;
    #keyboard: boolean = true;
    #dismiss_on_backdrop_click: boolean = true;

    set keyboard(keyboard: boolean) {
        this.#keyboard = keyboard;
        this.update();
    }

    set dismiss_on_backdrop_click(dismiss_on_backdrop_click: boolean) {
        this.#dismiss_on_backdrop_click = dismiss_on_backdrop_click;
        this.update();
    }

    handler(): void {
        this.modal_instance?.show();
    }

    update(): void {
        this.modal_instance?.destroy();
        if (!this.select_element) {
            return;
        }
        this.modal_instance = createModal(this.select_element, {
            keyboard: this.#keyboard,
            dismiss_on_backdrop_click: this.#dismiss_on_backdrop_click,
        });
    }

    connectedCallback(): void {
        this.modal_instance?.destroy();
        this.button = this.querySelector("[type=button]");
        this.select_element = this.querySelector(".tlp-modal");
        if (!this.button || !this.select_element) {
            return;
        }
        this.modal_instance = createModal(this.select_element, { keyboard: this.#keyboard });

        this.button.addEventListener("click", this.handler.bind(this));

        if (this.getAttribute("story") === "events") {
            this.modal_instance.addEventListener(EVENT_TLP_MODAL_WILL_HIDE, (event) => {
                event.preventDefault();
                // eslint-disable-next-line no-alert
                const should_hide = window.confirm("You may lose your work. Close the modal?");
                if (this.modal_instance && should_hide) {
                    this.modal_instance.hide();
                }
            });
        }
    }
}

if (!window.customElements.get("tuleap-modal-wrapper")) {
    window.customElements.define("tuleap-modal-wrapper", ModalWrapper);
}
