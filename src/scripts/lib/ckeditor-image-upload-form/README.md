# @tuleap/ckeditor-image-upload-form

It is used only in `RichTextEditor.js` in `@tuleap/core`. It keeps the form in Tracker Artifact view in a consistent
state and cleans up files that have been uploaded and then removed.

## Usage:

```javascript
import { UploadImageFormFactory, getUploadImageOptions } from "@tuleap/ckeditor-image-upload-form";
const locale = "en_US"; // Retrieve the locale somehow
const factory = new UploadImageFormFactory(document, locale);

const help_block = factory.createHelpBlock(textarea);
help_block.onFormatChange(new_format); // call this when the editor format changes

getUploadImageOptions(textarea); // Gives additional CKEditor options
// Initialize CKEditor
factory.initiateImageUpload(ckeditor_instance, textarea); // Call this after CKEditor has been initialized

```
