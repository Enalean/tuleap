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

import type {
    LazyboxNewItemClickedCallback,
    LazyboxNewItemLabelCallback,
    LazyboxOptions,
    LazyboxSelectionBadgeCallback,
    LazyboxTemplatingCallback,
} from "../../src/Options";
import { TemplatingCallbackStub } from "../stubs/TemplatingCallbackStub";

const noop = (): void => {
    //Do nothing
};

export class OptionsBuilder {
    readonly #is_multiple: boolean;
    #placeholder = "";
    #search_input_placeholder = "";
    #templating_callback: LazyboxTemplatingCallback = TemplatingCallbackStub.build();
    #selection_badge_callback: LazyboxSelectionBadgeCallback | undefined = undefined;
    #new_item_clicked_callback: LazyboxNewItemClickedCallback | undefined = undefined;
    #new_item_label_callback: LazyboxNewItemLabelCallback = () => "";

    private constructor(is_multiple: boolean) {
        this.#is_multiple = is_multiple;
    }

    static withSingle(): OptionsBuilder {
        return new OptionsBuilder(false);
    }

    static withMultiple(): OptionsBuilder {
        return new OptionsBuilder(true);
    }

    static withSelectionBadgeCallback(callback: LazyboxSelectionBadgeCallback): OptionsBuilder {
        const builder = new OptionsBuilder(true);
        builder.#selection_badge_callback = callback;
        return builder;
    }

    withPlaceholder(placeholder: string): this {
        this.#placeholder = placeholder;
        return this;
    }

    withSearchInputPlaceholder(placeholder: string): this {
        this.#search_input_placeholder = placeholder;
        return this;
    }

    withNewItemButton(
        clicked_callback: LazyboxNewItemClickedCallback,
        label_callback: LazyboxNewItemLabelCallback
    ): this {
        this.#new_item_clicked_callback = clicked_callback;
        this.#new_item_label_callback = label_callback;
        return this;
    }

    withTemplatingCallback(callback: LazyboxTemplatingCallback): this {
        this.#templating_callback = callback;
        return this;
    }

    build(): LazyboxOptions {
        let options: LazyboxOptions = {
            is_multiple: true,
            placeholder: this.#placeholder,
            templating_callback: this.#templating_callback,
            search_input_callback: noop,
            selection_callback: noop,
        };
        if (this.#new_item_clicked_callback) {
            options = {
                ...options,
                new_item_clicked_callback: this.#new_item_clicked_callback,
                new_item_label_callback: this.#new_item_label_callback,
            };
        }
        if (this.#selection_badge_callback) {
            options = {
                ...options,
                selection_badge_callback: this.#selection_badge_callback,
            };
        }
        if (!this.#is_multiple) {
            return {
                ...options,
                is_multiple: false,
                search_input_placeholder: this.#search_input_placeholder,
            };
        }
        return options;
    }
}
