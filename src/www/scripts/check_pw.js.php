<?php
require_once('pre.php');
$GLOBALS['Language']->loadLanguageMsg('account/account');

header('Content-type: application/x-javascript');

?>
/*
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
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
                if (res.include(i)) {
                    $('password_validator_msg_'+i).addClassName('password_validator_ko');
                    $('password_validator_msg_'+i).removeClassName('password_validator_ok');
                } else {
                    $('password_validator_msg_'+i).addClassName('password_validator_ok');
                    $('password_validator_msg_'+i).removeClassName('password_validator_ko');
                }
            });
            if (res.length) {
                $('password_strategy_good_or_bad').update(password_strategy_messages.bad);
                $('password_strategy_good_or_bad').removeClassName('password_strategy_good');
                $('password_strategy_good_or_bad').addClassName('password_strategy_bad');
            } else {
                $('password_strategy_good_or_bad').update(password_strategy_messages.good);
                $('password_strategy_good_or_bad').addClassName('password_strategy_good');
                $('password_strategy_good_or_bad').removeClassName('password_strategy_bad');
            }
        }
    });
}

Event.observe(window, 'load', function() {
    $('form_pw').setAttribute('autocomplete', 'off');
    password_validator_check($('form_pw'));
    new Form.Element.Observer('form_pw', 0.2, password_validator_check);
});

