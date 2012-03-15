<form method="GET" action="{$path}/Search/Results">
<span class="graytitle">Search</span>
<ul class="pageitem">
  <li class="form">
    <input type="text" name="lookfor"/>
  </li>
  <li class="form">
    <input type="submit" name="submit" value="{translate text="Find"}">
  </li>
</ul>
</form>

<ul class="pageitem">
  {* TODO: implement advanced search and browse for mobile template
  <li class="menu"><a href="{$path}/Search/Advanced"><img alt="search" src="{$path}/interface/themes/mobile/iWebKit/images/search.png" /><span class="name">Advanced Search</span><span class="arrow"></span></a></li>
  <li class="menu"><a href="{$path}/Browse/Home"><img alt="search" src="{$path}/interface/themes/mobile/iWebKit/images/browse.png" /><span class="name">Browse</span><span class="arrow"></span></a></li>
   *}
  <li class="menu"><a href="{$path}/MyResearch/Home">{* TODO: Find this graphic -- <img alt="search" src="{$path}/interface/themes/mobile/iWebKit/images/login.png" /> *}<span class="name">My Account</span><span class="arrow"></span></a></li>
</ul>
