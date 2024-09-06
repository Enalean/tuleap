# @tuleap/plugin-tracker-artifact-modal

## Dependencies

Depends on `angular`, `angular-sanitize`, `tlp`, `ckeditor4` and `jquery`. Provide them as `externals` or `alias` in
webpack configuration:

```javascript
// webpack.config.js
{
    //...
    externals: {
        "tlp": "tlp",
        "jquery": "jQuery",
        "ckeditor4": "CKEDITOR",
    },
    resolve: {
      alias: {
          // angular alias for the artifact modal (otherwise it is included twice)
          angular$: path.resolve(__dirname, "./node_modules/angular"),
          "angular-sanitize$": path.resolve(__dirname, "./node_modules/angular-sanitize"),
      }
    }
    //...
}
```

## How to include this in my angular app?

- You need webpack or a module-loader that can understand ES2015 `import`s.
- You need a sass build that can use `@use`.
- In your main `app.js`, add the following:
    ```javascript
    import angular_artifact_modal from '@tuleap/plugin-tracker-artifact-modal';

    // And in your main module declaration
    angular.module('my-app', [
        angular_artifact_modal
    ])
    //...
    ```
- In your main `app.scss`, add the following:
    ```scss
    @use 'pkg:@tuleap/plugin-tracker-artifact-modal';
    ```

## Usage

To create a new artifact, use:
```javascript
NewTuleapArtifactModalService.showCreation(
    user_id,
    tracker_id,
    parent_item,
    callback,
    prefill_values
);
```

`callback` will receive as first parameter `artifact_id`.

To edit an existing artifact, use:
```javascript
NewTuleapArtifactModalService.showEdition(
    user_id,
    tracker_id,
    artifact_id,
    callback,
);
```

`callback` will receive as first parameter `artifact_id`.

To show that the modal is loading, use `NewTuleapArtifactModalService.loading.loading`.
