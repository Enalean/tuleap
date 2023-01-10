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

    <form method="post" v-bind:action="server.restrict_url" class="tlp-pane">
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
                <allow-all-projects-checkbox v-bind:server="server" />
                <allowed-projects-table
                    v-if="server.is_project_restricted"
                    v-bind:server="server"
                    v-bind:set_nb_to_allow="setNbToAllow"
                    v-bind:set_nb_to_revoke="setNbToRevoke"
                    v-bind:set_nb_to_move="setNbToMove"
                />
                <csrf-token />
            </section>
            <section class="tlp-pane-section tlp-pane-section-submit">
                <div
                    class="tlp-alert-warning tlp-badge-outline"
                    v-if="nb_to_move"
                    data-test="warning-moved-project"
                >
                    {{
                        $gettext(
                            "Moving project from a server to another will induce loosing of modifications for users that are currently using the former."
                        )
                    }}
                </div>
                <div class="onlyoffice-admin-restrict-server-footer-actions">
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
                    <span class="onlyoffice-admin-restrict-server-footer-spacer"></span>
                    <button
                        type="reset"
                        class="tlp-button-primary tlp-button-outline tlp-modal-action"
                        v-on:click="navigation.cancelRestriction()"
                    >
                        {{ $gettext("Cancel") }}
                    </button>
                    <button
                        type="submit"
                        class="tlp-button-primary tlp-modal-action"
                        v-bind:disabled="nb_to_revoke + nb_to_allow === 0"
                        data-test="submit"
                    >
                        {{ $gettext("Save") }}
                    </button>
                </div>
            </section>
        </div>
    </form>
</template>
<script setup lang="ts">
import type { Server } from "../../../type";
import { ref } from "vue";
import AllowAllProjectsCheckbox from "./AllowAllProjectsCheckbox.vue";
import AllowedProjectsTable from "./AllowedProjectsTable.vue";
import CsrfToken from "../../CsrfToken.vue";
import { strictInject } from "../../../helpers/strict-inject";
import { NAVIGATION } from "../../../injection-keys";

const navigation = strictInject(NAVIGATION);

defineProps<{ server: Server }>();

const nb_to_allow = ref(0);
const nb_to_revoke = ref(0);
const nb_to_move = ref(0);

function setNbToAllow(nb: number): void {
    nb_to_allow.value = nb;
}

function setNbToRevoke(nb: number): void {
    nb_to_revoke.value = nb;
}

function setNbToMove(nb: number): void {
    nb_to_move.value = nb;
}
</script>

<style lang="scss" scoped>
h1 {
    padding: 0 460px 0 0;
}

.tlp-pane-section-submit {
    flex-direction: column;
    gap: var(--tlp-small-spacing);
}

.onlyoffice-admin-restrict-server-section {
    display: flex;
    flex-direction: column;
}

.onlyoffice-admin-restrict-server-footer-actions {
    display: flex;
    align-items: center;
    gap: var(--tlp-small-spacing);
    width: 100%;
}

.onlyoffice-admin-restrict-server-footer-spacer {
    flex: 1 0 auto;
}
</style>
