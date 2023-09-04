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

import type { TimeboxLabel } from "./TimeboxLabel";
import type { RetrieveContainedNode } from "./RetrieveContainedNode";

export type LabelTextCallback = (text: string, label_index: number, length: number) => string;

export class ReactiveLabelCollection {
    private constructor(
        private readonly nodes: Node[],
        private readonly label_input: TimeboxLabel,
    ) {}

    static fromSelectorAndTimeboxLabel(
        retriever: RetrieveContainedNode,
        selector: string,
        label_input: TimeboxLabel,
    ): ReactiveLabelCollection {
        return new ReactiveLabelCollection(retriever.getAllNodesBySelector(selector), label_input);
    }

    reactOnLabelChange(labelTextCallback: LabelTextCallback): void {
        this.onChange(this.label_input.value, labelTextCallback);
        this.label_input.addInputListener((input_value) => {
            return this.onChange(input_value, labelTextCallback);
        });
    }

    private onChange(input_value: string, labelTextCallback: LabelTextCallback): void {
        for (let i = 0; i < this.nodes.length; i++) {
            this.nodes[i].textContent = labelTextCallback(input_value, i, this.nodes.length);
        }
    }

    stopReacting(): void {
        this.label_input.removeInputListeners();
    }
}
