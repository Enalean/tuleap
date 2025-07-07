<!--
  - Copyright (c) Enalean, 2024-present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="empty-state-page">
        <div class="empty-state-illustration">
            <project-ongoing-creation-svg />
        </div>

        <h1 class="empty-state-title">
            {{ $gettext("Your project is being created.") }}
        </h1>
        <p class="empty-state-text" v-dompurify-html="message_creation"></p>

        <a class="tlp-button-primary tlp-button-large empty-state-action" href="/my/"
            ><i class="fa-solid fa-reply tlp-button-icon" aria-hidden="true"></i
            >{{ $gettext("Go to my home page") }}</a
        >
    </div>
</template>

<script setup lang="ts">
import ProjectOngoingCreationSvg from "./ProjectOngoingCreationSvg.vue";
import { onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { MinimalProjectRepresentation } from "../../../../../type";
import { useStore } from "../../../../../stores/root";
import { redirectToUrl } from "../../../../../helpers/location-helper";

const { $gettext } = useGettext();
const root_store = useStore();

onMounted(() => {
    if (root_store.is_project_approval_required) {
        return;
    }
    const match = /\/(\d+)$/.exec(location.href);
    if (match && match.length > 1) {
        pollProjectCreation(0, Number.parseInt(match[1], 10));
    }
});

const delays = [0, 500, 1000, 1000, 1500, 1500, 2000, 2000, 3000, 3000];

function scheduleProjectPolling(i: number, id: number): void {
    if (i > delays.length) {
        return;
    }

    setTimeout(() => {
        pollProjectCreation(i, id);
    }, delays[i]);
}

function pollProjectCreation(i: number, id: number): void {
    getProject(id).match(
        function (project: MinimalProjectRepresentation): void {
            const params = new URLSearchParams();
            params.set("should-display-created-project-modal", "true");

            redirectToUrl("/projects/" + encodeURIComponent(project.shortname) + "/?" + params);
        },
        function (fault: Fault): void {
            if ("isNotFound" in fault && fault.isNotFound()) {
                scheduleProjectPolling(i + 1, id);
            }
        },
    );
}

function getProject(id: number): ResultAsync<MinimalProjectRepresentation, Fault> {
    return getJSON<MinimalProjectRepresentation>(uri`/api/projects/${id}`);
}

const message_creation = $gettext("You will receive an email when the project is created.");
</script>
