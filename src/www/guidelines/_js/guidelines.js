$(function() {
    $('div.code').each(function(i, block) {
        var code = $(this).siblings('.example').html();
        $(this).html('<pre><code class="xml">' + escapeHtml(code.replace(/<br><br>/gi, '\n')).trim() + '</code></pre>');
    });

    $('pre > code').each(function(i, block) {
        hljs.highlightBlock(block);
    });

    $('body').scrollspy({ target: '#menu' });

    $('#color-switcher > li').click(function() {
        $('#color-switcher > li').removeClass('active');
        $(this).addClass('active');

        $('body').removeClass();
        $('body').addClass($(this).attr('class'));
    });
});

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
