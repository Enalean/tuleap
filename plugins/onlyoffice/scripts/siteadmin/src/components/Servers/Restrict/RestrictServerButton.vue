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
    <button
        type="button"
        class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
        v-on:click="navigation.restrict(server)"
    >
        <i class="tlp-button-icon fa-solid fa-lock" aria-hidden="true"></i>
        {{ $gettext("Restrict") }}
    </button>
</template>
<script setup lang="ts">
import type { Server } from "../../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { NAVIGATION } from "../../../injection-keys";

defineProps<{ server: Server }>();

const navigation = strictInject(NAVIGATION);
</script>
