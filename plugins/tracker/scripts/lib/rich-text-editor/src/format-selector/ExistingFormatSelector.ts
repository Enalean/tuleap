/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { FormatSelectorInterface, FormatSelectorPresenter } from "./FormatSelectorInterface";
import { isValidTextFormat } from "@tuleap/plugin-tracker-constants";

export class ExistingFormatSelector implements FormatSelectorInterface {
    constructor(private readonly doc: Document) {}

    insertFormatSelectbox(textarea: HTMLTextAreaElement, presenter: FormatSelectorPresenter): void {
        const format_input = this.doc.getElementById(presenter.id);
        if (!(format_input instanceof HTMLSelectElement)) {
            throw new Error(`Format selector with id #${presenter.id} not found`);
        }
        format_input.addEventListener("input", () => {
            if (isValidTextFormat(format_input.value)) {
                presenter.editor.onFormatChange(format_input.value);
            } else {
                throw new Error(`${format_input.value} is an invalid form`);
            }
        });
    }
}
