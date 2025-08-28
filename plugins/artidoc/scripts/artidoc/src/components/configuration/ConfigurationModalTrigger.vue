<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
        class="tlp-dropdown-menu-item"
        type="button"
        role="menuitem"
        v-bind:title="button_title"
        v-on:click="openModal()"
        data-test="document-actions-open-configuration-modal"
    >
        <i class="fa-solid fa-cog fa-fw" aria-hidden="true"></i>
        {{ $gettext("Configure document") }}
    </button>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { OPEN_CONFIGURATION_MODAL_BUS } from "@/stores/useOpenConfigurationModalBusStore";
import { strictInject } from "@tuleap/vue-strict-inject";

const { $gettext } = useGettext();

const button_title = $gettext("Configure document");

const bus = strictInject(OPEN_CONFIGURATION_MODAL_BUS);

function openModal(): void {
    bus.openModal();
}
</script>

<style scoped lang="scss">
.tlp-button-ellipsis {
    flex-shrink: 0;
    margin: 3px 0 0;
    font-size: 1.125rem;
}
</style>
