/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
import type { ColorName } from "@tuleap/core-constants";
import type { ColumnName } from "./ColumnName";

export const DATE_CELL = "date";
export const NUMERIC_CELL = "numeric";
export const TEXT_CELL = "text";
export const USER_CELL = "user";
export const PROJECT_CELL = "project";
export const TRACKER_CELL = "tracker";
export const PRETTY_TITLE_CELL = "pretty_title";

type DateCell = {
    readonly type: typeof DATE_CELL;
    readonly value: Option<string>;
    readonly with_time: boolean;
};

type NumericCell = {
    readonly type: typeof NUMERIC_CELL;
    readonly value: Option<number>;
};

type TextCell = {
    readonly type: typeof TEXT_CELL;
    readonly value: string;
};

type UserCell = {
    readonly type: typeof USER_CELL;
    readonly display_name: string;
    readonly avatar_uri: string;
    readonly user_uri: Option<string>;
};

type ProjectCell = {
    readonly type: typeof PROJECT_CELL;
    readonly name: string;
    readonly icon: string;
};

export type TrackerCell = {
    readonly type: typeof TRACKER_CELL;
    readonly name: string;
    readonly color: ColorName;
};

export type PrettyTitleCell = {
    readonly type: typeof PRETTY_TITLE_CELL;
    readonly tracker_name: string;
    readonly color: ColorName;
    readonly artifact_id: number;
    readonly title: string;
};

export type Cell =
    | DateCell
    | NumericCell
    | TextCell
    | UserCell
    | ProjectCell
    | TrackerCell
    | PrettyTitleCell;

export type ArtifactRow = {
    readonly uri: string;
    readonly cells: Map<ColumnName, Cell>;
};

export type ArtifactsTable = {
    readonly columns: Set<ColumnName>;
    readonly rows: ReadonlyArray<ArtifactRow>;
};
