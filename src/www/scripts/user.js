new Insertion.After(
    "form_pw",
    '<input type="button" name="generate" class="btn" value="Generate" onclick="setPwd()">'
);

function generate(entropy_bits) {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&%#|-!{?*+";
    function getRandomChar() {
        if (charset.length >= 256) {
            throw "We cannot get a random char from a charset with more than 255 chars";
        }

        const random_byte = new Uint8Array(1);
        const crypto = window.crypto || window.msCrypto;
        crypto.getRandomValues(random_byte);

        if (random_byte[0] >= charset.length) {
            return getRandomChar();
        }

        return charset[random_byte[0]];
    }

    const pass_length = Math.ceil(entropy_bits / (Math.log(charset.length) / Math.LN2));
    let pass = "";
    for (let i = 0; i < pass_length; i++) {
        pass += getRandomChar();
    }
    return pass;
}

function setPwd() {
    $("form_pw").value = generate(128);
}
