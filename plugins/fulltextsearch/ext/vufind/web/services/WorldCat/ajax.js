function SendEmail(id, to, from, message, strings)
{
    var url = path + "/WorldCat/AJAX";
    var params = "method=SendEmail&" +
                 "id=" + encodeURIComponent(id) + "&" +
                 "from=" + encodeURIComponent(from) + "&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "message=" + encodeURIComponent(message);
    sendAJAXEmail(url, params, strings);
}

function SendSMS(id, to, provider, strings)
{
    var url = path + "/WorldCat/AJAX";
    var params = "id=" + encodeURIComponent(id) + "&" +
                 "method=SendSMS&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "provider=" + encodeURIComponent(provider);
    sendAJAXSMS(url, params, strings);
}