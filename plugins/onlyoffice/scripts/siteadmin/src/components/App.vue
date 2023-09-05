<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <restrict-server v-if="server_to_restrict" v-bind:server="server_to_restrict" />
    <list-of-servers v-else />
</template>

<script setup lang="ts">
import { provide, ref } from "vue";
import ListOfServers from "./Servers/ListOfServers.vue";
import RestrictServer from "./Servers/Restrict/RestrictServer.vue";
import { CONFIG, NAVIGATION } from "../injection-keys";
import type { Server } from "../type";
import { strictInject } from "@tuleap/vue-strict-inject";

const props = defineProps<{ location: Location; history: History }>();

const config = strictInject(CONFIG);

const server_to_restrict = ref<Server | undefined>(
    config.servers.find((server) => server.restrict_url === props.location.pathname),
);

provide(NAVIGATION, {
    restrict(server: Server) {
        props.history.pushState({}, "", server.restrict_url);
        server_to_restrict.value = server;
    },
    cancelRestriction() {
        props.history.pushState({}, "", config.base_url);
        server_to_restrict.value = undefined;
    },
});
</script>
