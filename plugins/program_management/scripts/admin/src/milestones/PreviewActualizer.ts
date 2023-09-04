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

import { sprintf } from "sprintf-js";
import type { GettextProvider } from "../GettextProvider";
import { ReactiveLabel } from "../dom/ReactiveLabel";
import { ReactiveLabelCollection } from "../dom/ReactiveLabelCollection";
import type { RetrieveContainedNode } from "../dom/RetrieveContainedNode";
import type { TimeboxLabel } from "../dom/TimeboxLabel";

const LABEL_SELECTOR = "[data-timebox-label]";
const NEW_LABEL_SELECTOR = "[data-timebox-label-new]";
const EXAMPLE_LABEL_SELECTOR = "[data-timebox-label-example]";

export class PreviewActualizer {
    private constructor(
        private readonly gettext_provider: GettextProvider,
        private readonly timebox_label: ReactiveLabel,
        private readonly new_label: ReactiveLabel,
        private readonly example_labels: ReactiveLabelCollection,
        private readonly default_label_value: string,
        private readonly default_sub_label_value: string,
    ) {}

    static fromContainerAndTimeboxLabels(
        gettext_provider: GettextProvider,
        retriever: RetrieveContainedNode,
        label_input: TimeboxLabel,
        sub_label_input: TimeboxLabel,
        default_label_value: string,
        default_sub_label_value: string,
    ): PreviewActualizer {
        const timebox_label = ReactiveLabel.fromSelectorAndTimeboxLabel(
            retriever,
            LABEL_SELECTOR,
            label_input,
        );
        const new_label = ReactiveLabel.fromSelectorAndTimeboxLabel(
            retriever,
            NEW_LABEL_SELECTOR,
            sub_label_input,
        );
        const example_labels = ReactiveLabelCollection.fromSelectorAndTimeboxLabel(
            retriever,
            EXAMPLE_LABEL_SELECTOR,
            sub_label_input,
        );
        return new PreviewActualizer(
            gettext_provider,
            timebox_label,
            new_label,
            example_labels,
            default_label_value,
            default_sub_label_value,
        );
    }

    private defaultSubLabelValue(value: string): string {
        return value !== "" ? value : this.default_sub_label_value;
    }

    initTimeboxPreview(): void {
        this.timebox_label.reactOnLabelChange((value: string) =>
            value !== "" ? value : this.default_label_value,
        );
        this.new_label.reactOnLabelChange((text) =>
            sprintf(this.gettext_provider.gettext("New %s"), this.defaultSubLabelValue(text)),
        );
        this.example_labels.reactOnLabelChange((text, index, length) => {
            const defaulted_value = this.defaultSubLabelValue(text);
            const decreasing_iteration_number = length - index;
            return `${defaulted_value} ${decreasing_iteration_number}`;
        });
    }

    stopTimeboxPreview(): void {
        this.timebox_label.stopReacting();
        this.new_label.stopReacting();
        this.example_labels.stopReacting();
    }
}
