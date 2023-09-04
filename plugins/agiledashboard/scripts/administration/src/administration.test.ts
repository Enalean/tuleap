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

import {
    addModalListeners,
    displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce,
} from "./administration";

describe("administration", () => {
    describe("displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce", () => {
        function createLocalButtonElement(): {
            submit_without_modal: HTMLElement;
            submit_with_modal: HTMLElement;
            form_element_switch_to_explicit_backlog: HTMLInputElement;
        } {
            const local_document = document.createElement("div");

            const form_element_switch_to_explicit_backlog = document.createElement("input");
            form_element_switch_to_explicit_backlog.setAttribute("id", "use-explicit-top-backlog");
            form_element_switch_to_explicit_backlog.value = "1";
            form_element_switch_to_explicit_backlog.checked = true;

            const section_switch_to_explicit_backlog = document.createElement("section");

            const submit_without_modal = document.createElement("button");
            submit_without_modal.setAttribute("id", "ad-service-submit");

            const submit_with_modal = document.createElement("button");
            submit_with_modal.setAttribute("id", "scrum-configuration-edit-options-button");
            submit_with_modal.dataset.targetModalId = "scrum-explicit-backlog-switch-usage-modal";
            submit_with_modal.classList.add("scrum-administration-submit-hidden");

            const modal = document.createElement("div");
            const modal_legacy_text = document.createElement("p");
            const modal_explicit_text = document.createElement("p");

            modal.appendChild(modal_legacy_text);
            modal.appendChild(modal_explicit_text);

            section_switch_to_explicit_backlog.appendChild(submit_without_modal);
            section_switch_to_explicit_backlog.appendChild(submit_with_modal);
            section_switch_to_explicit_backlog.appendChild(modal);

            form_element_switch_to_explicit_backlog.appendChild(section_switch_to_explicit_backlog);

            local_document.appendChild(form_element_switch_to_explicit_backlog);

            document.body.innerHTML = "";
            document.body.appendChild(local_document);

            return {
                submit_without_modal,
                submit_with_modal,
                form_element_switch_to_explicit_backlog,
            };
        }
        it("does nothing if explicit backlog is not used", () => {
            const { submit_without_modal, submit_with_modal } = createLocalButtonElement();

            submit_without_modal.dataset.ExplicitBacklogValue = "0";

            displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce();

            expect(submit_without_modal.classList).not.toContain(
                "scrum-administration-submit-hidden",
            );
            expect(submit_with_modal.classList).toContain("scrum-administration-submit-hidden");
        });
        it("uses the default button when switch to backlog usage have never been hit", () => {
            const { submit_without_modal, submit_with_modal } = createLocalButtonElement();

            submit_without_modal.dataset.ExplicitBacklogValue = "0";

            displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce();

            expect(submit_without_modal.classList).not.toContain(
                "scrum-administration-submit-hidden",
            );
            expect(submit_with_modal.classList).toContain("scrum-administration-submit-hidden");
        });
        it("uses the button with a listener on modal when switch to backlog have been clicked at least once", () => {
            const {
                submit_without_modal,
                submit_with_modal,
                form_element_switch_to_explicit_backlog,
            } = createLocalButtonElement();

            submit_without_modal.dataset.ExplicitBacklogValue = "1";

            displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce();

            form_element_switch_to_explicit_backlog.click();

            expect(submit_without_modal.classList).toContain("scrum-administration-submit-hidden");
            expect(submit_with_modal.classList).not.toContain("scrum-administration-submit-hidden");
        });
    });

    describe("addModalListeners", () => {
        function createModalLocalDom(): {
            modal_legacy_text: HTMLElement;
            modal_explicit_text: HTMLElement;
            submit_with_modal: HTMLElement;
        } {
            const local_document = document.createElement("div");

            const modal = document.createElement("div");
            const modal_id = "scrum-explicit-modal";
            modal.setAttribute("id", modal_id);

            const modal_legacy_text = document.createElement("p");
            modal_legacy_text.setAttribute("id", "legacy-mode-text");
            const modal_explicit_text = document.createElement("p");
            modal_explicit_text.setAttribute("id", "explicit-mode-text");

            const submit_with_modal = document.createElement("button");
            submit_with_modal.setAttribute("id", "scrum-configuration-edit-options-button");
            submit_with_modal.dataset.targetModalId = modal_id;

            modal.appendChild(modal_legacy_text);
            modal.appendChild(modal_explicit_text);

            local_document.appendChild(modal);

            document.body.innerHTML = "";
            document.body.appendChild(local_document);
            document.body.appendChild(submit_with_modal);
            return { modal_legacy_text, modal_explicit_text, submit_with_modal };
        }

        it("display legacy text when user switch from explicit backlog to legacy one", () => {
            const { modal_legacy_text, modal_explicit_text, submit_with_modal } =
                createModalLocalDom();
            submit_with_modal.dataset.ExplicitBacklogValue = "0";

            addModalListeners(submit_with_modal);
            submit_with_modal.click();

            expect(modal_legacy_text.classList).not.toContain("scrum-administration-submit-hidden");
            expect(modal_explicit_text.classList).toContain("scrum-administration-submit-hidden");
        });

        it("display explicit backlog text when user switch from legacy to explicit mode", () => {
            const { modal_legacy_text, modal_explicit_text, submit_with_modal } =
                createModalLocalDom();
            submit_with_modal.dataset.ExplicitBacklogValue = "1";

            addModalListeners(submit_with_modal);
            submit_with_modal.click();

            expect(modal_legacy_text.classList).not.toContain("scrum-administration-submit-hidden");
            expect(modal_explicit_text.classList).toContain("scrum-administration-submit-hidden");
        });
    });
});
