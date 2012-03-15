<script language="JavaScript" type="text/javascript" src="{$path}/services/Browse/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="contentbox" style="margin-right: 15px;">

      <div class="yui-g">
        <div class="yui-g first" style="background-color:#EEE;">
          <div class="yui-u first">
            <div class="browseNav" style="margin: 0px;">
            {include file="Browse/top_list.tpl" currentAction="Author"}
            </div>
          </div>
          <div class="yui-u" id="browse2">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list2">
              <li><a href="{$url}/Browse/Author" onClick="highlightBrowseLink('list2', this); document.getElementById('list4').innerHTML=''; LoadAlphabet('author-letter', 'list3', 'authorStr'); return false">{translate text="By Alphabetical"}</a></li>
              <li><a href="{$url}/Browse/Author" onClick="highlightBrowseLink('list2', this); document.getElementById('list4').innerHTML=''; LoadSubject('callnumber-first', 'list3', 'authorStr'); return false">{translate text="By Call Number"}</a></li>
              <li><a href="{$url}/Browse/Author" onClick="highlightBrowseLink('list2', this); document.getElementById('list4').innerHTML=''; LoadSubject('topic_facet', 'list3', 'authorStr'); return false">{translate text="By Topic"}</a></li>
              <li><a href="{$url}/Browse/Author" onClick="highlightBrowseLink('list2', this); document.getElementById('list4').innerHTML=''; LoadSubject('genre_facet', 'list3', 'authorStr'); return false">{translate text="By Genre"}</a></li>
              <li><a href="{$url}/Browse/Author" onClick="highlightBrowseLink('list2', this); document.getElementById('list4').innerHTML=''; LoadSubject('geographic_facet', 'list3', 'authorStr'); return false">{translate text="By Region"}</a></li>
              <li><a href="{$url}/Browse/Author" onClick="highlightBrowseLink('list2', this); document.getElementById('list4').innerHTML=''; LoadSubject('era', 'list3', 'authorStr'); return false">{translate text="By Era"}</a></li>
            </ul>
            </div>
          </div>
        </div>
        <div class="yui-g">
          <div class="yui-u first" id="browse3">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list3">
            </ul>
            </div>
          </div>
          <div class="yui-u" id="browse4">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list4">
            </ul>
            </div>
          </div>
        </div>
      </div>
  
    </div>
  </div>
</div>