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
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";

const selected_tracker = strictInject(SELECTED_TRACKER);
const { $gettext } = useGettext();

const tracker_information = computed(() =>
    selected_tracker.value.mapOr(
        (tracker) =>
            $gettext('Your artidoc is currently set up on "%{ tracker_name }"', {
                tracker_name: tracker.label,
            }),
        "",
    ),
);
</script>
