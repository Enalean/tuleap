<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div class="document-switch-to-docman">
        <a
            v-bind:href="redirectUrl"
            class="document-switch-to-docman-link"
            data-test="document-switch-to-old-ui"
        >
            <i class="fa-solid fa-shuffle document-switch-to-docman-icon"></i>
            {{ $gettext("Switch to old user interface") }}
        </a>
    </div>
</template>

<script setup lang="ts">
import { useRoute } from "vue-router";
import { computed } from "vue";
import { useState } from "vuex-composition-helpers";
import type { RootState } from "../../type";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../configuration-keys";

const { current_folder } = useState<Pick<RootState, "current_folder">>(["current_folder"]);
const project = strictInject(PROJECT);

const redirectUrl = computed(() => {
    const route = useRoute();
    const encoded_project_id = encodeURIComponent(project.id);
    if (route.name === "folder") {
        let item_id = route.params.item_id;
        if (Array.isArray(item_id)) {
            item_id = item_id.length > 0 ? item_id[0] : "";
        }
        return (
            "/plugins/docman/?group_id=" +
            encoded_project_id +
            "&action=show&id=" +
            encodeURIComponent(parseInt(item_id, 10))
        );
    } else if (route.name === "preview" && current_folder.value) {
        return (
            "/plugins/docman/?group_id=" +
            encoded_project_id +
            "&action=show&id=" +
            encodeURIComponent(current_folder.value.id)
        );
    }
    return "/plugins/docman/?group_id=" + encoded_project_id;
});
</script>
