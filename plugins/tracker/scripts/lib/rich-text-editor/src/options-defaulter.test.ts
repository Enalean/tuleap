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

import type { RichTextEditorOptions } from "./types";
import { defaultOptionsIfNotProvided } from "./options-defaulter";

describe(`options-defaulter`, () => {
    it(`given RichTextEditorOptions, it defaults callbacks to "no operation" functions`, () => {
        const options_without_callbacks: RichTextEditorOptions = {
            format_selectbox_id: "irrelevant",
            format_selectbox_name: "irrelevant",
        };

        const defaulted_options = defaultOptionsIfNotProvided("fr_FR", options_without_callbacks);

        expect(defaulted_options.locale).toBe("fr_FR");
        expect(defaulted_options.onFormatChange).toBeDefined();
        const doc = document.implementation.createHTMLDocument();
        const textarea = doc.createElement("textarea");
        expect(defaulted_options.getAdditionalOptions(textarea)).toEqual({});
        expect(defaulted_options.onEditorInit).toBeDefined();
        expect(defaulted_options.onEditorDataReady).toBeDefined();
    });

    it(`given RichTextEditorOptions with callbacks, it does not change them`, () => {
        const options: RichTextEditorOptions = {
            format_selectbox_id: "irrelevant",
            format_selectbox_name: "irrelevant",
            onFormatChange: () => {
                // Do something with new_format
            },
            getAdditionalOptions: () => ({
                extraPlugins: "uploadimage",
                uploadUrl: "/upload/url",
            }),
            onEditorInit: () => {
                // Do something with ckeditor and textarea
            },
            onEditorDataReady: () => {
                // Init @tuleap/mention
            },
        };

        const defaulted_options = defaultOptionsIfNotProvided("fr_FR", options);
        expect(defaulted_options.onEditorInit).toBe(options.onEditorInit);
        expect(defaulted_options.onFormatChange).toBe(options.onFormatChange);
        expect(defaulted_options.getAdditionalOptions).toBe(options.getAdditionalOptions);
        expect(defaulted_options.onEditorDataReady).toBe(options.onEditorDataReady);
    });
});
