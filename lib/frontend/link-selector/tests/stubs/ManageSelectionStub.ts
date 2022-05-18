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

import type { ManageSelection } from "../../src/selection/SelectionManager";

export type ManageSelectionStub = ManageSelection & {
    getProcessSelectionCallCount: () => number;
    getCurrentSelection: () => Element | null;
};

export const ManageSelectionStub = {
    withSelectedElement(element: Element): ManageSelectionStub {
        return buildWithSelectionState({ selected_element: element });
    },
    withNoSelection(): ManageSelectionStub {
        return buildWithSelectionState({ selected_element: null });
    },
};

function buildWithSelectionState(state: { selected_element: Element | null }): ManageSelectionStub {
    let process_selection_call_count = 0,
        currently_selected_element: Element | null = state.selected_element;

    return {
        processSelection: (item: Element): void => {
            process_selection_call_count++;
            currently_selected_element = item;
        },
        hasSelection: () => currently_selected_element !== null,
        getProcessSelectionCallCount: () => process_selection_call_count,
        getCurrentSelection: () => currently_selected_element,
    };
}
