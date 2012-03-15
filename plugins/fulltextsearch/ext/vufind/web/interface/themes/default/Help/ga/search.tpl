<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<h1>Leideanna Úsáideacha Cuardaigh</h1>

<ul class="HelpMenu">
  <li><a href="#Wildcard Searches">Cuardaigh Shaoróige</a></li>
  <li><a href="#Fuzzy Searches">Cuardaigh Neamhbheachta</a></li>
  <li><a href="#Proximity Searches">Cuardaigh Ghaireachta</a></li>
  <li><a href="#Range Searches">Cuardaigh Réimsí</a></li>
  <li><a href="#Boosting a Term">Treisiú Téarma</a></li>
  <li><a href="#Boolean operators">Oibreoir Boole</a>
    <ul>
      <li><a href="#OR">OR</a></li>
      <li><a href="#AND">AND</a></li>
      <li><a href="#+">+</a></li>
      <li><a href="#NOT">NOT</a></li>
      <li><a href="#-">-</a></li>
    </ul>
  </li>
</ul>

<dl class="Content">
  <dt><a name="Wildcard Searches"></a>Cuardaigh Shaoróige</dt>
  <dd>
    <p>Úsáid an siombail<strong>?</strong> chun cuardach saoróige aon charachtar a dhéanamh.</p>
    <p>Mar shampla, má tá tú ag cuardach &quot;text&quot; nó &quot;test&quot; féadfaidh tú </p>
    <pre class="code">te?t</pre> a úsáid i gcomhair an chuardaigh. 
    <p>Úsáid an siombail <strong>*</strong> chun cuardach saoróige ilcharactar, 0 nó níos mó, a dhéanamh.</p>
    <p>Mar shampla, féadfaidh tú </p>
    <pre class="code">test*</pre> a úsáid chun test, tests nó tester, a chuardach</pre>
    <p>Féadfaidh tú cuardaigh shaoróige a dhéanamh i lár téarma.</p>
    <pre class="code">te*t</pre>
    <p>Tabhair faoi deara: Ní féidir na siombailí * ná ? a úsáid mar chéad charachtar chun cuardach a dhéanamh.</p>
  </dd>
  
  <dt><a name="Fuzzy Searches"></a>Cuardaigh Neamhbheachta</dt>
  <dd>
    <p>Úsáid siombail an tilde <strong>~</strong> ag deireadh Téarma<strong>Aon</strong> fhocail. Mar shampla, úsáid an cuardach neamhbheacht: </p>
    <pre class="code">roam~ </pre>chun cuardach a dhéanamh ar téarma a litrítear ar bhealach cosúil le “roam”.
    <p>Aimseoidh an cuardach seo téarmaí mar “foam” agus “roams”.</p>
    <p>Féadfar paraiméadar breise a chur leis chun an chosúlacht atá riachtanach a shainiú.  Má tá an luach idir 0 agus 1, agus má tá an luach sin níos cóngaraí do 1, ní dhéanfar ach téarmaí a bhfuil cosúlacht níos airde acu a mheaitseáil. Mar shampla:</p>
    <pre class="code">roam~0.8</pre>
    <p>Is é 0.5 an réamhshocrú a úsáidfear mura dtugtar an paraiméadar sin.</p>
  </dd>
  
  <dt><a name="Proximity Searches"></a>Cuardaigh Ghaireachta</dt>
  <dd>
    <p>
      Úsáid siombail an tilde <strong>~</strong> ag deireadh Téarma<strong>Il</strong>fhoclach. Mar shampla, chun cuardach a dhéanamh ar “economics” agus “Keynes” atá laistigh de 10 bhfocal ó chéile: iontráil
    </p>
    <pre class="code">&quot;economics Keynes&quot;~10</pre>
  </dd> {literal} <dt><a name="Range Searches"></a>Cuardaigh Réimsí</dt>
  <dd>
    <p>
      Úsáideann tú na carachtair <strong>{ }</strong> chun cuardach réimse a dhéanamh. Mar shampla, chun cuardach a dhéanamh ar théarma a thosaíonn le A, B, nó C: iontráil
    </p>
    <pre class="code">{A TO C}</pre>
    <p>
      Féadfar an rud céanna a dhéanamh i gcás réimsí mar Bhlianta:
    </p>
    <pre class="code">[2002 TO 2003]</pre>
  </dd> {/literal} <dt><a name="Boosting a Term"></a>Treisiú Téarma</dt>
  <dd>
    <p>
      Féadfaidh tú luach níos mó a thabhairt do théarma trí úsáid an charachtair <strong>^</strong>. Mar shampla, féadfaidh tú triail a bhaint as an gcuardach seo a leanas:
    </p>
    <pre class="code">economics Keynes^5</pre>
    <p>Rud a thabharfaidh luach níos airde don téarma &quot;Keynes&quot;</p>
  </dd>
  
  <dt><a name="Boolean operators"></a>Oibreoirí Boole </dt>
  <dd>
    <p>
      Cinntíonn oibreoirí Boole gur féidir téarmaí a chomhcheangal le hoibreoirí loighce.   Ceadaítear na hoibreoirí seo a leanas: <strong>AND</strong>, <strong>+</strong>, <strong>OR</strong>, <strong>NOT</strong> agus <strong>-</strong>.
    </p>
    <p>Tabhair faoi deara: Ní mór na hoibreoirí Boole a scríobh i gCEANNLITREACHA </p>
    <dl>
      <dt><a name="OR"></a>OR</dt>
      <dd>
        <p>Is é an t-oibreoir <strong>OR</strong> an t-oibreoir réamhshocraithe comhcheangailte Ciallaíonn sé sin go n-úsáidfear an t-oibreoir OR mura bhfuil aon oibreoir Boole idir an dá théarma.  Comhnascann an t-oibreoir OR an dá théarma agus faigheann an taifead meaitseála má tá ceachtar den dá théarma i dtaifead.</p>
        <p>Más mian leat doiciméid a chuardach ina bhfuil &quot;economics Keynes&quot; nó &quot;Keynes&quot; leis féin breac isteach an t-iarratas: </p>
        <pre class="code">&quot;economics Keynes&quot; Keynes</pre>
        <p>nó úsáid</p>
        <pre class="code">&quot;economics Keynes&quot; OR Keynes</pre>
      </dd>
      
      <dt><a name="AND"></a>AND</dt>
      <dd>
        <p>Meaitseálann an t-oibreoir AND na taifid ina bhfuil an dá théarma le fáil aon áit i réimse taifid. </p>
        <p>Breac isteach an t-iarratas: </p>
        <pre class="code">&quot;economics&quot; AND &quot;Keynes”</pre> chun taifid a chuardach ina bhfuil &quot;economics&quot; agus &quot;Keynes&quot;
      </dd>
      <dt><a name="+"></a>+</dt>
      <dd>
        <p>Éilíonn &quot;+&quot; nó an t-oibreoir riachtanach go mbeadh an téarma i ndiaidh na siombaile &quot;+&quot; áit éigin i réimse taifid. </p>
        <p>Chun cuardach a dhéanamh ar thaifid nach mór &quot;economics&quot; a bheith iontu agus a d’fhéadfadh &quot;Keynes&quot; a bheith iontu freisin breac isteach an t-iarratas:</p>
        <pre class="code">+economics Keynes</pre>
      </dd>
      <dt><a name="NOT"></a>NOT</dt>
      <dd>
        <p>Eisíonn an t-oibreoir NOT taifid ina bhfuil an téarma i ndiaidh NOT.</p>
        <p>Más mian leat doiciméid a chuardach ina bhfuil an focal &quot;economics&quot; ach nach bhfuil &quot;Keynes&quot; iontu breac isteach an t-iarratas: </p>
        <pre class="code">&quot;economics&quot; NOT &quot;Keynes&quot;</pre> chun cuardach a dhéanamh ar dhoiciméid ina bhfuil &quot;economics&quot; ach nach bhfuil &quot;Keynes&quot; iontu
        <p>Tabhair faoi deara: Ní féidir an t-oibreoir NOT a úsáid le téarma aonair amháin.  Mar shampla, ní thabharfaidh an cuardach seo aon toradh duit:</p>
        <pre class="code">NOT &quot;economics” </pre>
      </dd>
      <dt><a name="-"></a>-</dt>
      <dd>
        <p>Fágann an t-oibreoir toirmiscthe <strong>-</strong> ar lár doiciméid ina bhfuil an téarma i ndiaidh na siombaile &quot;-&quot;.</p>
        <p>Más mian leat doiciméid a chuardach ina bhfuil an focal &quot;economics&quot; ach nach bhfuil &quot;Keynes&quot; iontu breac isteach an t-iarratas: </p>
        <pre class="code">&quot;economics&quot; -&quot;Keynes&quot;</pre>
      </dd>
    </dl>
  </dd>
</dl> 