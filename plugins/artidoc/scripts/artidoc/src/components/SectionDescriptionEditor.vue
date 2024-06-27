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
    <div>
        <textarea ref="area_editor" v-bind:value="toValue(editable_description)"></textarea>
        <p class="tlp-text-muted drag-and-drop-info" v-if="is_dragndrop_allowed">
            {{ $gettext("You can drag 'n drop or paste image directly in the editor.") }}
        </p>
    </div>
</template>
<script setup lang="ts">
import type * as ckeditor from "ckeditor4";
import { toValue, ref, onMounted, onBeforeUnmount } from "vue";
import { config } from "@tuleap/ckeditor-config";
import { CURRENT_LOCALE } from "@/locale-injection-key";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { EditorSectionContent } from "@/composables/useEditorSectionContent";

// CKEDITOR is injected by the backend
// eslint-disable-next-line
import eventInfo = CKEDITOR.eventInfo;

import type { UploadHandler, UploadError } from "@tuleap/ckeditor-image-upload";
import { MaxSizeUploadExceededError, buildFileUploadHandler } from "@tuleap/ckeditor-image-upload";
import type { AttachmentFile } from "@/composables/useAttachmentFile";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import type { UserLocale } from "@/helpers/user-locale";
import { useGettext } from "vue3-gettext";
const { language } = strictInject<UserLocale>(CURRENT_LOCALE);

const props = defineProps<{
    upload_url: string;
    add_attachment_to_waiting_list: AttachmentFile["addAttachmentToWaitingList"];
    editable_description: string;
    is_dragndrop_allowed: boolean;
    input_current_description: EditorSectionContent["inputCurrentDescription"];
}>();

const area_editor = ref<HTMLTextAreaElement | null>(null);
const editor = ref<ckeditor.default.editor | null>(null);

const upload_max_size = strictInject(UPLOAD_MAX_SIZE);

const { $gettext, interpolate } = useGettext();

const onChange = (editor_value: string | undefined): void => {
    if (editor_value) {
        props.input_current_description(editor_value);
    }
};

const setupImageUpload = (): UploadHandler => {
    const onStartCallback = (): void => {};
    const wait_maximum_time = 9999999;
    const onErrorCallback = (error: UploadError | MaxSizeUploadExceededError): void => {
        if (error instanceof MaxSizeUploadExceededError) {
            editor.value?.showNotification(
                interpolate("You are not allowed to upload images bigger than %{ max_size }", {
                    max_size: upload_max_size,
                }),
                "warning",
                wait_maximum_time,
            );
        } else {
            editor.value?.showNotification(
                $gettext("Unable to upload the image"),
                "warning",
                wait_maximum_time,
            );
        }
    };
    const onSuccessCallback = (id: number, download_href: string): void => {
        props.add_attachment_to_waiting_list({ id, upload_url: download_href });
    };

    return buildFileUploadHandler({
        // eslint-disable-next-line
        ckeditor_instance: editor.value as any as CKEDITOR.editor,
        max_size_upload: upload_max_size,
        onStartCallback,
        onErrorCallback,
        onSuccessCallback,
    });
};

const onInstanceReady = (): void => {
    if (editor.value) {
        editor.value.on("change", () => onChange(editor.value?.getData()));

        editor.value.on("mode", (): void => {
            if (editor.value && editor.value.mode === "source") {
                const editable = editor.value.editable();
                editable.attachListener(editable, "input", () => {
                    onChange(editor.value?.getData());
                });
            }
        });

        // Ajust the height of the editor to the content. The timeout is required to load autogrow plugin correctly after editor initialization
        setTimeout(() => {
            if (editor.value) {
                editor.value.execCommand("autogrow");
            }
        });

        editor.value.on(
            "fileUploadRequest",
            // eslint-disable-next-line
            (event: eventInfo<CKEDITOR.eventDataTypes>) => {
                if (
                    props.upload_url === "/api/v1/tracker_fields/0/files" ||
                    !props.is_dragndrop_allowed
                ) {
                    event.cancel();
                    // @ts-expect-error : we have to intercept the fileLoader and abort the image upload to prevent the image from being displayed in the editor
                    event.data.fileLoader.abort();

                    editor.value?.showNotification(
                        $gettext("You are not allowed to paste images here"),
                        "warning",
                        0,
                    );
                } else {
                    // eslint-disable-next-line
                    setupImageUpload()(event as any);
                }
            },
            null,
            null,
            4,
        );

        editor.value.document.getBody().setStyle("font-size", "16px");
    }
};

onMounted(() => {
    if (editor.value !== null) {
        editor.value.destroy();
    }

    if (area_editor.value) {
        // @ts-expect-error: CKEDITOR is injected by the backend
        // eslint-disable-next-line
        editor.value = CKEDITOR.replace(area_editor.value, {
            ...config,
            language,
            extraPlugins: "autogrow,uploadimage",
            uploadUrl: props.upload_url,
            autoGrow_minHeight: 200,
            autoGrow_bottomSpace: 50,
            height: "auto",
            resize_enabled: false,
        });

        if (editor.value !== null) {
            editor.value.on("instanceReady", onInstanceReady);
        }
    }
});

onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.destroy();
    }
});
</script>

<style lang="scss" scoped>
@use "@/themes/includes/zindex";

div {
    z-index: zindex.$editor;
}

.drag-and-drop-info {
    margin: 0 0 var(--tlp-small-spacing);
    font-size: 0.9rem;
}
</style>
