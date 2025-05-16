<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" for="document-new-file-upload-approval-table-action">
            {{ $gettext("Approval table") }}
            <i class="fa-solid fa-asterisk"></i>
        </label>
        <div id="document-new-file-upload-approval-table-action">
            <div class="tlp-form-element">
                <label
                    class="tlp-label tlp-radio"
                    for="document-new-file-upload-approval-table-action-copy"
                >
                    <input
                        id="document-new-file-upload-approval-table-action-copy"
                        type="radio"
                        name="approval-update-action"
                        value="copy"
                        v-on:change="$emit('approval-table-action-change', $event.target.value)"
                        required
                    />
                    {{ $gettext("Copy the previous approval table.") }}
                </label>
                <p class="tlp-text-info">
                    {{ $gettext("Keep reviewers, comments and commitments.") }}
                </p>
            </div>
            <div class="tlp-form-element">
                <label
                    class="tlp-label tlp-radio"
                    for="document-new-file-upload-approval-table-action-reset"
                >
                    <input
                        id="document-new-file-upload-approval-table-action-reset"
                        type="radio"
                        name="approval-update-action"
                        value="reset"
                        v-on:change="$emit('approval-table-action-change', $event.target.value)"
                    />
                    {{ $gettext("Reset the approval cycle.") }}
                </label>
                <p class="tlp-text-info">
                    {{
                        $gettext(
                            "Only the table structure will be kept. Reviewer comments and commitments will be discarded.",
                        )
                    }}
                </p>
            </div>
            <div class="tlp-form-element">
                <label
                    class="tlp-label tlp-radio"
                    for="document-new-file-upload-approval-table-action-empty"
                >
                    <input
                        id="document-new-file-upload-approval-table-action-empty"
                        type="radio"
                        name="approval-update-action"
                        value="empty"
                        v-on:change="$emit('approval-table-action-change', $event.target.value)"
                    />
                    {{ $gettext("Create a new empty approval table.") }}
                </label>
                <p class="tlp-text-info">
                    {{ $gettext("Start over with a completely new approval table.") }}
                </p>
            </div>
        </div>
    </div>
</template>
