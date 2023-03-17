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
    <h1>{{ $gettext("ONLYOFFICE") }}</h1>

    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="fa-solid fa-file tlp-pane-title-icon" aria-hidden="true"></i>
                    {{ $gettext("ONLYOFFICE document servers") }}
                </h1>
            </div>
            <section class="tlp-pane-section">
                <div class="tlp-table-actions">
                    <add-server-button />
                    <add-server-modal />
                </div>

                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>{{ $gettext("Document server URL") }}</th>
                            <th>{{ $gettext("Projects restriction") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <servers-empty-state v-if="config.servers.length === 0" />
                        <server-row
                            v-for="server of config.servers"
                            v-bind:key="server.id"
                            v-bind:server="server"
                        />
                    </tbody>
                </table>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import AddServerModal from "./AddServerModal.vue";
import AddServerButton from "./AddServerButton.vue";
import ServersEmptyState from "./ServersEmptyState.vue";
import ServerRow from "./ServerRow.vue";

import { strictInject } from "@tuleap/vue-strict-inject";
import { CONFIG } from "../../injection-keys";

const config = strictInject(CONFIG);
</script>
