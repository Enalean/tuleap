# @tuleap/plugin-tracker-artifact-ckeditor-image-upload

It creates the "Help block" to show "You can drag'n drop images or copy/paste them".
It also gives the options and plugins to be passed to CKEditor to accept image upload.
It also keeps the form in Tracker Artifact view in a consistent state and cleans up files that have been uploaded and
then removed from the editor.

## Usage:

### Prerequisites

This lib needs a `<textarea>` to be created with some data-attributes to function.

- `[data-upload-url]` must contain the full URI to the `/api/v1/tracker_fields/<field_id>/files` endpoint.
  It is used by the [TUS client][tus-readme] to upload images pasted or drag and dropped in the rich text editor.
  For example: `data-upload-url="/api/v1/tracker_fields/3120/files"`
- `[data-upload-field-name]` must contain the `name` expected by the form handler
  in the backend to bind the uploaded file to the attachment fields. For example: `data-upload-field-name="artifact[3120][][tus-uploaded-id]"`
- `[data-upload-max-size]` must contain a `number` representing the max file size
  that can be uploaded. The value is expressed in Bytes. For example: `data-upload-max-size="3145728"`
- `[data-help-id]` must contain an HTML ID pointing to a `<p>` tag near the `<textarea>`.
  If uploading is allowed, this `<p>` tag will be filled with a translated text to indicate to
  end-users that they can paste or drag and drop images. (the "Help block" mentioned above).

```javascript
import {
    UploadImageFormFactory,
    getUploadImageOptions
} from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";

const locale = "en_US"; // Retrieve the locale somehow
const factory = UploadImageFormFactory(document, locale);

const help_block = factory.createHelpBlock(textarea);
help_block.onFormatChange(new_format); // call this when the editor format changes

getUploadImageOptions(textarea); // Gives additional CKEditor options
// Initialize CKEditor
// const ckeditor_instance = (some call);
factory.initiateImageUpload(ckeditor_instance, textarea); // Call this after CKEditor has been initialized
```

[tus-readme]: <https://www.npmjs.com/package/tus-js-client>
