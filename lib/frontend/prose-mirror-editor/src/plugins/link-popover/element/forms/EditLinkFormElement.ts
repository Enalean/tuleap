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
import type { GetText } from "@tuleap/gettext";
import { createPopover } from "@tuleap/tlp-popovers";
import type { EditLinkCallback } from "../LinkPopoverEditionFormRenderers";
import { renderEditLinkForm } from "./EditLinkFormTemplate";

export const TAG = "edit-link-form";

export type EditLinkFormElement = {
    gettext_provider: GetText;
    edit_link_callback: EditLinkCallback;
    cancel_callback: () => void;
    link_href: string;
    link_title: string;
    popover_anchor: HTMLElement;
};

export type InternalEditLinkFormElement = Readonly<EditLinkFormElement> & {
    popover_element: HTMLElement;
    render(): HTMLElement;
};

export type HostElement = InternalEditLinkFormElement & HTMLElement;

type DisconnectFunction = () => void;
export const connect = (host: InternalEditLinkFormElement): DisconnectFunction => {
    const popover_instance = createPopover(host.popover_anchor, host.popover_element, {
        placement: "right",
        trigger: "click",
    });
    popover_instance.show();

    return () => {
        popover_instance.destroy();
    };
};

const edit_link_form = define.compile<InternalEditLinkFormElement>({
    tag: TAG,
    link_href: "",
    link_title: "",
    gettext_provider: (host, gettext_provider) => gettext_provider,
    edit_link_callback: (host, edit_link_callback) => edit_link_callback,
    cancel_callback: (host, cancel_callback) => cancel_callback,
    popover_anchor: (host, popover_anchor) => popover_anchor,
    popover_element: {
        value: (host: InternalEditLinkFormElement): HTMLElement => {
            const popover = host.render().querySelector<HTMLElement>("[data-role=popover]");
            if (!popover) {
                throw new Error("Unable to retrieve the form popover element :(");
            }

            return popover;
        },
        connect,
    },
    render: {
        value: (host) => renderEditLinkForm(host, host.gettext_provider),
        shadow: false,
    },
});

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, edit_link_form);
}
