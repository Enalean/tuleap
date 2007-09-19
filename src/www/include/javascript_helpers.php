<?php
function autocomplete_for_users($input, $autocomplete, $options = array()) {
    _autocomplete_for_lists_or_users(0, 1, $input, $autocomplete, $options);
}
function autocomplete_for_lists_users($input, $autocomplete, $options = array()) {
    _autocomplete_for_lists_or_users(1, 1, $input, $autocomplete, $options);
}
function _autocomplete_for_lists_or_users($include_mailinglists, $include_users, $input, $autocomplete, $options = array()) {
    ?>
    <div id="<?=$autocomplete?>" class="lists_users_autocomplete"></div>
    <script type="text/javascript">
    Event.observe(window, 'load', function() {
            new Ajax.Autocompleter('<?=$input?>', '<?=$autocomplete?>', '/autocomplete.php?users=<?=$include_users?>&mailinglists=<?=$include_mailinglists?>', {
                    paramName: 'search_for',
                    method: 'GET',
                    tokens: ',',
                    frequency: 0.25,
                    afterUpdateElement: function (element, selectedElement) {
                        var p = element.value.length; 
                        if (element.setSelectionRange) {
                            setTimeout(function () { // yes, you need this :(
                                this.setSelectionRange(p, p);
                            }.bind(element), 10);
                        } else if (element.createTextRange) {
                            var range = element.createTextRange();
                            range.collapse(true);
                            range.moveEnd('character', p);
                            range.moveStart('character', p);
                            range.select();
                        }
                        <?=isset($options['afterUpdateElement'])?$options['afterUpdateElement']:''?>
                    }
            });
    });
    </script>
    <?php
}


function link_to_remote($text, $url, $options) {
    $onclick = 'new Ajax.';
    if (isset($options['update'])) {
        $onclick .= "Updater('". addslashes($options['update']) ."', ";
    } else {
        $onclick .= 'Request(';
    }
    $onclick .= "'". $url ."');";
    $onclick .= "return false;";
    echo '<a href="'. $url .'" onclick="'. $onclick .'">';
    echo $text;
    echo '</a>';
}
?>
