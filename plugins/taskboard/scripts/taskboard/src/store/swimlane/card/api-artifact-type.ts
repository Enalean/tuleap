/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

export enum TextFormat {
    TEXT = "text",
    HTML = "html",
}

export interface TextValue {
    readonly content: string;
    readonly format: TextFormat;
}

export interface Field {
    readonly field_id: number;
}

export interface TextField extends Field {
    readonly value: TextValue | string;
}

export interface ListField extends Field {
    readonly bind_value_ids: number[];
}

export interface Link {
    readonly id: number;
    readonly type: string;
}

export interface LinkField extends Field {
    readonly links: Link[];
}

export interface PutBody {
    readonly values: Field[];
}

export interface TrackerReference {
    readonly id: number;
}

export type Values = Array<TextField | ListField | LinkField>;

export interface PostBody {
    readonly tracker: TrackerReference;
    readonly values: Values;
}
