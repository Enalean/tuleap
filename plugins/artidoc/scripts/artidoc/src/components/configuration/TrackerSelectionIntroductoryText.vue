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
    <p>
        {{ $gettext("Artidoc allows composition of a document based on artifacts.") }}
        {{ $gettext("Each section is an artifact.") }}
    </p>
    <p>
        {{
            $gettext(
                "In order to be able to add new sections to your document, we need to know in which tracker the sections will be created into.",
            )
        }}
    </p>
    <p>
        {{
            $gettext(
                "Note: the tracker must have a title semantic (string field), a description semantic, and no required fields except title and description.",
            )
        }}
    </p>

    <div class="tlp-alert-warning" data-test="warning" v-if="should_display_warning">
        {{ reason }}
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { Option } from "@tuleap/option";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";

const { $gettext } = useGettext();

const props = defineProps<{
    selected_tracker: Option<Tracker>;
}>();

const reason = computed((): string =>
    props.selected_tracker.mapOr((tracker): string => {
        const has_title = Boolean(tracker.title);
        const has_description = Boolean(tracker.description);

        if (has_title && has_description) {
            return "";
        }

        if (has_title && !has_description) {
            return $gettext(
                "You cannot create new sections because you don't have submit permission for the description field of the selected tracker.",
            );
        }

        if (!has_title && has_description) {
            return $gettext(
                "You cannot create new sections because you don't have submit permission for the title field of the selected tracker.",
            );
        }

        return $gettext(
            "You cannot create new sections because you don't have submit permission for the title and description fields of the selected tracker.",
        );
    }, ""),
);

const should_display_warning = computed(() => reason.value !== "");
</script>
