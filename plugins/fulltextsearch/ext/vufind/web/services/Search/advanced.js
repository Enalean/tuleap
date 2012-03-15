var nextGroupNumber = 0;
var groupSearches = new Array();

function addSearch(group, term, field)
{
    if (term  == undefined) {term  = '';}
    if (field == undefined) {field = '';}

    // Keep form content
    protectForm();

    var searchHolder = getElem('group' + group + 'SearchHolder');
    var newSearch = "";

    newSearch += "<div class='advRow'>";
    // Label
    if (groupSearches[group] == 0) {
        newSearch += "<div class='label'>" + searchLabel + " :</div>";
    } else {
        newSearch += "<div class='label'>&nbsp;</div>";
    }
    // Terms
    newSearch += "<div class='terms'><input type='text' name='lookfor" + group + "[]' size='50' value='" + jsEntityEncode(term) + "'/></div>";

    // Field
    newSearch += "<div class='field'>" + searchFieldLabel + " ";
    newSearch += "<select name='type" + group + "[]'>";
    for (key in searchFields) {
        newSearch += "<option value='" + key + "'";
        if (key == field) {
            newSearch += " selected='selected'";
        }
        newSearch += ">" + searchFields[key] + "</option>";
    }
    newSearch += "</select>";
    newSearch += "</div>";

    // Handle floating nonsense
    newSearch += "<span class='clearer'></span>";
    newSearch += "</div>";

    // Done
    searchHolder.innerHTML += newSearch;

    // Actual value doesn't matter once it's not zero.
    groupSearches[group]++;
}

function addGroup(firstTerm, firstField, join)
{
    if (firstTerm  == undefined) {firstTerm  = '';}
    if (firstField == undefined) {firstField = '';}
    if (join       == undefined) {join       = '';}

    // Keep form content
    protectForm();

    var newGroup = "";
    newGroup += "<div id='group" + nextGroupNumber + "' class='group group" + (nextGroupNumber % 2) + "'>";

    newGroup += "<div class='groupSearchDetails'>";
    // Boolean operator drop-down
    newGroup += "<div class='join'>" + searchMatch + " : ";
    newGroup += "<select name='bool" + nextGroupNumber + "[]'>";
    for (key in searchJoins) {
        newGroup += "<option value='" + key + "'";
        if (key == join) {
            newGroup += " selected='selected'";
        }
        newGroup += ">" + searchJoins[key] + "</option>";
    }
    newGroup += "</select>";
    newGroup += "</div>";
    // Delete link
    newGroup += "<a href='javascript:void(0);' class='delete' id='delete_link_" + nextGroupNumber + "' onclick='deleteGroupJS(this);'>" + deleteSearchGroupString + "</a>";
    newGroup += "</div>";

    // Holder for all the search fields
    newGroup += "<div id='group" + nextGroupNumber + "SearchHolder' class='groupSearchHolder'></div>";
    // Add search term link
    newGroup += "<div class='addSearch'><a href='javascript:void(0);' class='add' id='add_search_link_" + nextGroupNumber + "' onclick='addSearchJS(this); return false;'>" + addSearchString + "</a></div>";

    newGroup += "</div>";

    // Set to 0 so adding searches knows
    //   which one is first.
    groupSearches[nextGroupNumber] = 0;

    // Add the new group into the page
    var search = getElem('searchHolder');
    search.innerHTML += newGroup;
    // Add the first search field
    addSearch(nextGroupNumber, firstTerm, firstField);
    // Keep the page in order
    reSortGroups();

    // Pass back the number of this group
    return nextGroupNumber - 1;
}

function deleteGroup(group)
{
    // Find the group
    var group = getElem('group' + group);
    //  And it's parent node
    var parent = group.parentNode;
    // Remove it from the DOM
    parent.removeChild(group);
    // And keep the page in order
    reSortGroups();
}

// Fired by onclick event
function deleteGroupJS(group)
{
    var groupNum = group.id.replace("delete_link_", "");
    deleteGroup(groupNum);
    return false;
}

// Fired by onclick event
function addSearchJS(group)
{
    var groupNum = group.id.replace("add_search_link_", "");
    addSearch(groupNum);
    return false;
}

function reSortGroups()
{
    // Top level holder
    var searchHolder = getElem('searchHolder');
    // Loop through all groups
    var len = searchHolder.childNodes.length;
    var groups = 0;
    for (var i = 0; i < len; i++) {
        // We only want nodes with an ID
        if (searchHolder.childNodes[i].id != undefined) {
            // If the number of this group doesn't
            //   match our running count
            if (searchHolder.childNodes[i].id != "group"+groups) {
                // Re-number this group
                reNumGroup(searchHolder.childNodes[i], groups);
            }
            groups++;
        }
    }
    nextGroupNumber = groups;

    // Hide some group-related controls if there is only one group:
    var groupJoin = getElem('groupJoin');
    if (groupJoin) {
        groupJoin.style.display = nextGroupNumber == 1 ? 'none' : 'block';
    }
    var firstGroup = getElem('delete_link_0');
    if (firstGroup) {
        firstGroup.style.display = nextGroupNumber == 1 ? 'none' : 'inline';
    }

    // If the last group was removed, add an empty group
    if (nextGroupNumber == 0) {
        addGroup();
    }
}

function reNumGroup(oldGroup, newNum)
{
    // Keep the old details for use
    var oldId  = oldGroup.id;
    var oldNum = oldId.substring(5, oldId.length);
    // Which alternating row we're on
    var alt = newNum % 2;

    // Make sure the function was called correctly
    if (oldNum != newNum) {
        // Set the new details
        oldGroup.id = "group" + newNum;
        oldGroup.className = "group group" + alt;

        // Update the delete link with the new ID
        var sDetails = getChildByClass(oldGroup, 'groupSearchDetails');
        var sDelete  = getChildByClass(sDetails, 'delete');
        sDelete.id = "delete_link_" + newNum;

        // Update the bool[] parameter number
        var sJoin = getChildByClass(sDetails, 'join');
        getChildByName(sJoin,  'bool' + oldNum + '[]').name = 'bool' + newNum + '[]';

        // Update the add term link with the new ID
        var sAdd     = getChildByClass(oldGroup, 'addSearch');
        getChildByClass(sAdd, 'add').id = 'add_search_link_' + newNum;

        // Update search holder ID
        var sHolder  = getChildByClass(oldGroup, 'groupSearchHolder');
        sHolder.id = 'group' + newNum + 'SearchHolder';
        // Now loop trough and update all lookfor[] and type[] parameters
        var len = sHolder.childNodes.length;
        var sTerms, sFields;
        for (var i = 0; i < len; i++) {
            sTerms  = getChildByClass(sHolder.childNodes[i],  'terms');
            sFields = getChildByClass(sHolder.childNodes[i],  'field');
            getChildByName(sTerms,  'lookfor' + oldNum + '[]').name = 'lookfor' + newNum + '[]';
            getChildByName(sFields, 'type'    + oldNum + '[]').name = 'type'    + newNum + '[]';
        }
    }
}

function getChildByName(node, childName)
{
    var len = node.childNodes.length;
    for (var i = 0; i < len; i++) {
        if (node.childNodes[i].name == childName) {
            return node.childNodes[i];
        }
    }
}

function getChildByClass(node, childClass)
{
    var len = node.childNodes.length;
    for (var i = 0; i < len; i++) {
        if (node.childNodes[i].className == childClass) {
            return node.childNodes[i];
        }
    }
}

// Only IE will keep the form values in tact
//  after modifying innerHTML unless you run this
function protectForm()
{
    var e = getElem(searchFormId).elements;
    var len = e.length;
    var j, jlen;

    for (var i = 0; i < len; i++) {
        if (e[i].value != e[i].getAttribute('value')) {
            e[i].setAttribute('value', e[i].value);
        }
        if (e[i].type == 'select-one' && e[i].selectedIndex > 0) {
            jlen = e[i].options.length;
            for (j = 0; j < jlen; j++) {
                if (e[i].selectedIndex == j) {
                    e[i].options[j].setAttribute('selected', 'selected');
                } else {
                    e[i].options[j].removeAttribute('selected');
                }
            }
        }
    }
}

// Match all checkbox filters to the 'all' box
function filterAll(element)
{
    // Go through all elements
    var e = getElem(searchFormId).elements;
    var len = e.length;
    for (var i = 0; i < len; i++) {
        //  Look for filters (specifically checkbox filters)
        if (e[i].name == 'filter[]' && e[i].checked != undefined) {
            e[i].checked = element.checked;
        }
    }
}