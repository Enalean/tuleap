/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

type SubtaskLoadingStatus = "nope" | "loading" | "loaded" | "error" | "empty";
export const SUBTASKS_WAITING_TO_BE_LOADED: SubtaskLoadingStatus = "nope";
export const SUBTASKS_ARE_LOADING: SubtaskLoadingStatus = "loading";
export const SUBTASKS_ARE_LOADED: SubtaskLoadingStatus = "loaded";
export const SUBTASKS_ARE_IN_ERROR: SubtaskLoadingStatus = "error";
export const SUBTASKS_ARE_EMPTY: SubtaskLoadingStatus = "empty";

export interface Project {
    readonly id: number;
    readonly label: string;
}

export interface Task {
    readonly id: number;
    readonly subtasks_uri: string;
    readonly project: Project;
    readonly title: string;
    readonly xref: string;
    readonly color_name: string;
    readonly progress: number | null;
    readonly progress_error_message: string;
    readonly html_url: string;
    readonly start: Date | null;
    readonly end: Date | null;
    readonly dependencies: Record<string, number[]>;
    readonly is_milestone: boolean;
    readonly has_subtasks: boolean;
    readonly subtasks_loading_status: SubtaskLoadingStatus;
    readonly is_expanded: boolean;
    readonly subtasks: Task[];
    readonly parent: Task | undefined;
    readonly are_dates_implied: boolean;
    readonly time_period_error_message: string;
}

export type TimeScale = "month" | "quarter" | "week";

export interface TimePeriod {
    readonly units: Date[];
    formatShort(unit: Date): string;
    formatLong(unit: Date): string;
    additionalUnits(nb: number): Date[];
    getEvenOddClass(unit: Date): string;
}

export interface TaskDimension {
    readonly left: number;
    readonly width: number;
    readonly index: number;
}
export class TaskDimensionMap extends WeakMap<Task, TaskDimension> {}

export class TasksByNature extends Map<string, Task[]> {}
export class TasksDependencies extends WeakMap<Task, TasksByNature> {}

export class NaturesLabels extends Map<string, string> {}

export class NbUnitsPerYear extends Map<number, number> {}

export interface TaskRow {
    readonly task: Task;
}

export interface SkeletonRow {
    readonly for_task: Task;
    readonly is_skeleton: true;
    readonly is_last_one: boolean;
}

export interface ErrorRow {
    readonly for_task: Task;
    readonly is_error: true;
}

export interface EmptySubtasksRow {
    readonly for_task: Task;
    readonly is_empty: true;
}

export interface SubtaskRow {
    readonly parent: Task;
    readonly subtask: Task;
    readonly is_last_one: boolean;
}

export type Row = TaskRow | SkeletonRow | EmptySubtasksRow | ErrorRow | SubtaskRow;

export interface Iteration {
    readonly id: number;
    readonly start: Date;
    readonly end: Date;
    readonly title: string;
    readonly html_url: string;
}

export type IterationLevel = 1 | 2;
