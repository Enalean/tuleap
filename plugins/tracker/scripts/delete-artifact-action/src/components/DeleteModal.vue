<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <section>
        <form
            action="/plugins/tracker/"
            class="tlp-modal tlp-modal-danger"
            id="delete-artifact-modal"
            aria-labelledby="delete-artifact-modal-title"
            ref="delete_artifact_modal"
        >
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title" id="delete-artifact-modal-title">
                    {{ $gettext("Artifact deletion") }}
                </h1>
                <button
                    class="tlp-modal-close"
                    type="button"
                    data-dismiss="modal"
                    v-bind:aria-label="$gettext('Close')"
                >
                    <i class="fa-solid fa-xmark tlp-modal-close-icon" role="img"></i>
                </button>
            </div>
            <div class="tlp-modal-body">
                <input type="hidden" name="id" v-bind:value="artifact_id" />
                <input type="hidden" name="tracker" v-bind:value="tracker_id" />
                <input type="hidden" v-bind:name="token_name" v-bind:value="token" />
                <input type="hidden" name="func" value="admin-delete-artifact" />
                <p>
                    {{ $gettext("You are about to delete an artifact.") }}
                    <strong>{{ $gettext("This action cannot be undone.") }}</strong>
                </p>
                <p>
                    {{ $gettext("Please confirm your action.") }}
                </p>
            </div>
            <div class="tlp-modal-footer">
                <button
                    type="button"
                    class="tlp-button-danger tlp-button-outline tlp-modal-action"
                    data-dismiss="modal"
                >
                    {{ $gettext("Cancel") }}
                </button>
                <button
                    type="submit"
                    class="tlp-button-danger tlp-modal-action"
                    data-test="delete-artifact"
                >
                    <i class="fa-regular fa-trash-alt tlp-button-icon" aria-hidden="true"></i>
                    {{ $gettext("Delete artifact") }}
                </button>
            </div>
        </form>
    </section>
</template>
<script setup lang="ts">
import { onMounted, ref, onBeforeUnmount } from "vue";
import { useGettext } from "vue3-gettext";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";

defineProps<{ artifact_id: number; tracker_id: number; token: string; token_name: string }>();

const { $gettext } = useGettext();

const delete_artifact_modal = ref<HTMLElement>();
let modal: Modal | null = null;

onMounted(() => {
    if (!delete_artifact_modal.value) {
        return;
    }

    modal = createModal(delete_artifact_modal.value);
    modal.show();
});

onBeforeUnmount(() => {
    modal?.destroy();
});
</script>

<style lang="scss" scoped>
@use "sass:meta";

body[class*="FlamingParrot"] section :deep() {
    color: initial;
    font-size: 1rem;
    font-weight: initial;
    text-shadow: initial;

    @include meta.load-css("pkg:@tuleap/tlp-modal");
}
</style>
