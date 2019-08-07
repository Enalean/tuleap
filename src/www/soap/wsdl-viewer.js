//eslint-disable-next-line @typescript-eslint/no-unused-vars
function pagingInit() {
    var nav = document.getElementById("nav");
    var tabs = nav.getElementsByTagName("li");
    for (var ii = 0; ii < nav.length; ++ii) {
        tabs[ii].className = "close";
        var aa = nav[ii].getElementsByTagName("a");
        //		aa.onclick = function() { activate(this.href); return false; }
        if (ii == 0) {
            aa.className = "current";
        }
    }
    print("DONE!");
}
