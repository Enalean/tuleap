/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { TextFieldFormat } from "../../../../../constants/fields-constants";
import { TEXT_FORMAT_COMMONMARK } from "../../../../../constants/fields-constants";
import type { FormatChangedCallback, FormatSelectorPresenter } from "../FormatSelectorInterface";

export class EditorAreaState {
    public selected_value: TextFieldFormat;
    public readonly selectbox_id: string;
    public readonly selectbox_name?: string;
    private readonly presenterOnFormatChangedCallback: FormatChangedCallback;

    constructor(
        public readonly mount_point: HTMLDivElement,
        public readonly textarea: HTMLTextAreaElement,
        presenter: FormatSelectorPresenter
    ) {
        this.selectbox_id = presenter.id;
        this.selectbox_name = presenter.name;
        this.selected_value = presenter.selected_value;
        this.presenterOnFormatChangedCallback = presenter.formatChangedCallback;
    }

    public isCurrentFormatMarkdown(): boolean {
        return this.selected_value === TEXT_FORMAT_COMMONMARK;
    }

    public onFormatChange(new_format: TextFieldFormat): void {
        this.selected_value = new_format;
        this.presenterOnFormatChangedCallback(new_format);
    }
}
