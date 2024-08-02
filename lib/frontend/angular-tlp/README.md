# @tuleap/angular-tlp

## Prerequisites

You should be using webpack in order to load Angular-TLP easily. Indicate the relative path to angular-tlp/index.js in `alias`.
Don't forget to include tlp as `externals` since it should already be loaded.
Then, in the app.js file (or similar main AngularJS module), import the module name as a dependency of your module.

```typescript
// webpack.config.js
var path = require('path');

{
    // ...
    externals: {
        tlp: 'tlp'
    }
    // ...
}
```

```typescript
// app.js OR similar main AngularJS module
import angular from 'angular';
import angular_tlp_module from '@tuleap/angular-tlp';

export default angular.module('my-main-module', [
    angular_tlp_module,
    //... other modules
])
// services, controllers, etc...
.name;
```
## TLPModalService

TLPModalService.open(options)

### Arguments:

* { Object } options. Contains:
   * { String } templateUrl
     * Relative URL to the template of the modal. Must be already defined in AngularJS' `$templateCache`.
   * { Function } controller
     * Controller function for the modal. Will be injected with `modal_instance` and all properties defined in the `resolve` option.
   * { String } controllerAs
     * Name of the scope property that will hold the controller in the template.
   * (Optional) { Object } tlpModalOptions
     * Options passed down to the TLP modal constructor.
   * (Optional) { Object } resolve
     * Each key will be injected in the provided modal controller. Use it to pass data and callbacks to the modal controller.

The template provided in `options.templateUrl` must be saved in AngularJS' `$templateCache`. TlpModalService will NOT load it dynamically. You must import it in the calling controller !

`modal_instance.tlp_modal` will be undefined at controller creation ! Code depending on it must be in the controller's `$onInit()` function !

```typescript
// your-controller.js

// You must import the modal's template this way to save it in AngularJS' $templateCache
import './my-custom-modal.tpl.html';
import MyCustomModalController from './my-custom-modal-controller.js';

export default YourController;

YourController.$inject = [
    //...
    'TlpModalService',
    //...
];

function YourController(
    //...
    TlpModalService,
    //...
) {
    const self = this;
    self.openEditModal = openEditModal;

    function customFunction(tracker_id) {
        console.log(tracker_id);
    }

    function openEditModal(argument) {
        TlpModalService.open({
            // This template needs to already be in $templateCache.
            // TlpModalService will NOT load it dynamically.
            // You must import it at the top of the controller !
            templateUrl: 'my-custom-modal.tpl.html',
            // You should import the controller and provide it
            // to the modal this way
            controller: MyCustomModalController,
            // Same as directives, the name of the controller in the template
            controllerAs: 'my_custom_modal',
            // TLP modal options
            tlpModalOptions: {
                keyboard: false
            },
            // All the properties of "resolve" will be injected in the modal's controller
            // just as if they were services !
            resolve: {
                my_custom_object: {
                    tracker_id: 76
                },
                // This can be used to provide callbacks to the modal
                my_custom_function: customFunction
            }
        });
    }
}
```

```typescript
// my-custom-modal-controller.js
export default MyCustomModalController;

MyCustomModalController.$inject = [
    //...
    'modal_instance',
    'my_custom_object',
    'my_custom_function'
    //...
];

function MyCustomModalController(
    //...
    modal_instance,
    my_custom_object,
    my_custom_function
    //...
) {
    const self = this;

    // Use $onInit to run code when the controller is instanciated
    self.$onInit = function() {
        // Use modal_instance to access TLP's modal object
        modal_instance.tlp_modal.addEventListener('tlp-modal-shown', () => {
            // You can also access the modal's DOM this way
            const input = modal_instance.tlp_modal.element.querySelector('.my-title-input');
            if (input) { input.focus(); };
        });

        modal_instance.tlp_modal.addEventListener('tlp-modal-hidden', () => {
            // Using the "resolved" functions and objects
            // This should log "76"
            my_custom_function(my_custom_object.tracker_id);
        });
    }
}
```
