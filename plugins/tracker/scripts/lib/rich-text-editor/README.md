# @tuleap/plugin-tracker-rich-text-editor

Depends on the global variable `CKEDITOR` (from `ckeditor4`). Provide it as `externals` in webpack configuration and include ckeditor files **before** loading this module.

## Usage:

```typescript
import { RichTextEditorFactory, RichTextEditorOptions } from "@tuleap/plugin-tracker-rich-text-editor";
import { TextFieldFormat } from "./fields-constants";

const locale = "en_US"; // Retrieve the locale somehow
const factory = new RichTextEditorFactory(document, locale);

options: RichTextEditorOptions = {
    format_selectbox_id: "format_id", // html "id" attribute for the Format selectbox
    format_selectbox_name: "format_name", // html "name" attribute for the Format selectbox
    getAdditionalOptions: (textarea: HTMLTextAreaElement) => {
        // Add additional CKEditor options, or return empty object
        return {};
    },
    onFormatChange: (new_format: TextFieldFormat) => {
        // React on change of Format selectbox value
    },
    onEditorInit: (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) => {
        // React on creation of the CKEditor (only in "html" format)
    }
}

factory.createRichTextEditor(textarea, options);

```
