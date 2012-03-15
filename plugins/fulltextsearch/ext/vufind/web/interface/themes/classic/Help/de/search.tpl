<h1>Hilfe zu den Suchoperatoren</h1>

<ul class="HelpMenu">
  <li><a href="#Wildcard Searches">Suche mit Platzhaltern</a></li>
  <li><a href="#Fuzzy Searches">Unscharfe Suche</a></li>
  <li><a href="#Proximity Searches">Suche nach ähnlichen Wörtern</a></li>
  <li><a href="#Range Searches">Bereichssuche</a></li>
  <li><a href="#Boosting a Term">Wort gewichten</a></li>
  <li><a href="#Boolean operators">Boolsche Operatoren</a>
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
  <dt><a name="Wildcard Searches"></a>Suche mit Platzhaltern</dt>
  <dd>
    <p>Als Platzhalter für ein beliebiges Zeichen verwenden sie<strong>?</strong>.</p>
    <p>Beispiel: Wenn sie nach "Text" oder "Test" suchen wollen geben sie </p>
    <pre class="code">Te?t</pre>
    <p>ein.</p>
    <p>Als Platzhalter für 0 oder mehrere beliebige Zeichen verwenden sie
       <strong>*</strong>.</p>
    <p>Beispiel: Sie wollen nach "Test", "Tests" oder "Tester" suchen, dann geben
       sie:</p>
    <pre class="code">Test*</pre>
    <p>ein</p>
    <p>Sie können Platzhalter irgendwo platzieren:</p>
    <pre class="code">Te*t</pre>
  </dd>
  
  <dt><a name="Fuzzy Searches"></a>Unscharfe Suche</dt>
  <dd>
    <p>Verwenden sie für die unscharfe Suche die Tilde (<strong>~</strong>) am
       Ende eines Wortes.
       Beispiel: Sie wollen ähnlich geschriebene Wörter zu "Raum" erhalten:</p>
    <pre class="code">Raum~</pre>
    <p>In diese Suche werden Wörter, wie "Baum" oder "Rahm" aufgenommen.</p>
    <p>Sie können die Anzahl der Wörter erhöhen oder verringern, indem sie 
       einen Wert zwischen 0 und 1 hinter der Tilde setzen.
       Je kleiner der Wert, desto unschärfer die Suche. Wird der Wert auf 1
       gesetzt, wird nur nach ähnlich geschriebenen Wörtern gesucht.
       Beispiel:</p>
    <pre class="code">roam~0.8</pre>
    <p>Wenn sie nichts angeben, wird der Wert automatische auf 0.5 gesetzt.</p>
  </dd>
  
  <dt><a name="Proximity Searches"></a>Bereichssuche</dt>
  <dd>
    <p>
      Für die Bereichssuche verwenden sie die Tilde (<strong>~</strong>), welches
      sie hinter einer Gruppe von Wörter setzen.
      Beispiel: Um nach Ökonomie und Keynes, welche 10 Wörter entfernt sind, zu
      suchen, geben sie folgende ein:
    </p>
    <pre class="code">"Ökonomie Keynes"~10</pre>
  </dd>
  
  {literal}
  <dt><a name="Range Searches"></a>Bereichssuche</dt>
  <dd>
    <p>
      Für die Bereichssuche verwenden sie geschweifte Klammern (<strong>{ }</strong>).
      Beispiel: Sie wollen ein Wort, welches mit A, B oder C anfängt suchen:
    </p>
    <pre class="code">{A TO C}</pre>
    <p>
      Das gleiche können sie mit Zahlen, wie Jahreszahlen machen:
    </p>
    <pre class="code">[2002 TO 2003]</pre>
  </dd>
  {/literal}
  
  <dt><a name="Boosting a Term"></a>Wort gewichten</dt>
  <dd>
    <p>
      Sie können Wörter mehr Bedeutung zuweisen, indem sie dieses mit einer
      Zahl gewichten. Verwenden sie hierzu <strong>^</strong>. Beispiel:
    </p>
    <pre class="code">economics Keynes^5</pre>
    <p>Dadurch wird das Wort "Keynes" stärker gewichtet und wird bei der Suche
       stärker beachtet.
    </p>
  </dd>

  <dt><a name="Boolean operators"></a>Boolsche Operatoren</dt>
  <dd>
    <p>
      Boolsche Operatoren erlauben es Wörter, logisch miteinander zu verknüpfen.
      Folgende Operatoren sind erlaubt:
      <strong>AND</strong>, <strong>+</strong>, <strong>OR</strong>,
      <strong>NOT</strong> and <strong>-</strong>.
    </p>
    <p>Hinweis: Boolsche Operatoren werden groß geschrieben</p>
    <dl>
      <dt><a name="OR"></a>OR</dt>
      <dd>
        <p>Die Oder-Verknüpfung (<strong>OR</strong>) ist der Standardoperator. Das
            bedeutet, dass wenn zwischen zwei Wörten kein Operator gesetzt wird,
            die Oder-Verknüpfung verwendet wird. Steht eine Oder-Verknüpfung zwischen
            zwei Wörtern, so erhalten sie Treffer in welchem eines oder beide
            Wörter gefunden wurde.
        </p>
        <p>Beispiel: Sie suchen nach Titel, in welchen die Wörter
            "economics Keynes" oder "Keynes" enthalten sind:</p>
        <pre class="code">"economics Keynes" Keynes</pre>
        <p>oder</p>
        <pre class="code">"economics Keynes" OR Keynes</pre>
        <p>ein</p>
      </dd>
      
      <dt><a name="AND"></a>AND</dt>
      <dd>
        <p>Wenn sie zwei Wörter mit <strong>AND</strong> verbinden, erhalten
           sie Treffer, in denen beide Wörter vorhanden sind.</p>
        <p>Beispiel: Sie suchen nach Titel, in welchen die Wörter "economics" und
           "Keynes" enthalten sind:</p>
        <pre class="code">"economics" AND "Keynes"</pre>
      </dd>
      <dt><a name="+"></a>+</dt>
      <dd>
        <p>Indem sie den "+"-Operator hinter einem Wort setzen, erhalten sie
           Treffer in welchem dieses Wort vorhanden ist.
        </p>
        <p>Beispiel: Sie suchen nach Titel, die das Wort "economics" enthalten
           müssen und das Wort "Keynes" erhalten können.</p>
        <pre class="code">+economics Keynes</pre>
      </dd>
      <dt><a name="NOT"></a>NOT</dt>
      <dd>
        <p>Indem sie NOT hinter ein Wort setzen, schliessen sie Treffer aus,
           welche dieses Wort enthalten</p>
        <p>Beispiel: Sie wollen nach Titel suchen, welche das Wort "econmics"
           enthalten aber nicht das Wort "Keynes":</p>
        <pre class="code">"economics" NOT "Keynes"</pre>
        <p>Hinweis: NOT muss mit mindestens zwei Wörtern verwenndet werden.
           Beispielsweise liefert folgende Suche keine Treffer:</p>
        <pre class="code">NOT "economics"</pre>
      </dd>
      <dt><a name="-"></a>-</dt>
      <dd>
        <p>Wird der Operator <strong>-</strong> hinter ein Wort gesetzt, so
           werden alle Treffer ausgefiltert, die dieses Wort enthalten.</p>
        <p>Beispiel: Sie wollen nach Titel suchen, welche das Wort "economics"
           aber nicht das Wort "Keynes" enthalten:</p>
        <pre class="code">"economics" -"Keynes"</pre>
      </dd>
    </dl>
  </dd>
</dl>
