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

export interface Iteration {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
    status: string;
    user_can_update: boolean;
}

export interface State {
    iterations_content: Map<number, UserStory[]>;
}

export interface Element {
    readonly background_color: string;
    readonly is_open: boolean;
    readonly id: number;
    readonly uri: string;
    readonly xref: string;
    readonly title: string;
    readonly tracker: TrackerMinimalRepresentation;
    readonly project: ProjectMinimalRepresentation;
}

export interface UserStory extends Element {
    feature: Feature | null;
}

export type Feature = Element;

interface TrackerMinimalRepresentation {
    readonly color_name: string;
}

interface ProjectMinimalRepresentation {
    readonly id: number;
    readonly uri: string;
    readonly label: string;
    readonly icon: string;
}
