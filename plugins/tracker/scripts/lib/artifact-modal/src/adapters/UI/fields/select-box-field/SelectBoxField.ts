/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import type { ControlSelectBoxField } from "./SelectBoxFieldController";
import type { SelectBoxFieldPresenter } from "./SelectBoxFieldPresenter";
import { buildSelectBox } from "./SelectBoxFieldTemplate";
import type { BindValueId } from "../../../../domain/fields/select-box-field/BindValueId";
import { highlightSelectBoxField } from "./SelectBoxHighlighter";

export interface SelectBoxField {
    readonly controller: ControlSelectBoxField;
    readonly select_element: HTMLSelectElement;
    field_presenter: SelectBoxFieldPresenter;
    bind_value_ids: ReadonlyArray<BindValueId>;
}
type InternalSelectboxField = SelectBoxField & {
    content(): HTMLElement;
};
export type HostElement = InternalSelectboxField & HTMLElement;

type MapOfClasses = Record<string, boolean>;

export const getFormElementClasses = (host: SelectBoxField): MapOfClasses => ({
    "tlp-form-element": true,
    "tlp-form-element-error":
        (host.bind_value_ids.length === 0 || host.bind_value_ids.includes(100)) &&
        host.field_presenter.is_field_required,
});

function getDisconnectedCallback(host: HostElement): () => void {
    return () => {
        host.controller.destroy();
    };
}

export const SelectBoxField = define<InternalSelectboxField>({
    tag: "tuleap-artifact-modal-select-box-field",
    select_element: ({ content }) => {
        const input = content().querySelector(`[data-select=list-picker]`);
        if (!(input instanceof HTMLSelectElement)) {
            throw new Error(`Unable to find the <select> in the SelectBoxField`);
        }
        return input;
    },
    controller: {
        set: (host, controller: ControlSelectBoxField) => {
            host.bind_value_ids = controller.getInitialBindValueIds();
            host.field_presenter = controller.buildPresenter();
            controller.initListPicker(host.select_element);
            controller.onDependencyChange((bind_value_ids, presenter) => {
                host.bind_value_ids = bind_value_ids;
                host.field_presenter = presenter;

                highlightSelectBoxField(host);
            });

            setTimeout(() => controller.setSelectedValue(host.bind_value_ids));

            return controller;
        },
        connect: (host) => getDisconnectedCallback(host),
    },
    field_presenter: undefined,
    bind_value_ids: undefined,
    content: (host) => html`
        <div class="${getFormElementClasses(host)}">
            <label for="${`tracker_field_${host.field_presenter.field_id}`}" class="tlp-label">
                ${host.field_presenter.field_label}
                ${host.field_presenter.is_field_required &&
                html`<i class="fa-solid fa-asterisk" aria-hidden="true"></i>`}
            </label>
            ${buildSelectBox(host)}
        </div>
    `,
});
