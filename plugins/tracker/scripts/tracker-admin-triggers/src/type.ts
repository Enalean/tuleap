/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

interface TriggerFieldValue {
    readonly id: number;
    readonly label: string;
    readonly is_hidden: boolean;
}
export interface TriggerField {
    readonly id: number;
    readonly name: string;
    readonly label: string;
    readonly values: ReadonlyArray<TriggerFieldValue>;
}
type TrackerId = number;
interface Tracker {
    readonly id: TrackerId;
    readonly name: string;
    readonly fields: ReadonlyArray<TriggerField>;
}

export interface BuilderData {
    readonly targets: Record<number, TriggerField>;
    readonly triggers: Record<TrackerId, Tracker>;
}

export interface SubmitData {
    readonly target: {
        readonly field_id: string;
        readonly field_value_id: string;
        readonly field_label: string;
        readonly field_value_label: string;
    };
    readonly condition: string;
    readonly triggering_fields: ReadonlyArray<FieldValue>;
}

interface FieldValue {
    readonly tracker_name: string;
    readonly field_id: number;
    readonly field_label: string;
    readonly field_value_id: number;
    readonly field_value_label: string;
}

export type ExistingTriggers = ReadonlyArray<{
    readonly id: number;
    readonly target: FieldValue;
    readonly condition: string;
    readonly triggering_fields: ReadonlyArray<FieldValue>;
}>;

export interface TriggeringField {
    id: number;
    isInitialCondition(): boolean;
    remove(): void;
    removeAllOptions(): void;
    activateDeleteButton(factory: TriggeringFieldFactory): void;
    removeDeleteButton(): void;
    addConditionSelector(): void;
    makeOperatorDynamic(): void;
    toJSON(): SubmitData["triggering_fields"][0] | null;
}

export interface TriggeringFieldFactory {
    triggering_fields: Array<TriggeringField>;
    addTriggeringField(builder_data: BuilderData): TriggeringField;
    removeTriggeringField(id: number): void;
    reset(): void;
    getTriggeringFieldsAsJSON(): SubmitData["triggering_fields"] | null;
}
