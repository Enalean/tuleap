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
    <div class="tlp-form-element" v-if="config.servers.length <= 1">
        <label class="tlp-label tlp-checkbox">
            <input type="checkbox" v-bind:checked="!server.is_project_restricted" disabled />
            {{ $gettext("Allow all projects to use this server") }}
        </label>
        <p class="tlp-text-warning">
            <i class="fa-solid fa-person-digging" aria-hidden="true"></i>
            {{ $gettext("Under construction, you cannot set restrictions yet.") }}
        </p>
    </div>
</template>

<script setup lang="ts">
import type { Server } from "../../../type";
import { CONFIG } from "../../../injection-keys";
import { strictInject } from "../../../helpers/strict-inject";
defineProps<{ server: Server }>();

const config = strictInject(CONFIG);
</script>
