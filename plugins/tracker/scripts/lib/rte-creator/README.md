# @tuleap/plugin-tracker-rte-creator

Creates the Rich Text Editors (RTE) with image upload in the Tracker Artifact View (Create, Edit) and the Tracker Modal v2 (the one in Cardwall).

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

## Prerequisites:

`<textarea>` elements are assumed to be created beforehand. They are not created by this lib. The lib wraps them with rich text editors and sets up image upload capability if it's possible (if there is a File field with update permission).

For the "New follow-up editor" and the "Edit follow-up editor", `<textarea>` elements MUST have specific ids (see the constants in [RichTextEditorsCreator.ts](./src/RichTextEditorsCreator.ts)).

For the "Text field editors", creation is delayed until the `<textarea>` appears in the viewport. It uses an IntersectionObserver. The `<textarea>` elements must be contained by an element with a certain CSS classname (see the constants in [RichTextEditorsCreator.ts](./src/RichTextEditorsCreator.ts)).

## Usage:

```typescript
import { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";

const locale = "en_US"; // Retrieve the locale somehow

const creator = new RichTextEditorsCreator(
    document,
    new UploadImageFormFactory(document, locale),
    RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale),
);
creator.createNewFollowupEditor();
creator.createEditFollowupEditor(changeset_id, format);
creator.createTextFieldEditors();
```
