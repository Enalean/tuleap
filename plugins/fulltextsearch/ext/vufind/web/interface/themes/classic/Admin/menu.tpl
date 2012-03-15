<div class="yui-u first">
    <ul id="list1">
      <li{if $action == "Home"} class="active"{/if} style="float: none;"><a href="Home">Home</a><li>
      <li{if $action == "Statistics"} class="active"{/if} style="float: none;"><a href="Statistics">Statistics</a><li>
      <li{if $action == "Config"} class="active"{/if} style="float: none;"><a href="Config">Configuration</a>
        {if $action == "Config"}
        <ul style="padding-left:20px;">
          <li><a href="Config?file=config.ini">General Configuration</a></li>
          <li><a href="Config?file=searchspecs.yaml">Search Specifications</a></li>
          <li><a href="Config?file=searches.ini">Search Settings</a></li>
          <li><a href="Config?file=facets.ini">Facet Settings</a></li>
          <li><a href="Config?file=stopwords.txt">Stop Words</a></li>
          <li><a href="Config?file=synonyms.txt">Synonyms</a></li>
          <li><a href="Config?file=protwords.txt">Protected Words</a></li>
          <li><a href="Config?file=elevate.xml">Elevated Words</a></li>
        </ul>
        {/if}
      </li>
      <li{if $action == "Records"} class="active"{/if} style="float: none;"><a href="Records">Record Management</a>
        {if $action == "Records"}
        <ul style="padding-left:20px;">
          {* not implemented yet <li><a href="">Add Records</a></li> *}
          <li><a href="{$url}/Admin/Records?util=deleteSuppressed">Delete Suppressed</a></li>
          {* not implemented yet <li><a href="">Update Authority</a></li> *}
        </ul>
        {/if}
      </li>
      <li{if $action == "Maintenance"} class="active"{/if} style="float: none;"><a href="Maintenance">System Maintenance</a></li>
      <!--<li{if $action == "Targets"} class="active"{/if} style="float: none;"><a href="Targets">Search Targets</a></li>-->
    </ul>
</div>