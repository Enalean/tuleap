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
    <div class="document-header">
        <h1 class="artidoc-header-title">
            {{ title }}
        </h1>
        <configuration-modal v-if="can_user_edit_document" />
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { TITLE } from "@/title-injection-key";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";

const title = strictInject(TITLE);
const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
</script>

<style scoped lang="scss">
.document-header {
    display: flex;
    gap: var(--tlp-medium-spacing);
    padding: var(--tlp-medium-spacing) var(--tlp-medium-spacing) var(--tlp-title-spacing);
}
</style>
