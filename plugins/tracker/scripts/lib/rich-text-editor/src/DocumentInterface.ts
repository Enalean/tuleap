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

import { TextFieldFormat } from "../../../constants/fields-constants";

export interface OptionPresenter {
    is_selected: boolean;
    text: string;
    value: string;
}

export type SelectboxInputCallback = (new_value: string) => void;

export interface SelectboxPresenter {
    id: string;
    name: string;
    onInputCallback: SelectboxInputCallback;
    options: HTMLOptionElement[];
}

export interface SelectboxWrapperPresenter {
    label: string;
    child: HTMLSelectElement;
}

export interface DocumentInterface {
    /**
     * @throws Error
     */
    getDefaultFormat(): TextFieldFormat;
    createOption(presenter: OptionPresenter): HTMLOptionElement;
    createSelectBox(presenter: SelectboxPresenter): HTMLSelectElement;
    createSelectBoxWrapper(presenter: SelectboxWrapperPresenter): HTMLDivElement;
    insertFormatWrapper(textarea: HTMLTextAreaElement, wrapper: HTMLDivElement): void;
}
