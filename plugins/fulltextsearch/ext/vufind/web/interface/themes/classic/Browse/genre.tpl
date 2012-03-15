<script language="JavaScript" type="text/javascript" src="{$path}/services/Browse/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="contentbox" style="margin-right: 15px;">

      <div class="yui-g">
        <div class="yui-g first">
          <div class="yui-u first">
            <div class="browseNav" style="margin: 0px;">
            {include file="Browse/top_list.tpl" currentAction="Genre"}
            </div>
          </div>
          <div class="yui-u" id="browse2">
            <div class="browseNav" style="margin: 0px;">
            <ul class="browse" id="list2">
              <li><a href="{$url}/Browse/Genre" onClick="highlightBrowseLink('list2', this); LoadAlphabet('genre_facet', 'list3', 'genre_facet'); return false">{translate text="By Alphabetical"}</a></li>
              <li><a href="{$url}/Browse/Genre" onClick="highlightBrowseLink('list2', this); LoadSubject('topic_facet', 'list3', 'genre_facet'); return false">{translate text="By Topic"}</a></li>
              <li><a href="{$url}/Browse/Genre" onClick="highlightBrowseLink('list2', this); LoadSubject('geographic_facet', 'list3', 'genre_facet'); return false">{translate text="By Region"}</a></li>
              <li><a href="{$url}/Browse/Genre" onClick="highlightBrowseLink('list2', this); LoadSubject('era', 'list3', 'genre_facet'); return false">{translate text="By Era"}</a></li>
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