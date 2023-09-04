/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import type { Ref } from "vue";
import { onScopeDispose, watch } from "vue";
import type { ListPicker, ListPickerOptions } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import "@tuleap/list-picker/style";

export function useListPicker(
    target: Ref<HTMLSelectElement | undefined>,
    options: ListPickerOptions,
): void {
    let list_picker: ListPicker | null = null;

    const cleanup = (): void => {
        if (list_picker !== null) {
            list_picker.destroy();
        }
    };

    const stop_watching = watch(
        () => target.value,
        (source_select_box: HTMLSelectElement | undefined): void => {
            cleanup();

            if (source_select_box) {
                list_picker = createListPicker(source_select_box, options);
            }
        },
        { immediate: true, flush: "post" },
    );

    onScopeDispose((): void => {
        stop_watching();
        cleanup();
    });
}
