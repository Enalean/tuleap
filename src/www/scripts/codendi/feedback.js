var codendi = codendi || { };

codendi.feedback = {
    log: function (level, msg) {
        var feedback = $('feedback');
        if (feedback) {
            var current = null;
            if (feedback.childElements().size() && (current = feedback.childElements().reverse(0)[0]) && current.hasClassName('feedback_' + level)) {
                current.insert(new Element('li').update(msg));
            } else {
                feedback.insert(new Element('ul').addClassName('feedback_'+level).insert(new Element('li').update(msg)));
            }
        } else {
            alert(level + ': ' + msg);
        }
    },
    clear: function () {
        var feedback = $('feedback');
        if (feedback) {
            feedback.update('');
        }
    }
};

