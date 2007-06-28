<?php

function autocomplete_for_lists_users($input, $autocomplete) {
    ?>
    <div id="<?=$autocomplete?>" class="lists_users_autocomplete"></div>
    <script type="text/javascript">
    Event.observe(window, 'load', function() {
            new Ajax.Autocompleter('<?=$input?>', '<?=$autocomplete?>', '/people/lists_users.php', {
                    paramName: 'search_for',
                    tokens: ',',
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
                    }
            });
    });
    </script>
    <?php
}


?>
