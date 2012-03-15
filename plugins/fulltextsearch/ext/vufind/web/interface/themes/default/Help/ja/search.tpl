<h1>検索演算子の使い方</h1>

<ul class="HelpMenu">
  <li><a href="#Wildcard Searches">ワイルドカード検索</a></li>
  <li><a href="#Fuzzy Searches">あいまい検索</a></li>
  <li><a href="#Proximity Searches">近接検索</a></li>
  <li><a href="#Range Searches">範囲検索</a></li>
  <li><a href="#Boosting a Term">検索語の重み付け</a></li>
  <li><a href="#Boolean operators">ブール演算子</a>
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
  <dt><a name="Wildcard Searches"></a>ワイルドカード検索</dt>
  <dd>
    <p>1文字のワイルドカード検索を行うには <strong>?</strong> 記号を使用します。</p>
    <p>たとえば、"text" または "test" を検索するには次の検索語を使用します。</p>
    <pre class="code">te?t</pre>
    <p>複数（0文字以上）の文字のワイルド検索を行うには <strong>*</strong> 記号を使用します。</p>
    <p>たとえば、"test", "tests" またｈ "tester" を検索するには次のように指定します。</p>
    <pre class="code">test*</pre>
    <p>検索語の中間にもワイルドカード検索を使用することができます。</p>
    <pre class="code">te*t</pre>
    <p>注: 検索語の最初の文字に "*" または "?" 記号を使用することはできません。 </p>
  </dd>
  
  <dt><a name="Fuzzy Searches"></a>あいまい検索</dt>
  <dd>
    <p>あいまい検索を行うには、<strong>単一の</strong>検索語の最後に <strong>~</strong> 記号を
    使用します。たとえば、スペルが "roam" に似た語を検索するには、次のように指定します。</p>
    <pre class="code">roam~</pre>
    <p>これにより、"foam" や "roams" といった語がヒットします。</p>
    <p>類似の程度を指定するには追加のパラメタを使用します。パラメタの値は0から1で、1に近いほど類似度のより高い語がヒットします。たとえば、次のように指定します。</p>
    <pre class="code">roam~0.8</pre>
    <p>パラメタが指定されない場合は、0.5がデフォルトとして使用されます。</p>
  </dd>
  
  <dt><a name="Proximity Searches"></a>近接検索</dt>
  <dd>
    <p>
    近接検索を行うには、<strong>複数の</strong>検索語の最後にチルダ記号 <strong>~</strong> を
    使用します。
    たとえば、10文字以内の間に "economics" と "kynes" が含まれる資料を検索するには、次のように
    指定します。
    </p>
    <pre class="code">"economics Keynes"~10</pre>
  </dd>
  
  {literal}
  <dt><a name="Range Searches"></a>範囲検索</dt>
  <dd>
    <p>
    範囲検索を行うには、<strong>{ }</strong> を使用します。たとえば、"A", "B", "C" の
    いずれかで始まる語を検索するには次のように指定します。
    </p>
    <pre class="code">{A TO C}</pre>
    <p>
      出版年のような数値フィールドでも次のように範囲検索を行うことができます。
    </p>
    <pre class="code">[2002 TO 2003]</pre>
  </dd>
  {/literal}
  
  <dt><a name="Boosting a Term"></a>検索語の重み付け</dt>
  <dd>
    <p>
      検索語により高い重みを与えるには、<strong>^</strong> 文字を使用します。
      たとえば、次の検索を行うと
    </p>
    <pre class="code">economics Keynes^5</pre>
    <p>検索語 "Keynes" がより重視されます。</p>
  </dd>
  
  <dt><a name="Boolean operators"></a>ブール演算子</dt>
  <dd>
    <p>
      ブール演算子により検索語に論理演算を適用できます。次の演算子が使用できます: 
      <strong>AND</strong>, <strong>+</strong>, <strong>OR</strong>, <strong>NOT</strong>, 
      <strong>-</strong>
    </p>
    <p>注: ブール演算子は「すべて大文字」でなければなりません。</p>
    <dl>
      <dt><a name="OR"></a>OR</dt>
      <dd>
        <p><strong>OR</strong> 演算子はデフォルトの結合演算子です。つまり、2つの検索語の間に
        ブール演算子がない場合、OR演算子が使用されます。OR演算子は2つの検索語を結びつけ、
        いずれかの検索語が存在するレコードを検索します。</p>
        <p>"economics Keynes" あるいは単に "Keynes" のいずれかを含む資料を検索するには、次の
        ように指定します。</p>
        <pre class="code">"economics Keynes" Keynes</pre>
        <p>あるいは</p>
        <pre class="code">"economics Keynes" OR Keynes</pre>
      </dd>
      
      <dt><a name="AND"></a>AND</dt>
      <dd>
        <p>AND 演算子はレコードのいずれかのフィールドに2つの検索語の双方が存在するレコードに
        ヒットします。</p>
        <p>"economics" と "Keynes" の双方が含まれるレコードを検索するには、次のように指定します。 </p>
        <pre class="code">"economics" AND "Keynes"</pre>
      </dd>
      <dt><a name="+"></a>+</dt>
      <dd>
        <p>"+" すなわち必須演算子は、"+" 記号が付いた検索語がレコードのいずれかのフィールドに
        必ず存在することを要求します。</p>
        <p>"economics" が必ず含まれ、"Keynes" が含まれていてもいなくても良いレコードを検索するには、次のように
        指定します。</p>
        <pre class="code">+economics Keynes</pre>
      </dd>
      <dt><a name="NOT"></a>NOT</dt>
      <dd>
        <p>NOT 演算子は、NOT の後ろにある検索語を含むレコードを除外します。</p>
        <p>"economics" を含むが、"Keynes" は含まない資料を検索するには、次のように指定します。</p>
        <pre class="code">"economics" NOT "Keynes"</pre>
        <p>注: NOT 演算子は単一の検索語には使用できません。たとえば、次の検索はノーヒットに
        なります。</p>
        <pre class="code">NOT "economics"</pre>
      </dd>
      <dt><a name="-"></a>-</dt>
      <dd>
        <p><Strong>-</strong> すなわち抑制演算子は、"-" 記号の後ろにある検索語を含む資料を除外します。</p>
        <p>economics" を含むが、"Keynes" は含まない資料を検索するには、次のように指定します。</p>
        <pre class="code">"economics" -"Keynes"</pre>
      </dd>
    </dl>
  </dd>
</dl>
