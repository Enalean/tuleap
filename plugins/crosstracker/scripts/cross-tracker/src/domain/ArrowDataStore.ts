/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

export type ArrowDataEntry = {
    readonly uuid: string;
    readonly element: HTMLElement;
    readonly caret: HTMLElement;
};

export type ArrowDataStore = {
    addEntry(uuid: string, element: HTMLElement, caret: HTMLElement): void;
    getByUUID(uuid: string): ArrowDataEntry | undefined;
};

export const ArrowDataStore = (): ArrowDataStore => {
    const arrow_data_collection: Array<ArrowDataEntry> = [];

    return {
        addEntry(uuid: string, element: HTMLElement, caret: HTMLElement): void {
            arrow_data_collection.push({ uuid, element, caret });
        },
        getByUUID(uuid: string): ArrowDataEntry | undefined {
            return arrow_data_collection.find((item) => item.uuid === uuid);
        },
    };
};
