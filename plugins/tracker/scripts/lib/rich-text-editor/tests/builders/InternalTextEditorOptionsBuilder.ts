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

import type { InternalTextEditorOptions } from "../../src/types";

type AdditionalOptionsProvider = () => CKEDITOR.config;

const noop = (): void => {
    //Do nothing
};
const emptyOptionsProvider = (): Record<string, never> => ({});

export class InternalTextEditorOptionsBuilder {
    #locale = "en_US";
    #additional_options_provider: AdditionalOptionsProvider = emptyOptionsProvider;

    private constructor() {
        // Prefer static method for instantiation
    }

    static options(): InternalTextEditorOptionsBuilder {
        return new InternalTextEditorOptionsBuilder();
    }

    withLocale(locale: string): this {
        this.#locale = locale;
        return this;
    }

    withAdditionalOptionsProvider(provider: AdditionalOptionsProvider): this {
        this.#additional_options_provider = provider;
        return this;
    }

    build(): InternalTextEditorOptions {
        return {
            locale: this.#locale,
            onEditorInit: noop,
            onEditorDataReady: noop,
            onFormatChange: noop,
            getAdditionalOptions: this.#additional_options_provider,
        };
    }
}
