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
        class="tlp-button-primary tlp-table-actions-element onlyoffice-admin-modal-server-button"
        v-bind:disabled="config.servers.length > 0"
        v-on:click="emitter.emit('show-add-server-modal')"
    >
        <i class="tlp-button-icon fa-solid fa-plus" aria-hidden="true"></i>
        {{ $gettext("Add document server") }}
    </button>
</template>

<script setup lang="ts">
import { strictInject } from "../../helpers/strict-inject";
import { CONFIG } from "../../injection-keys";
import emitter from "../../helpers/emitter";

const config = strictInject(CONFIG);
</script>
