# @tuleap/plugin-tracker-artifact-ckeditor-image-upload

It creates the "Help block" to show "You can drag'n drop images or copy/paste them".
It also gives the options and plugins to be passed to CKEditor to accept image upload.
It also keeps the form in Tracker Artifact view in a consistent state and cleans up files that have been uploaded and
then removed from the editor.

## Usage:

```javascript
import { UploadImageFormFactory, getUploadImageOptions } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
const locale = "en_US"; // Retrieve the locale somehow
const factory = new UploadImageFormFactory(document, locale);

const help_block = factory.createHelpBlock(textarea);
help_block.onFormatChange(new_format); // call this when the editor format changes

getUploadImageOptions(textarea); // Gives additional CKEditor options
// Initialize CKEditor
// const ckeditor_instance = (some call);
factory.initiateImageUpload(ckeditor_instance, textarea); // Call this after CKEditor has been initialized
```
