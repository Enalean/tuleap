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
        class="onlyoffice-admin-toggle-allow-all-wrapper"
        v-bind:class="{
            'tlp-tooltip tlp-tooltip-right': config.servers.length > 1,
        }"
        v-bind:data-tlp-tooltip="
            $gettext('You can allow all the projects when only one document server is configured')
        "
    >
        <div
            class="tlp-form-element"
            v-bind:class="{
                'tlp-form-element-disabled': config.servers.length > 1,
            }"
        >
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
                    v-bind:disabled="config.servers.length > 1"
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
            <unrestiction-confirmation-modal
                v-if="show_unrestriction_modal"
                v-on:cancel-unrestriction="cancelUnrestriction"
            />
            <restriction-confirmation-modal
                v-if="show_restriction_modal"
                v-on:cancel-restriction="cancelRestriction"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Server } from "../../../type";
import { CONFIG } from "../../../injection-keys";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ref } from "vue";
import UnrestictionConfirmationModal from "./UnrestictionConfirmationModal.vue";
import RestrictionConfirmationModal from "./RestrictionConfirmationModal.vue";

const props = defineProps<{
    server: Server;
}>();

const is_checked = ref(!props.server.is_project_restricted);
const show_unrestriction_modal = ref(false);
const show_restriction_modal = ref(false);

const config = strictInject(CONFIG);

function onChange(checkbox: EventTarget | null): void {
    if (!(checkbox instanceof HTMLInputElement)) {
        return;
    }

    if (props.server.is_project_restricted) {
        show_unrestriction_modal.value = checkbox.checked;
    } else {
        show_restriction_modal.value = !checkbox.checked;
    }
}

function cancelUnrestriction(): void {
    show_unrestriction_modal.value = false;
    is_checked.value = false;
}

function cancelRestriction(): void {
    show_restriction_modal.value = false;
    is_checked.value = true;
}
</script>

<style lang="scss" scoped>
.onlyoffice-admin-toggle-allow-all-wrapper {
    align-self: flex-start;
}
</style>
