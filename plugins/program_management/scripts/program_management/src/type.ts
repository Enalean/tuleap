/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import type { ProgramIncrement } from "./helpers/ProgramIncrement/program-increment-retriever";

export interface ProgramElement {
    artifact_id: number;
    artifact_title: string;
    artifact_xref: string;
    tracker: TrackerMinimalRepresentation;
    background_color: string;
}

export interface TrackerMinimalRepresentation {
    id: number;
    uri: string;
    label: string;
    color_name: string;
}

export interface HandleDragPayload {
    readonly dropped_card: HTMLElement;
    readonly target_cell: HTMLElement;
    readonly source_cell: HTMLElement;
}

export interface State {
    to_be_planned_elements: ProgramElement[];
    program_increments: ProgramIncrement[];
    modal_error_message: string;
    has_modal_error: boolean;
}
