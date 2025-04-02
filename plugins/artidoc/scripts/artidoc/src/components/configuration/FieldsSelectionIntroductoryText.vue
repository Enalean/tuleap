<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <p class="tlp-text-info" data-test="tracker-information">{{ tracker_information }}</p>
    </div>
    <p>
        {{ $gettext("Read-only fields provide context information about each artifact section.") }}
        {{
            $gettext(
                "The selection of fields to be displayed in an artidoc is tied to the selected tracker, meaning they cannot be set up until a tracker is configured.",
            )
        }}
        {{
            $gettext(
                "To configure the fields, you must be a Document Manager and have access to the tracker.",
            )
        }}
    </p>
    <p>
        {{ $gettext("Please select the fields you want to display below each artifact section.") }}
    </p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { computed } from "vue";
import type { Tracker } from "@/stores/configuration-store";

const { $gettext, interpolate } = useGettext();

const props = defineProps<{ tracker: Tracker }>();

const tracker_information = computed(() => {
    return interpolate($gettext('Your artidoc is currently set up on "%{ tracker_name }"'), {
        tracker_name: props.tracker.label,
    });
});
</script>
