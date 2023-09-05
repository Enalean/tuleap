/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { CreateNewInlineCommentFormWidget } from "../../src/app/file-diff/widgets/SideBySideCodeMirrorWidgetCreator";
import type { NewInlineCommentFormWidgetCreationParams } from "../../src/app/file-diff/types-codemirror-overriden";

export interface StubCreateNewInlineCommentFormWidget {
    build: () => CreateNewInlineCommentFormWidget;
    getLastCreationParametersReceived: () => NewInlineCommentFormWidgetCreationParams | null;
    getNbCalls: () => number;
}

export const CreateNewInlineCommentFormWidgetStub = (): StubCreateNewInlineCommentFormWidget => {
    let last_call_params: NewInlineCommentFormWidgetCreationParams | null = null;
    let nb_calls = 0;

    return {
        build: (): CreateNewInlineCommentFormWidget => ({
            displayNewInlineCommentFormWidget: (
                widget_params: NewInlineCommentFormWidgetCreationParams,
            ): void => {
                last_call_params = widget_params;
                nb_calls++;
            },
        }),
        getLastCreationParametersReceived: () => last_call_params,
        getNbCalls: () => nb_calls,
    };
};
