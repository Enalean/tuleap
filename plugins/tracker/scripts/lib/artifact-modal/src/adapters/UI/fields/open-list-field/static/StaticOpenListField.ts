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
import type { ControlStaticOpenListField } from "./StaticOpenListFieldController";
import type { StaticOpenListFieldPresenter } from "./StaticOpenListFieldPresenter";
import { renderStaticOpenListField } from "./StaticOpenListFieldTemplate";

export const TAG = "tuleap-artifact-modal-static-open-list-field";

export type StaticOpenListField = {
    disabled: boolean;
    controller: ControlStaticOpenListField;
};

export type InternalStaticOpenListField = Readonly<StaticOpenListField> & {
    readonly content: () => HTMLElement;
    select_element: HTMLSelectElement;
    presenter: StaticOpenListFieldPresenter;
};

export type HostElement = InternalStaticOpenListField & HTMLElement;

export const StaticOpenListField = define<InternalStaticOpenListField>({
    tag: TAG,
    presenter: undefined,
    disabled: false,
    select_element: ({ content }) => {
        const select = content().querySelector("[data-role=select-element]");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error(`Unable to find the <select> in the StaticOpenListField`);
        }

        return select;
    },
    controller: {
        set: (host, controller: ControlStaticOpenListField) => {
            controller.init(host);

            return controller;
        },
    },
    content: renderStaticOpenListField,
});
