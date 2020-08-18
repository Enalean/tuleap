/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { MinimalTracker } from "./tracker";

interface BaseTrackerFieldValue {
    field_id: number;
    label: string;
}

export interface StringValueField extends BaseTrackerFieldValue {
    type: "string";
    value: string;
}

export interface TextValueField extends BaseTrackerFieldValue {
    type: "text";
    format: "text" | "html";
    value: string;
}

interface NumberValueField extends BaseTrackerFieldValue {
    type: "float" | "int";
    value: null | number;
}

interface ComputedValueWithAutomaticValueField extends BaseTrackerFieldValue {
    type: "computed";
    is_autocomputed: true;
    value: null | number;
}

interface ComputedValueWithManualValueField extends BaseTrackerFieldValue {
    type: "computed";
    is_autocomputed: false;
    manual_value: null | number;
}

interface SubmittedOnValueField extends BaseTrackerFieldValue {
    type: "subon";
    value: string;
}

interface LastUpdateOnValueField extends BaseTrackerFieldValue {
    type: "lud";
    value: string;
}

interface DateValueField extends BaseTrackerFieldValue {
    type: "date";
    value: string | null;
}

interface OtherNonSupportedFieldValue extends BaseTrackerFieldValue {
    type: never;
}

export type TrackerFieldValue =
    | StringValueField
    | TextValueField
    | NumberValueField
    | ComputedValueWithAutomaticValueField
    | ComputedValueWithManualValueField
    | SubmittedOnValueField
    | LastUpdateOnValueField
    | DateValueField
    | OtherNonSupportedFieldValue;

export interface Artifact {
    id: number;
    values: TrackerFieldValue[];
    values_by_field: {
        [field_name: string]: TrackerFieldValue;
    };
    tracker: MinimalTracker;
}
