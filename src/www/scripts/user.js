new Insertion.After('form_pw','<input type="button" name="generate" class="btn" value="Generate" onclick="setPwd()">');

function generate(){
    var lowercase = "abcdefghijklmnopqrstuvwxyz";
    var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var numbers = "0123456789";
    var specials_chars = "&%#|-!{?*+";
    var pass = "";
    pass += lowercase.substr($random(0,25),1);
    pass += lowercase.substr($random(0,25),1);
    pass += uppercase.substr($random(0,25),1);
    pass += uppercase.substr($random(0,25),1);
    pass += numbers.substr($random(0,9),1);
    pass += numbers.substr($random(0,9),1);
    pass += specials_chars.substr($random(0,9),1);
    pass += specials_chars.substr($random(0,9),1);

    function $random(min, max){
        return Math.floor(Math.random() * (max - min + 1) + min);
    };

    function shuffle(sentence) {
        return sentence.split("").sort(function(a,b) {
                 if($random(0,100)%2 == 0) {
                    return 1;
                 } else {
                    return -1;
                 }
            }).join("");
    }
    pass = shuffle(pass);
    return pass;
}

function setPwd(){
    var password = generate();
    $('form_pw').value=password;
}
