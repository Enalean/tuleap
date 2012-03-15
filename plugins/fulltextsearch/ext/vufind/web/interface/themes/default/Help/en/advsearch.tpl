<h1>Advanced Searching Tips</h1>

<ul class="HelpMenu">
  <li><a href="#Search Fields">Search Fields</a></li>
  <li><a href="#Search Groups">Search Groups</a></li>
</ul>

<dl class="Content">
  <dt><a name="Search Fields"></a>Search Fields</dt>
  <dd>
    <p>When you first visit the Advanced Search page, you are presented with 
       several search fields.  In each field, you can type the keywords you 
       want to search for.  <a href="Home?topic=search">Search operators</a>
       are allowed.</p>
    <p>Each field is accompanied by a drop-down that lets you specify the type 
       of data (title, author, etc.) you are searching for.  You can mix and
       match search types however you like.</p>
    <p>The "Match" setting lets you specify how multiple search fields should
       be handled.</p>
    <ul>
      <li>ALL Terms - Return only records that match every search field.</li>
      <li>ANY Terms - Return any records that match at least one search field.</li>
      <li>NO Terms -- Return all records EXCEPT those that match search fields.</li>
    </ul>
    <p>The "Add Search Field" button may be used to add additional search fields
       to the form.  You may use as many search fields as you wish.</p>
  </dd>
  
  <dt><a name="Search Groups"></a>Search Groups</dt>
  <dd>
    <p>For certain complex searches, a single set of search fields may not be 
       enough.  For example, suppose you want to find books about the history of
       China or India.  If you did an ALL Terms search for China, India, and 
       History, you would only get books about China AND India.  If you did an
       ANY Terms search, you would get books about history that had nothing to
       do with China or India.</p>
    <p>Search Groups provide a way to build searches from multiple groups of
       search fields.  Every time you click the "Add Search Group" button, a new
       group of fields is added.  Once you have multiple search groups, you can
       remove unwanted groups with the "Remove Search Group" button, and you can
       specify whether you want to match on ANY or ALL search groups.</p>
    <p>In the history of China or India example described above, you could solve
       the problem using search groups like this:</p>
    <ul>
      <li>In the first search group, enter "India" and "China" and make sure that
          the "Match" setting is "ANY Terms."</li>
      <li>Add a second search group and enter "history."</li>
      <li>Make sure the match setting next to the Search Groups header is set to
          "ALL Groups."</li>
    </ul>
  </dd>
</dl>
