<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
    <div class="tlp-framed-horizontally">
        <div class="tlp-alert-danger">
            {{ $gettext("An error occurred while searching for results.") }}
            <blockquote class="document-search-error-quote" v-if="error_message.length > 0">
                {{ error_message }}
            </blockquote>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

interface I18NWrapperError {
    readonly error: {
        readonly code?: number;
        readonly message?: string;
        readonly i18n_error_message?: string;
    };
}

const props = defineProps<{ error: Error }>();

const error_message = ref("");

watch(
    () => props.error,
    async (): Promise<void> => {
        if (isFetchWrapperError(props.error)) {
            error_message.value = await getErrorMessageFromError(props.error);
            if (error_message.value) {
                return;
            }
        }

        error_message.value = props.error.message;
    },
    { immediate: true },
);

function isFetchWrapperError(error: Error): error is FetchWrapperError {
    return "response" in error;
}

async function getErrorMessageFromError(error: FetchWrapperError): Promise<string> {
    try {
        const error_body: I18NWrapperError = await error.response.json();

        if (!error_body.error) {
            return "";
        }

        if (error_body.error.i18n_error_message) {
            return error_body.error.i18n_error_message;
        }

        if (error_body.error.code && error_body.error.message) {
            return error_body.error.code + " " + error_body.error.message;
        }

        return "";
    } catch (e) {
        return "";
    }
}
</script>
