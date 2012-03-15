<h1>Aide sur la recherche</h1>

<ul class="HelpMenu">
  <li><a href="#Wildcard Searches">Troncature</a></li>
  <li><a href="#Fuzzy Searches">Recherches floues</a></li>
  <li><a href="#Proximity Searches">Recherche par proximité</a></li>
  <li><a href="#Range Searches">Recherches plages</a></li>
  <li><a href="#Boosting a Term">Valoriser un terme</a></li>
  <li><a href="#Boolean operators">Opérateurs  booléens</a>
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
  <dt><a name="Wildcard Searches"></a>Troncature</dt>
  <dd>
    <p>La troncature est un signe qui remplace une ou plusieurs lettres d'un mot.</p>
    <p><strong>?</strong> (point d'interrogation) représente n'importe quel caractère unique.</p>
    <p>Par example, pour rechercher "texte" ou "teste" vous pouvez utiliser:</p>
    <pre class="code">te?t</pre>
    <p>Pour remplacer 0 ou plus de letters d'un mot utilisez <strong>*</strong>
       (astérisque).</p>
    <p>Par example pour rechercher "testes", "teste", "testez" vous pouvez
       utilisez</p>
    <pre class="code">test*</pre>
    <p>L'astérisque représente n'importe quelle chaîne de caractères et peut être
       placé à n'importe quelle place.</p>
    <pre class="code">te*t</pre>
    <p>Notez: Il n'est pas possible d'utilier l'astérisque ou le point
       d'interrogation comme premier signe dans une recherche</p>
  </dd>
  
  <dt><a name="Fuzzy Searches"></a>Recherches floues</dt>
  <dd>
    <p>Utilisez le tilde <strong>~</strong> à la fin d'un mot. Par exemple, pour
       chercher un mot semblable à "amant" vous utilisez la recherche floue:
       <pre class="code">amant~</pre>
       <p>Cette recherche trouvera des termes comme diamant ou aimant</p>
       <p>Un paramètre suplémentaire définit le niveau de similarité. La
          valeur du paramètre peut être entre 0 et 1. Plus le chiffre est proche
          de 1, plus les mots utilisés pour la recherche sont similaire au mot
          de départ. Exemple:</p>
    <pre class="code">amant~0.8</pre>
    <p>La valeur du paramètre par défaut est 0.5</p>
  </dd>

  <dt><a name="Proximity Searches"></a>Recherche par proximité</dt>
  <dd>
    <p>
      Ajoutez le tilde <strong>~</strong> après plusieurs mots. Par exemple:
      Pour rechercher "pain" et "boulangerie" avec une distance entre les termes
      de recherche de 10 mots vous tapez dans le champ de recherche:</p>
    <pre class="code">"pain boulangerie"~10</pre>
  </dd>
  
  {literal}
  <dt><a name="Range Searches"></a>Recherche plage</dt>
  <dd>
    <p>
      Pour faire une recherche plage (range search) vous devez utiliser les
      parenthèses <strong>{ }</strong>. Par exemple pour chercher un terme qui
      débute par A, B ou C vous tapez:
    </p>
    <pre class="code">{A TO C}</pre>
    <p>
      La même chose peut être appliqué sur des nombres comme par exemple
      sur l'année:
    </p>
    <pre class="code">[2002 TO 2003]</pre>
  </dd>
  {/literal}
  
  <dt><a name="Boosting a Term"></a>Valoriser un terme</dt>
  <dd>
    <p>
      Pour donner à un terme une importance plus élevé dans la recheche utilisez
      l'accent circonflexe<strong>^</strong>. Par exemple, si vous essayez la
      recherche suivante,
    </p>
    <pre class="code">pain boulangerie^5</pre>
    <p>vous obtiendrez plus de résultats avec le mot "boulangerie".</p>
  </dd>
  
  <dt><a name="Boolean operators"></a>Opérateurs booléens</dt>
  <dd>
    <p>
      Les opérateurs booléens permettent de combiner des recherches afin de les
      expliciter et/ou de les affiner. Les opérateurs booléens suivant sont
      possible:
      <strong>AND</strong>, <strong>+</strong>, <strong>OR</strong>, <strong>NOT</strong> and <strong>-</strong>.
    </p>
    <p>Notez: Les opérateurs booléens doivent être ecrit en lettre majuscules</p>
    <dl>
      <dt><a name="OR"></a>OR</dt>
      <dd>
        <p>L'oppérateur <strong>OR</strong> (OU) est l'opérateur par défaut. Ce
           qui veut dire, que si vous faitez une recherche avec deux termes sans
           l'opérateur OR, le moteur de recherche utilse automatiquement
           l'opérateur OR.
           Au moins l'un de termes reliès par OR doit être présent dans le
           résultat.</p>
        <p>Pour rechercher un document qui présente le terme "miammiam le croissant"
           ou seulement le terme "croissant", utilisez la recherche suivante:
        </p>
        <pre class="code">"miammiam le croissant" croissant</pre>
        <p>ou</p>
        <pre class="code">"miammiam le croissant" OR croissant</pre>
      </dd>
      
      <dt><a name="AND"></a>AND</dt>
      <dd>
          <p>Utilisez <strong>AND</strong> pour croiser plusieurs termes de 
             recherche. Vous obtiendrez les notices bibliographiques qui
             contiennent tous les mots recherchés.Il permet d'affiner la
             recherche ou de réduire le nombre de réponses.</p>
        <p>Example, les resultats contiendront "Théodore Monod et désert si vous
           utilisez la recherche suicante:</p>
        <pre class="code">"Théodore Monod " AND "désert"</pre>
      </dd>
      <dt><a name="+"></a>+</dt>
      <dd>
        <p>Pour obtenir des resultats avec un certain terme en utilisant plusieurs
            termes dans une recherche, utilisez le signe plus <strong>+</strong>.
            Placez le signe plus devant le mot que vous voulez rechercher.
        </p>
        <p>Par exemple, si vous voulez des resultats qui contiennent "miau" et
            peut être "chat", utilisez la recherche suivante:
        </p>
        <pre class="code">+miau chat</pre>
      </dd>
      <dt><a name="NOT"></a>NOT</dt>
      <dd>
        <p>L'opérateur NOT permet d'exclure des termes de la recherche.</p>
        <p>Pour obtenir des resultats qui contiennent le terme "raton" mais pas
            "laveur", utilisez la recherche suivantes: </p>
        <pre class="code">"raton" NOT "laveur"</pre>
        <p>Notez: L'opérateur NOT ne peut pas être utilisé avec un seul terme.
           L'exemple suivant donne aucun resultat:</p>
        <pre class="code">NOT "inconnus"</pre>
      </dd>
      <dt><a name="-"></a>-</dt>
      <dd>
        <p>Le signe moins <strong>-</strong> permet d'exclure le terme de la
           recherche qui se situe derriere l'opérateur.</p>
        <p>Pour rechercher tous les documents qui contiennent le term
           "Valéry" mais pas "Giscard" utilisez la recherche suivante: </p>
        <pre class="code">"Valéry" -"Giscard"</pre>
      </dd>
    </dl>
  </dd>
</dl>
