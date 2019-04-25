export default function AutoFocus() {
    return {
        link: {
            pre: function(scope, element, attr) {},
            post: function(scope, element, attr) {
                element[0].focus();
            }
        }
    };
}
