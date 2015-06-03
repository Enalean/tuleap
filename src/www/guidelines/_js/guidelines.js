$(function() {
    $('div.code').each(function(i, block) {
        var code = $(this).siblings('.example').html();
        $(this).html('<pre><code class="xml">' + escapeHtml(code.replace(/<br><br>/gi, '\n')).trim() + '</code></pre>');
    });

    $('pre > code').each(function(i, block) {
        hljs.highlightBlock(block);
    });

    $('body').scrollspy({ target: '#menu' });
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
