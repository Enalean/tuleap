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
        <label class="tlp-label" for="onlyoffice-admin-toggle-allow-all">
            {{ $gettext("Allow all projects to use this server") }}
        </label>
        <div class="tlp-switch">
            <input type="hidden" name="is_restricted" value="1" />
            <input
                type="checkbox"
                name="is_restricted"
                value="0"
                v-model="is_checked"
                v-bind:disabled="!server.is_project_restricted"
                v-on:change="onChange($event.target)"
                id="onlyoffice-admin-toggle-allow-all"
                class="tlp-switch-checkbox"
            />
            <label
                for="onlyoffice-admin-toggle-allow-all"
                class="tlp-switch-button"
                aria-hidden
            ></label>
        </div>
        <p class="tlp-text-warning" v-if="!server.is_project_restricted">
            <i class="fa-solid fa-person-digging" aria-hidden="true"></i>
            {{ $gettext("Under construction, you cannot set restrictions yet.") }}
        </p>
        <unrestiction-confirmation-modal
            v-if="show_unrestriction_modal"
            v-on:cancel-unrestriction="cancelUnrestriction"
        />
    </div>
    <input type="hidden" name="is_restricted" value="1" v-else />
</template>

<script setup lang="ts">
import type { Server } from "../../../type";
import { CONFIG } from "../../../injection-keys";
import { strictInject } from "../../../helpers/strict-inject";
import { ref } from "vue";
import UnrestictionConfirmationModal from "./UnrestictionConfirmationModal.vue";

const props = defineProps<{
    server: Server;
}>();

const is_checked = ref(!props.server.is_project_restricted);
const show_unrestriction_modal = ref(false);

const config = strictInject(CONFIG);

function onChange(checkbox: EventTarget | null): void {
    if (!(checkbox instanceof HTMLInputElement)) {
        return;
    }

    if (props.server.is_project_restricted) {
        show_unrestriction_modal.value = checkbox.checked;
    }
}

function cancelUnrestriction(): void {
    show_unrestriction_modal.value = false;
    is_checked.value = false;
}
</script>

<style lang="scss" scoped>
.tlp-form-element {
    margin: 0 0 var(--tlp-x-large-spacing);
}
</style>
