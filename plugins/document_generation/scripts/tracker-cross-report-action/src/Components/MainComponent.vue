<!--
  - Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
    <Teleport to="#main-content">
        <div class="tracker-cross-report-document-modal">
            <modal-content v-bind:properties="properties" />
        </div>
    </Teleport>
</template>
<script setup lang="ts">
import type { GlobalExportProperties } from "../type";
import ModalContent from "./ModalContent.vue";

defineProps<{ properties: GlobalExportProperties }>();
</script>
<style lang="scss" scoped>
@use "sass:meta";

.tracker-cross-report-document-modal :deep() {
    @include meta.load-css("@tuleap/tlp/src/scss/components/typography");
    @include meta.load-css(
        "@tuleap/tlp/src/scss/components/forms",
        (
            "tlp-images-basepath": "@tuleap/tlp/src/images",
        )
    );
    @include meta.load-css("@tuleap/tlp-modal");
    @include meta.load-css("@tuleap/tlp-button");
}
</style>
