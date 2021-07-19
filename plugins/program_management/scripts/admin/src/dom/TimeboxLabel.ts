/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { RetrieveElement } from "./RetrieveElement";

export type InputCallback = (value: string) => void;

export class TimeboxLabel {
    private constructor(private readonly input: HTMLInputElement) {}

    get value(): string {
        return this.input.value;
    }

    addInputListener(callback: InputCallback): void {
        this.input.addEventListener("input", () => {
            callback(this.value);
        });
    }

    static fromId(retriever: RetrieveElement, id: string): TimeboxLabel {
        return new TimeboxLabel(retriever.getInputById(id));
    }
}
