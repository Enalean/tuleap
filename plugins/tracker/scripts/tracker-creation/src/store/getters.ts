/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { CreationOptions, State } from "./type";

export const is_ready_for_step_2 = (state: State): boolean => {
    if (state.active_option === CreationOptions.NONE_YET) {
        return false;
    }

    return isDuplicationReady(state) || isXmlImportReady(state);
};

function isDuplicationReady(state: State): boolean {
    return (
        state.active_option === CreationOptions.TRACKER_TEMPLATE &&
        state.selected_tracker_template !== null
    );
}

function isXmlImportReady(state: State): boolean {
    return (
        state.active_option === CreationOptions.TRACKER_XML_FILE &&
        state.is_a_xml_file_selected &&
        state.has_xml_file_error === false &&
        state.is_parsing_a_xml_file === false
    );
}

export const is_ready_to_submit = (state: State): boolean => {
    return (
        state.tracker_to_be_created.name.length > 0 &&
        state.tracker_to_be_created.shortname.length > 0
    );
};

export const is_a_duplication = (state: State): boolean => {
    return state.active_option === CreationOptions.TRACKER_TEMPLATE;
};

export const is_a_xml_import = (state: State): boolean => {
    return state.active_option === CreationOptions.TRACKER_XML_FILE;
};
