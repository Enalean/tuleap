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

import type { CreatePlaceholderWidget } from "../../src/app/file-diff/widgets/side-by-side-code-mirror-widget-creator";
import type { PlaceholderCreationParams } from "../../src/app/file-diff/types-codemirror-overriden";

export interface StubCreatePlaceholderWidget {
    build: () => CreatePlaceholderWidget;
    getLastCreationParametersReceived: () => PlaceholderCreationParams | null;
    getNbCalls: () => number;
}

export const CreatePlaceholderWidgetStub = (): StubCreatePlaceholderWidget => {
    let last_call_params: PlaceholderCreationParams | null = null;
    let nb_calls = 0;

    return {
        build: (): CreatePlaceholderWidget => ({
            displayPlaceholderWidget: (widget_params: PlaceholderCreationParams): void => {
                last_call_params = widget_params;
                nb_calls++;
            },
        }),
        getLastCreationParametersReceived: () => last_call_params,
        getNbCalls: () => nb_calls,
    };
};
