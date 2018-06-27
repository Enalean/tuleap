const SECONDARY_MOUSE_BUTTON = 2;
const USE_CAPTURE = true;

export function fixRightClickBug() {
    /* Fix for https://github.com/angular-ui/bootstrap/issues/5051
     * Dropdown menu closes on right click in Firefox
     * Due to our dependency on Bootstrap v2, we cannot upgrade angular-ui-bootstrap.
     * Since we pull that library from bower, we cannot monkey-patch it either.
     * On Firefox, right-clicking on elements has the weird side-effect of
     * triggering a "click" event on document.
     * Since that click event does not bubble from the original target, there is
     * no way to catch it conventionally with stopPropagation().
     *
     * Therefore, we add a "capturing" click listener on document
     *
     * "Capturing" listener will trigger before the actual right-click event
     * on the element.
     * "stopImmediatePropagation()" will prevent other "click" listeners on
     * document from running, and keep our dropdown from closing.
    */
    document.addEventListener(
        "click",
        function(event) {
            if (event.button === SECONDARY_MOUSE_BUTTON) {
                event.stopImmediatePropagation();
            }
        },
        USE_CAPTURE
    );
}
