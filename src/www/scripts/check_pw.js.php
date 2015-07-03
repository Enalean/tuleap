<?php
require_once('pre.php');

header('Content-type: application/x-javascript');
header("Cache-Control: no-cache, no-store, must-revalidate");

?>
/*
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*/
var password_strategy_good_or_bad;
var password_strategy_messages = {
    good:'<?=$GLOBALS['Language']->getText('account_check_pw', 'good')?>',
    bad:'<?=$GLOBALS['Language']->getText('account_check_pw', 'bad')?>'
};
function password_validator_check(element) {
    new Ajax.Request('/include/check_pw.php', {
        parameters: {
            form_pw:element.value
        },
        onComplete:function(transport) {
            var res = eval('('+transport.responseText+')');
            password_validators.each(function (i) {
                $$('.password_validator_msg_'+i).each(function(element) {

                    var child = element.firstChild;

                    if(res.include(i)) {
                        $(child).addClassName('icon-remove');
                        $(child).removeClassName('icon-ok');

                        $(child).addClassName('password_strategy_bad');
                        $(child).removeClassName('password_strategy_good');
                    }
                    else {
                        $(child).addClassName('icon-ok');
                        $(child).removeClassName('icon-remove');

                        $(child).addClassName('password_strategy_good');
                        $(child).removeClassName('password_strategy_bad');
                    }
                });
            });
            $$('.password_strategy_good_or_bad').each(function(element) {
                if (res.length) {
                    $(element).update(password_strategy_messages.bad);
                    $(element).removeClassName('password_strategy_good');
                    $(element).addClassName('password_strategy_bad');
                } else {
                    $(element).update(password_strategy_messages.good);
                    $(element).addClassName('password_strategy_good');
                    $(element).removeClassName('password_strategy_bad');
                }
            });
        }
    });
}

Event.observe(window, 'load', function() {
    $('form_pw').setAttribute('autocomplete', 'off');

    $('form_pw').on('keyup', function(e){
       password_validator_check($('form_pw'));
    });

    $('form_pw').on('focus', function(e){
       password_validator_check($('form_pw'));
    });
});

