<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <section class="tlp-popover tlp-popover-info" ref="popover_content">
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-header">
            <h1 class="tlp-popover-title">
                {{ $gettext("Tuleap can notify approval members in two ways:") }}
            </h1>
        </div>
        <div class="tlp-popover-body">
            <ul>
                <li>
                    <strong>{{ $gettext("All at once") }}</strong>
                    {{ $gettext(": notify all reviewers who did not commit themselves yet.") }}
                </li>
                <li>
                    <strong>{{ $gettext("Sequential") }}</strong>
                    {{
                        $gettext(
                            ": notify reviewers (who did not commit themselves) one after another. If someone reject the document, the sequence stops",
                        )
                    }}
                </li>
            </ul>
            <p>
                {{
                    $gettext(
                        "After an approver is notified by the approval table, it is informed of any later modification done on the document.",
                    )
                }}
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { createPopover } from "@tuleap/tlp-popovers";

const props = defineProps<{
    trigger: HTMLElement;
}>();

const popover_content = ref<HTMLElement>();

onMounted(() => {
    if (popover_content.value === undefined) {
        throw Error("Failed to create notification popover");
    }

    createPopover(props.trigger, popover_content.value);
});
</script>
