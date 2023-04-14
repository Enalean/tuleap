/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { LazyboxOptions } from "../../src";

const noop = (): void => {
    //Do nothing
};

export class OptionsBuilder {
    #is_multiple = false;
    #placeholder = "";
    #search_input_placeholder = "";

    private constructor() {
        //Do nothing, make constructor private
    }

    static withoutNewItem(): OptionsBuilder {
        return new OptionsBuilder();
    }

    withPlaceholder(placeholder: string): this {
        this.#placeholder = placeholder;
        return this;
    }

    withSearchInputPlaceholder(placeholder: string): this {
        this.#search_input_placeholder = placeholder;
        return this;
    }

    withIsMultiple(): this {
        this.#is_multiple = true;
        return this;
    }

    build(): LazyboxOptions {
        return {
            is_multiple: this.#is_multiple,
            placeholder: this.#placeholder,
            search_input_placeholder: this.#search_input_placeholder,
            search_field_callback: noop,
            selection_callback: noop,
            templating_callback: (html) => html``,
        };
    }
}
