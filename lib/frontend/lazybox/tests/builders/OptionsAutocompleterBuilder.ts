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

import type { LazyAutocompleterOptions, LazyboxTemplatingCallback } from "../../src/Options";
import { TemplatingCallbackStub } from "../stubs/TemplatingCallbackStub";

const noop = (): void => {
    //Do nothing
};

export class OptionsAutocompleterBuilder {
    #placeholder = "";
    #templating_callback: LazyboxTemplatingCallback = TemplatingCallbackStub.build();

    private constructor() {}

    static someOptions(): OptionsAutocompleterBuilder {
        return new OptionsAutocompleterBuilder();
    }

    withPlaceholder(placeholder: string): this {
        this.#placeholder = placeholder;
        return this;
    }

    withTemplatingCallback(callback: LazyboxTemplatingCallback): this {
        this.#templating_callback = callback;
        return this;
    }

    build(): LazyAutocompleterOptions {
        return {
            placeholder: this.#placeholder,
            templating_callback: this.#templating_callback,
            search_input_callback: noop,
            selection_callback: noop,
        };
    }
}
