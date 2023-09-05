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
    <tr>
        <td>
            {{ server.server_url }}
            <span class="tlp-badge-warning" v-if="!server.has_existing_secret">
                <i class="fa-solid fa-triangle-exclamation tlp-badge-icon" aria-hidden="true"></i>
                {{ $gettext("Secret is missing") }}
            </span>
        </td>
        <td>
            <span class="tlp-badge-secondary" v-if="server.is_project_restricted">
                {{
                    $ngettext(
                        "%{ nb } project can use the server",
                        "%{ nb } projects can use the server",
                        server.project_restrictions.length,
                        { nb: String(server.project_restrictions.length) },
                    )
                }}
            </span>
            <span class="tlp-badge-success" v-else>
                <i class="fa-solid fa-check tlp-badge-icon" aria-hidden="true"></i>
                {{ $gettext("All projects can use the server") }}
            </span>
        </td>
        <td class="tlp-table-cell-actions">
            <edit-server-button v-bind:server="server" />
            <restrict-server-button v-bind:server="server" />
            <delete-server-button v-bind:server="server" />

            <edit-server-modal v-bind:server="server" />
            <delete-server-modal v-bind:server="server" />
        </td>
    </tr>
</template>

<script setup lang="ts">
import EditServerButton from "./EditServerButton.vue";
import RestrictServerButton from "./Restrict/RestrictServerButton.vue";
import DeleteServerButton from "./DeleteServerButton.vue";
import EditServerModal from "./EditServerModal.vue";
import DeleteServerModal from "./DeleteServerModal.vue";
import type { Server } from "../../type";

defineProps<{ server: Server }>();
</script>
