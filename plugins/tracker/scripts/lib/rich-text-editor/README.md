# @tuleap/plugin-tracker-rich-text-editor

## Dependencies

Depends on `ckeditor4` and `jquery`. Provide them as `externals` in webpack configuration:

```javascript
// webpack.config.js
{
    //...
    externals: {
        "ckeditor4": "CKEDITOR",
        "jquery": "jQuery",
    },
    //...
}
```
Also, make sure to include jQuery and CKEDITOR sources in PHP **before** loading this module.

## Usage:

### Prerequisites:

A `<textarea>` element is needed. It is not created by this lib. The lib wraps it
with a rich text editor with buttons and interactivity.

The `<textarea>` MUST have a `[data-project-id]` attribute that contains the current
project's id. It is necessary to be able to transform references like `art #123` into
HTML links when pressing the "Preview" button in Markdown format.

### Without image upload:

```typescript
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import type { RichTextEditorOptions } from "@tuleap/plugin-tracker-rich-text-editor";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";

const locale = "en_US"; // Retrieve the locale somehow

// If you want to create the format selector, use:
const factory = RichTextEditorFactory.forFlamingParrotWithFormatSelector(
    document,
    locale
);
// Or if the format selector (and the Preview and Help buttons)
// are created by other means, use:
const factory = RichTextEditorFactory.forFlamingParrotWithExistingFormatSelector(
    document,
    locale
);
// Or for burning parrot, when the format selector (and the Preview and Help buttons)
// are created by other means, use:
const default_format = "commonmark"; // Retrieve the default format somehow
const factory = RichTextEditorFactory.forBurningParrotWithExistingFormatSelector(
    document,
    locale,
    default_format
);

options: RichTextEditorOptions = {
    format_selectbox_id: "format_id", // html "id" attribute for the Format selectbox
    format_selectbox_name: "format_name", // html "name" attribute for the Format selectbox
    format_selectbox_value: "text", // "text" | "html" | "commonmark". The initial value of the Format selectbox
    getAdditionalOptions: (textarea: HTMLTextAreaElement) => {
        // Add additional CKEditor options, or return empty object
        return {};
    },
    onFormatChange: (new_format: TextFieldFormat, new_content: string) => {
        // React on change of Format selectbox value
        // This is also called once at initialization
        // Content of the editor can change when switching from one format to another.
    },
    onEditorInit: (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) => {
        // React on creation of the CKEditor (only in "html" format)
    },
    onEditorDataReady: (ckeditor: CKEDITOR.editor) => {
        // This is only useful in Tracker Artifact view to setup @tuleap/mention.
        // There is no other use for this callback.
    }
}

const editor = factory.createRichTextEditor(textarea, options);

// Get the content of either the CKEditor or the textarea, depending on the chosen format
editor.getContent();

// Destroy the CKEditor (if it exists, in case format is not "html" it does nothing)
editor.destroy();
```

### With image upload:

#### Prerequisites

In addition to `[data-project-id]`, the `<textarea>` element must also have other
data-attributes. See `@tuleap/plugin-tracker-artifact-ckeditor-image-upload` for details.

⚠️ Only use this in Tracker Artifact view (FlamingParrot). It is very specific
to the `<form>` in Tracker Artifact view.

Pass the exports from `@tuleap/plugin-tracker-artifact-ckeditor-image-upload` into the options for `rich-text-editor`.

```typescript
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import type { RichTextEditorOptions } from "@tuleap/plugin-tracker-rich-text-editor";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    UploadImageFormFactory,
    getUploadImageOptions
} from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";

const locale = "en_US"; // Retrieve the locale somehow
const editor_factory = RichTextEditorFactory.forFlamingParrotWithFormatSelector(
    document,
    locale
);
const upload_factory = new UploadImageFormFactory(document, locale);

const help_block = upload_factory.createHelpBlock(textarea);

options: RichTextEditorOptions = {
    format_selectbox_id: "format_id",
    getAdditionalOptions: (textarea: HTMLTextAreaElement) => getUploadImageOptions(textarea),
    onFormatChange: (new_format: TextFieldFormat) => help_block.onFormatChange(new_format),
    onEditorInit: (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) =>
        upload_factory.initiateImageUpload(ckeditor_instance, textarea)
}
const editor = factory.createRichTextEditor(textarea, options);
```
