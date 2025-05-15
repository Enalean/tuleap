<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  - This item is a part of Tuleap.
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
    <button
        type="button"
        class="tlp-dropdown-menu-item"
        role="menuitem"
        data-test="download-as-zip-button"
        v-on:click="checkFolderSize"
        data-shortcut-download-zip
    >
        <i
            class="fa-fw tlp-dropdown-menu-item-icon"
            v-bind:class="{
                'fa-solid fa-tlp-zip-download': !is_retrieving_folder_size,
                'fa-solid fa-spin fa-circle-notch': is_retrieving_folder_size,
            }"
        ></i>
        {{ $gettext("Download as zip") }}
    </button>
</template>
<script setup lang="ts">
import { redirectToUrl } from "../../../../helpers/location-helper";
import emitter from "../../../../helpers/emitter";
import type { Folder } from "../../../../type";
import { isPlatformOSX } from "../../../../helpers/platform-detector";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { computed, ref } from "vue";
import type { PropertiesActions } from "../../../../store/properties/properties-actions";

const props = defineProps<{ item: Folder }>();

const { project_name, max_archive_size, warning_threshold } = useNamespacedState<
    Pick<ConfigurationState, "project_name" | "max_archive_size" | "warning_threshold">
>("configuration", ["project_name", "max_archive_size", "warning_threshold"]);

const { getFolderProperties } = useNamespacedActions<PropertiesActions>("properties", [
    "getFolderProperties",
]);

const is_retrieving_folder_size = ref(false);

const folder_href = computed((): string => {
    return `/plugins/document/${project_name.value}/folders/${encodeURIComponent(
        props.item.id,
    )}/download-folder-as-zip`;
});

function shouldWarnOSXUser(total_size: number, nb_files: number): boolean {
    if (!isPlatformOSX(window)) {
        return false;
    }

    const total_size_in_GB = total_size * Math.pow(10, -9);
    return total_size_in_GB >= 4 || nb_files >= 64000;
}

async function checkFolderSize(): Promise<void> {
    is_retrieving_folder_size.value = true;

    const folder_properties = await getFolderProperties(props.item);
    is_retrieving_folder_size.value = false;

    if (folder_properties === null) {
        return;
    }

    // max_archive_size is in MB, total_size in Bytes. Let's convert it to Bytes first.
    const max_archive_size_in_Bytes = max_archive_size.value * Math.pow(10, 6);
    const { total_size, nb_files } = folder_properties;

    if (total_size > max_archive_size_in_Bytes) {
        emitter.emit("show-max-archive-size-threshold-exceeded-modal", {
            detail: { current_folder_size: total_size },
        });

        return;
    }

    // warning_threshold is in MB, total_size in Bytes. Let's convert it to Bytes first.
    const warning_threshold_in_Bytes = warning_threshold.value * Math.pow(10, 6);
    const should_warn_osx_user = shouldWarnOSXUser(total_size, nb_files);

    if (total_size > warning_threshold_in_Bytes) {
        emitter.emit("show-archive-size-warning-modal", {
            detail: {
                current_folder_size: total_size,
                folder_href: folder_href.value,
                should_warn_osx_user,
            },
        });

        return;
    }

    redirectToUrl(folder_href.value);
}
</script>
