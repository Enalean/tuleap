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
    <div
        class="tlp-modal"
        v-bind:class="{ 'tlp-modal-medium-sized': server.is_project_restricted }"
        role="dialog"
        v-bind:aria-labelledby="'onlyoffice-admin-restrict-server-title-' + server.id"
        ref="root"
    >
        <div class="tlp-modal-header">
            <h1
                class="tlp-modal-title"
                v-bind:id="'onlyoffice-admin-restrict-server-title-' + server.id"
            >
                {{ $gettext("Projects restriction") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{ $gettext("Define which projects will be able to use the server:") }}
                <code>{{ server.server_url }}</code>
            </p>
            <allow-all-projects-checkbox v-bind:server="server" />
            <allowed-projects-table
                v-if="server.is_project_restricted"
                v-bind:server="server"
                v-bind:set_nb_to_allow="setNbToAllow"
                v-bind:set_nb_to_revoke="setNbToRevoke"
            />
        </div>
        <div class="tlp-modal-footer onlyoffice-admin-restrict-server-modal-footer">
            <span class="tlp-badge-success tlp-badge-outline" v-if="nb_to_allow">
                {{
                    $ngettext(
                        `%{ nb } project to allow`,
                        `%{ nb } projects to allow`,
                        nb_to_allow,
                        { nb: String(nb_to_allow) }
                    )
                }}
            </span>
            <span class="tlp-badge-danger tlp-badge-outline" v-if="nb_to_revoke">
                {{
                    $ngettext(
                        `%{ nb } project to revoke`,
                        `%{ nb } projects to revoke`,
                        nb_to_revoke,
                        { nb: String(nb_to_revoke) }
                    )
                }}
            </span>
            <span class="onlyoffice-admin-restrict-server-modal-footer-spacer"></span>
            <button
                type="reset"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Close") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { Server } from "../../type";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { onMounted, onUnmounted, ref } from "vue";
import emitter from "../../helpers/emitter";
import AllowAllProjectsCheckbox from "./Restrict/AllowAllProjectsCheckbox.vue";
import AllowedProjectsTable from "./Restrict/AllowedProjectsTable.vue";

const props = defineProps<{ server: Server }>();

let modal: Modal | null = null;
const root = ref<HTMLElement | null>(null);
const nb_to_allow = ref(0);
const nb_to_revoke = ref(0);

function setNbToAllow(nb: number): void {
    nb_to_allow.value = nb;
}

function setNbToRevoke(nb: number): void {
    nb_to_revoke.value = nb;
}

function showModal(server: Server): void {
    if (server.id !== props.server.id) {
        return;
    }

    if (!root.value) {
        return;
    }

    if (!modal) {
        modal = createModal(root.value);
    }

    modal.show();
}

onMounted(() => {
    emitter.on("show-restrict-server-modal", showModal);
});
onUnmounted(() => {
    emitter.off("show-restrict-server-modal", showModal);
});
</script>

<style lang="scss">
.onlyoffice-admin-restrict-server-modal-footer {
    align-items: center;
    gap: calc(var(--tlp-medium-spacing) / 2);
}

.onlyoffice-admin-restrict-server-modal-footer-spacer {
    flex: 1 0 auto;
}
</style>
