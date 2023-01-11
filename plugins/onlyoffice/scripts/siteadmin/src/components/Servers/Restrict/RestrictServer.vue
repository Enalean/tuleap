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
    <h1>{{ server.server_url }}</h1>

    <form method="post" v-bind:action="server.restrict_url" class="tlp-pane" ref="form">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    {{ $gettext("Projects restriction") }}
                </h1>
            </div>
            <section class="tlp-pane-section onlyoffice-admin-restrict-server-section">
                <p>
                    {{ $gettext("Define which projects will be able to use the server:") }}
                    <span class="tlp-badge-secondary">{{ server.server_url }}</span>
                </p>
                <csrf-token />
                <allow-all-projects-checkbox v-bind:server="server" />
                <allowed-projects-table
                    v-if="server.is_project_restricted"
                    v-bind:server="server"
                    v-bind:submit="submit"
                />
            </section>
        </div>
    </form>
</template>
<script setup lang="ts">
import type { Server } from "../../../type";
import AllowAllProjectsCheckbox from "./AllowAllProjectsCheckbox.vue";
import AllowedProjectsTable from "./AllowedProjectsTable.vue";
import CsrfToken from "../../CsrfToken.vue";
import { ref } from "vue";

defineProps<{ server: Server }>();

const form = ref<HTMLFormElement | null>(null);

function submit(): void {
    if (form.value) {
        form.value.submit();
    }
}
</script>

<style lang="scss" scoped>
h1 {
    padding: 0 460px 0 0;
}

.onlyoffice-admin-restrict-server-section {
    display: flex;
    flex-direction: column;
}
</style>
