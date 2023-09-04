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

import type { Option } from "@tuleap/option";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import type { DispatchEvents } from "../DispatchEvents";
import type { FileUploadSetup } from "../fields/file-field/FileUploadSetup";
import { WillGetFileUploadSetup } from "../fields/file-field/WillGetFileUploadSetup";
import type { WillDisableSubmit } from "../submit/WillDisableSubmit";
import { WillEnableSubmit } from "../submit/WillEnableSubmit";
import type { DidUploadImage } from "../fields/file-field/DidUploadImage";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { InterpretCommonMark } from "./InterpretCommonMark";

export type FormattedTextControllerType = {
    getDefaultTextFormat(): TextFieldFormat;
    getFileUploadSetup(): Option<FileUploadSetup>;
    interpretCommonMark(commonmark: string): ResultAsync<string, Fault>;
    onFileUploadStart(event: WillDisableSubmit): void;
    onFileUploadError(): void;
    onFileUploadSuccess(event: DidUploadImage): void;
};

export const FormattedTextController = (
    event_dispatcher: DispatchEvents,
    commonmark_retriever: InterpretCommonMark,
    default_text_format: TextFieldFormat,
): FormattedTextControllerType => ({
    getDefaultTextFormat: () => default_text_format,

    getFileUploadSetup(): Option<FileUploadSetup> {
        const event = WillGetFileUploadSetup();
        event_dispatcher.dispatch(event);
        return event.setup;
    },

    interpretCommonMark: commonmark_retriever.interpretCommonMark,

    onFileUploadStart(event): void {
        event_dispatcher.dispatch(event);
    },

    onFileUploadError(): void {
        event_dispatcher.dispatch(WillEnableSubmit());
    },

    onFileUploadSuccess(event): void {
        event_dispatcher.dispatch(WillEnableSubmit(), event);
    },
});
