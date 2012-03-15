<form method="GET" action="{$url}/Search/Results" name="searchForm" class="search">
  <ul class="pageitem">
    <input type="hidden" name="type[]" value="title">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_title"}"></li>
    <input type="hidden" name="type[]" value="subject">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_subject"}"></li>
    <input type="hidden" name="type[]" value="author">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_author"}"></li>
    <input type="hidden" name="type[]" value="publisher">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_publisher"}"></li>
    <input type="hidden" name="type[]" value="series">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_series"}"></li>
    <input type="hidden" name="type[]" value="callnumber">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_callnumber"}"></li>
    <input type="hidden" name="type[]" value="isn">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_isn"}"></li>
    <input type="hidden" name="type[]" value="toc">
    <li class="form"><input type="text" name="lookfor[]" placeholder="{translate text="adv_search_toc"}"></li>
  </ul>
  <ul class="pageitem">
    <li class="form"><input type="submit" name="submit" value="{translate text="Find"}"></li>
  </ul>
</form>
