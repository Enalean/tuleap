function lightbox()
{
    var lightbox = document.getElementById('lightbox');
    var popupbox = document.getElementById('popupbox');
    var loadMsg = document.getElementById('lightboxLoading').innerHTML;

    popupbox.innerHTML = '<img src="' + path + '/images/loading.gif" /><br />' + loadMsg;
   
    hideSelects('hidden');

    // Find out how far down the screen the user has scrolled.
    var new_top = YAHOO.util.Dom.getDocumentScrollTop();

    //Get the height of the document
    var documentHeight = YAHOO.util.Dom.getDocumentHeight();

    lightbox.style.display='block';
    lightbox.style.height= documentHeight + 'px';
    
    popupbox.style.display='block';
    popupbox.style.top = new_top + 200 + 'px';
    popupbox.style.left='25%';
    popupbox.style.width='50%';
}

function hideLightbox()
{
    var lightbox = document.getElementById('lightbox');
    var popupbox = document.getElementById('popupbox');

    hideSelects('visible');
    lightbox.style.display='none';
    popupbox.style.display='none';
}

function hideSelects(visibility)
{
    selects = document.getElementsByTagName('select');
    for(i = 0; i < selects.length; i++) {
        selects[i].style.visibility = visibility;
    }
}

function toggleMenu(elemId)
{
    var o = document.getElementById(elemId);
    o.style.display = o.style.display == 'block' ? 'none' : 'block';
}

function getElem(id)
{
    if (document.getElementById) {
        return document.getElementById(id);
    } else if (document.all) {
        return document.all[id];
    }
}

function filterAll(element)
{
    // Go through all elements
    var e = getElem('searchForm').elements;
    var len = e.length;
    for (var i = 0; i < len; i++) {
        //  Look for filters (specifically checkbox filters)
        if (e[i].name == 'filter[]' && e[i].checked != undefined) {
            e[i].checked = element.checked;
        }
    }
}

function jsEntityEncode(str)
{
    var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    return new_str;
}