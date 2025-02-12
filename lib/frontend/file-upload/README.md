# @tuleap/file-upload

Utilities to upload files via TUS protocol.

## Usage

Import the package in your application:
```shell
pnpm install @tuleap/file-upload
```

Then in your typescript file:
```typescript
import { getFileUploader } from "@tuleap/file-upload";

const uploader = getFileUploader();

uploader.createOngoingUpload(file, [], options);
```
This will return an optional ongoing upload.

If you need to cancel ongoing uploads:
```typescript
uploader.cancelOngoingUpload();
```

## Options

`createOngoingUpload` takes `options: FileUploadOptions` as parameter:
```typescript
{
    post_information: {
        // The url to initiate the upload
        upload_url: string,
        // callback to build the payload that can varies depending on context
        getUploadJsonPayload: (file: File) => unknown,
    },
    // callback to call in case of error, allowing to display it in the UI for example
    onErrorCallback: (error: UploadError, file_name: string) => void,
    // callback to call in case of successful upload
    onSuccessCallback: (id: FileIdentifier, download_href: string, file_name: string) => void,
    // callback to call every time a progress is made on a file upload
    onProgressCallback: (file_name: string, global_progress: number) => void,
};
```
