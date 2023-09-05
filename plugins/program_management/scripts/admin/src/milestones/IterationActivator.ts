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

import type { TimeboxLabel } from "../dom/TimeboxLabel";
import type { TrackerSelector } from "../dom/TrackerSelector";
import type { PreviewActualizer } from "./PreviewActualizer";

export class IterationActivator {
    constructor(
        private readonly iteration_label: TimeboxLabel,
        private readonly iteration_sub_label: TimeboxLabel,
        private readonly iteration_selector: TrackerSelector,
        private readonly actualizer: PreviewActualizer,
    ) {}

    watchIterationSelection(): void {
        if (!this.iteration_selector.hasSelection()) {
            this.iteration_label.disable();
            this.iteration_sub_label.disable();
        } else {
            this.actualizer.initTimeboxPreview();
        }
        this.iteration_selector.addChangeListener((has_selection) => {
            if (has_selection) {
                this.iteration_label.enable();
                this.iteration_sub_label.enable();
                this.actualizer.initTimeboxPreview();
                return;
            }
            this.iteration_label.disable();
            this.iteration_sub_label.disable();
            this.actualizer.stopTimeboxPreview();
        });
    }
}
