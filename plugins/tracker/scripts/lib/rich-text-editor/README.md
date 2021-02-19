# @tuleap/plugin-tracker-rich-text-editor

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

```typescript
import { RichTextEditorFactory, RichTextEditorOptions } from "@tuleap/plugin-tracker-rich-text-editor";
import { TextFieldFormat } from "./fields-constants";

const locale = "en_US"; // Retrieve the locale somehow

// If you want to have the format selector with the editor, use:
const factory = RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale);
// Or if the format selector is created by other means, use:
const factory = RichTextEditorFactory.forFlamingParrotWithExistingFormatSelector(document, locale);

options: RichTextEditorOptions = {
    format_selectbox_id: "format_id", // html "id" attribute for the Format selectbox
    format_selectbox_name: "format_name", // html "name" attribute for the Format selectbox
    format_selectbox_value: "text", // "text" | "html" | "commonmark". The initial value of the Format selectbox
    getAdditionalOptions: (textarea: HTMLTextAreaElement) => {
        // Add additional CKEditor options, or return empty object
        return {};
    },
    onFormatChange: (new_format: TextFieldFormat) => {
        // React on change of Format selectbox value
        // This is also called once at initialization
    },
    onEditorInit: (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) => {
        // React on creation of the CKEditor (only in "html" format)
    }
}

const editor = factory.createRichTextEditor(textarea, options);
editor.getContent(); // Get the content of either the CKEditor or the textarea, depending on the chosen format
```
