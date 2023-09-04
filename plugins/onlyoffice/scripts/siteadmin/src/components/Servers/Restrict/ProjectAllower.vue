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
        <input
            type="hidden"
            name="project-to-add"
            v-model="project_to_add_id"
            v-if="project_to_add_id"
            data-test="project-to-add"
        />

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
        <move-project-confirmation-modal
            v-if="show_move_project_modal"
            v-bind:cancel="show_move_project_modal.cancelMove"
            v-bind:move="show_move_project_modal.move"
            v-bind:previous_server="show_move_project_modal.previous_server"
            v-bind:project="show_move_project_modal.project"
            v-bind:new_server="server"
        />
    </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref } from "vue";
import { extractProjectnameFromAutocompleterResult } from "../../../helpers/extract-projectname-from-autocompleter-result";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { Project, ProjectFromRest, Server } from "../../../type";
import { useGettext } from "vue3-gettext";
import { autocomplete_projects_for_select2 } from "@tuleap/autocomplete-for-select2";
import MoveProjectConfirmationModal from "./MoveProjectConfirmationModal.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CONFIG } from "../../../injection-keys";

const config = strictInject(CONFIG);

const props = defineProps<{
    error: (message: string) => void;
    server: Server;
}>();

const select = ref<HTMLSelectElement | null>(null);
const is_loading = ref(false);
const project_to_add_id = ref(0);

interface ShowMoveProjectModal {
    readonly move: () => void;
    readonly cancelMove: () => void;
    readonly project: Project;
    readonly previous_server: Server;
}

const show_move_project_modal = ref<ShowMoveProjectModal | null>(null);

onMounted(() => {
    if (select.value) {
        autocomplete_projects_for_select2(select.value, {
            include_private_projects: true,
        });
    }
});

function resetSelectField(): void {
    if (select.value) {
        select.value.value = "";
        select.value.dispatchEvent(new Event("change"));
    }
}

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
            const project = {
                id,
                label,
                url: `/projects/${encodeURIComponent(shortname)}`,
            };
            function addProject(): void {
                project_to_add_id.value = project.id;
                nextTick(() => {
                    if (select.value) {
                        select.value.form?.submit();
                    }
                });
            }

            const previous_server = config.servers.find(
                (server) =>
                    server.id !== props.server.id &&
                    server.project_restrictions.some(
                        (already_allowed_project) => project.id === already_allowed_project.id,
                    ),
            );
            if (previous_server) {
                show_move_project_modal.value = {
                    project,
                    previous_server,
                    move: (): void => {
                        show_move_project_modal.value = null;
                        addProject();
                    },
                    cancelMove: (): void => {
                        if (show_move_project_modal.value) {
                            resetSelectField();
                            show_move_project_modal.value = null;
                            is_loading.value = false;
                        }
                    },
                };
            } else {
                addProject();
            }
        },
        (): void => {
            setError();
            is_loading.value = false;
        },
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
