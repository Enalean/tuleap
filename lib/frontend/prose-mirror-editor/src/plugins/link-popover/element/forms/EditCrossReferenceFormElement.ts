/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { define } from "hybrids";
import { createPopover } from "@tuleap/tlp-popovers";
import { renderEditCrossReferenceFormElement } from "./EditCrossReferenceFormTemplate";
import type { GetText } from "@tuleap/gettext";

export const TAG = "edit-cross-reference-form";

export type EditCrossReferenceCallback = (cross_reference_text: string) => void;

export type EditCrossReferenceFormElement = {
    gettext_provider: GetText;
    edit_cross_reference_callback: EditCrossReferenceCallback;
    cancel_callback: () => void;
    reference_text: string;
    popover_anchor: HTMLElement;
};

export type InternalEditCrossReferenceFormElement = Readonly<EditCrossReferenceFormElement> & {
    popover_element: HTMLElement;
    render(): HTMLElement;
};

export type HostElement = InternalEditCrossReferenceFormElement & HTMLElement;

type DisconnectFunction = () => void;
export const connect = (host: InternalEditCrossReferenceFormElement): DisconnectFunction => {
    const popover_instance = createPopover(host.popover_anchor, host.popover_element, {
        placement: "right",
        trigger: "click",
    });
    popover_instance.show();

    return () => {
        popover_instance.destroy();
    };
};

const edit_cross_reference_form = define.compile<InternalEditCrossReferenceFormElement>({
    tag: TAG,
    reference_text: "",
    gettext_provider: (host, gettext_provider) => gettext_provider,
    edit_cross_reference_callback: (host, edit_cross_reference_callback) =>
        edit_cross_reference_callback,
    cancel_callback: (host, cancel_callback) => cancel_callback,
    popover_anchor: (host, popover_anchor) => popover_anchor,
    popover_element: {
        value: (host: InternalEditCrossReferenceFormElement): HTMLElement => {
            const popover = host.render().querySelector<HTMLElement>("[data-role=popover]");
            if (!popover) {
                throw new Error("Unable to retrieve the form popover element :(");
            }

            return popover;
        },
        connect,
    },
    render: {
        value: (host) => renderEditCrossReferenceFormElement(host, host.gettext_provider),
        shadow: false,
    },
});

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, edit_cross_reference_form);
}
