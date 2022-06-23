<!--
  - Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
  -->

<template>
    <div class="git-tracker-create-branch-modal">
        <div ref="root_element" class="tlp-modal git-tracker-create-branch-modal" role="dialog">
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title">
                    {{ $gettext("Create branch on a Git repository") }}
                </h1>
                <button
                    class="tlp-modal-close"
                    type="button"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
                </button>
            </div>
            <div class="tlp-modal-body"></div>
            <div class="tlp-modal-footer"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";

let modal: Modal | null = null;
const root_element = ref<InstanceType<typeof Element>>();

onMounted((): void => {
    if (root_element.value === undefined) {
        throw new Error("Cannot find modal root element");
    }
    modal = createModal(root_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: false,
        destroy_on_hide: true,
    });

    modal.show();
});

onBeforeUnmount(() => {
    modal?.destroy();
});
</script>

<style lang="scss" scoped>
@use "sass:meta";

.git-tracker-create-branch-modal :deep() {
    @include meta.load-css("@tuleap/tlp/src/scss/components/typography");
    @include meta.load-css("@tuleap/tlp/src/scss/components/buttons");
    @include meta.load-css(
        "@tuleap/tlp/src/scss/components/forms",
        (
            "tlp-images-basepath": "@tuleap/tlp/src/images",
        )
    );
    @include meta.load-css("@tuleap/tlp-modal");
}
</style>
