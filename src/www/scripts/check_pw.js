/*
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*/

function password_validator_check(element) {
    new Ajax.Request('/include/check_pw.php', {
        parameters: {
            form_pw:element.value
        },
        onComplete:function(transport) {
            var res = eval('('+transport.responseText+')');
            password_validators.each(function (i) {
                if (res.include(i)) {
                    $('password_validator_msg_'+i).addClassName('password_validator_ko');
                    $('password_validator_msg_'+i).removeClassName('password_validator_ok');
                } else {
                    $('password_validator_msg_'+i).addClassName('password_validator_ok');
                    $('password_validator_msg_'+i).removeClassName('password_validator_ko');
                }
            });
        }
    });
}

Event.observe(window, 'load', function() {
    $('form_pw').setAttribute('autocomplete', 'off');
    password_validator_check($('form_pw'));
    new Form.Element.Observer('form_pw', 0.2, password_validator_check);
});

