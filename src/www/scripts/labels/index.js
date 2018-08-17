import { create } from "./labels-box";

document.addEventListener("DOMContentLoaded", function() {
    [].forEach.call(document.querySelectorAll(".item-labels-box"), function(element) {
        create(element, element.dataset.labelsEndpoint);
    });
});
