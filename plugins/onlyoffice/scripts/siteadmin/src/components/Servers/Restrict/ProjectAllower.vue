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
    <div class="tlp-form-element-append">
        <select
            class="tlp-select onlyoffice-admin-restrict-server-select-project"
            v-bind:data-placeholder="$gettext('Project name')"
            ref="select"
        ></select>
        <button
            type="button"
            id="allow-project"
            class="tlp-append tlp-button-primary"
            v-on:click="onClick"
            data-test="button"
        >
            <i
                class="fa-solid fa-circle-check tlp-button-icon"
                v-bind:class="{ 'fa-circle-notch fa-spin': is_loading }"
                aria-hidden="true"
            ></i>
            {{ $gettext("Allow project") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { extractProjectnameFromAutocompleterResult } from "../../../helpers/extract-projectname-from-autocompleter-result";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { Project, ProjectFromRest } from "../../../type";
import { useGettext } from "vue3-gettext";
import { autocomplete_projects_for_select2 } from "@tuleap/autocomplete-for-select2";

const props = defineProps<{ add: (project: Project) => void; error: (message: string) => void }>();

const select = ref<HTMLSelectElement | null>(null);
const is_loading = ref(false);

onMounted(() => {
    if (select.value) {
        autocomplete_projects_for_select2(select.value, {
            include_private_projects: true,
        });
    }
});

function onClick(): void {
    if (!select.value) {
        return;
    }

    const shortname = extractProjectnameFromAutocompleterResult(select.value.value);

    resetError();
    is_loading.value = true;

    getJSON<ReadonlyArray<ProjectFromRest>>(uri`/api/projects`, {
        params: {
            query: JSON.stringify({ shortname }),
        },
    }).match(
        (matching_projects: ReadonlyArray<ProjectFromRest>): void => {
            if (matching_projects.length !== 1) {
                setError();
                return;
            }

            const { id, shortname, label } = matching_projects[0];
            props.add({
                id,
                label,
                url: `/projects/${encodeURIComponent(shortname)}`,
            });
            is_loading.value = false;
            if (select.value) {
                select.value.value = "";
                select.value.dispatchEvent(new Event("change"));
            }
        },
        (): void => {
            setError();
            is_loading.value = false;
        }
    );
}

const { $gettext } = useGettext();

function setError(): void {
    props.error($gettext("Unable to find project information"));
}

function resetError(): void {
    props.error("");
}
</script>

<style lang="scss">
.onlyoffice-admin-restrict-server-select-project {
    width: 200px;
}
</style>
