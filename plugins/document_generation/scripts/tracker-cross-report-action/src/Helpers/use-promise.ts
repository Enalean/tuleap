/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import { ref, shallowRef, unref, watch } from "vue";

export function usePromise<Data>(
    default_data: Data,
    promise: Ref<Promise<Data>> | Promise<Data>,
): UsePromiseResult<Data> {
    const is_processing = ref(true);
    const data = shallowRef(default_data);
    const error = ref<Error | null>(null);

    watch(
        () => unref(promise),
        (promise) => {
            is_processing.value = true;
            error.value = null;
            promise
                .then((retrieved_data) => {
                    is_processing.value = false;
                    data.value = retrieved_data;
                })
                .catch((retrieved_error) => {
                    is_processing.value = false;
                    error.value = retrieved_error;
                });
        },
        { immediate: true },
    );

    return {
        is_processing,
        data,
        error,
    };
}

interface UsePromiseResult<Data> {
    readonly is_processing: Ref<boolean>;
    readonly data: Ref<Data>;
    readonly error: Ref<Error | null>;
}
