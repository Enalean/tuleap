export default UUIDGeneratorService;

UUIDGeneratorService.$inject = ["$window"];

/**
 * Source Code: https://github.com/gdi2290/angular-uuid-secure
 */
function UUIDGeneratorService($window) {
    var TypeArray = Uint16Array || Int16Array;

    return {
        generateUUID: generateUUID,
    };

    function generateUUID() {
        return $window.crypto ? generatorUUIDSecure() : generatorUUID();
    }

    function generatorUUID() {
        var d = new Date().getTime();
        var uuid = "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function (c) {
            var r = (d + Math.random() * 16) % 16 | 0;
            d = Math.floor(d / 16);
            return (c === "x" ? r : (r & 0x3) | 0x8).toString(16);
        });

        return uuid;
    }

    function generatorUUIDSecure() {
        var buf = new TypeArray(8);
        getRandomValues(buf);
        var S4 = function (num) {
            var ret = num.toString(16);
            while (ret.length < 4) {
                ret = "0" + ret;
            }
            return ret;
        };
        return (
            S4(buf[0]) +
            S4(buf[1]) +
            "-" +
            S4(buf[2]) +
            "-" +
            S4(buf[3]) +
            "-" +
            S4(buf[4]) +
            "-" +
            S4(buf[5]) +
            S4(buf[6]) +
            S4(buf[7])
        );
    }

    function getRandomValues(buf) {
        // Browser
        if ($window.crypto.getRandomValues) {
            return $window.crypto.getRandomValues(buf);
        }
        // Node
        if ($window.crypto.randomBytes) {
            var bytes = $window.crypto.randomBytes(buf.length);
            buf.set(bytes);
        }

        return null;
    }
}
