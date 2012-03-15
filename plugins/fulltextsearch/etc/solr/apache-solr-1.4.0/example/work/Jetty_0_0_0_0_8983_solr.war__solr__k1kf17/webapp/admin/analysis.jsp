<%@ page contentType="text/html; charset=utf-8" pageEncoding="UTF-8"%>
<%--
 Licensed to the Apache Software Foundation (ASF) under one or more
 contributor license agreements.  See the NOTICE file distributed with
 this work for additional information regarding copyright ownership.
 The ASF licenses this file to You under the Apache License, Version 2.0
 (the "License"); you may not use this file except in compliance with
 the License.  You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
--%>
<%@ page import="org.apache.lucene.analysis.Analyzer,
                 org.apache.lucene.analysis.Token,
                 org.apache.lucene.analysis.TokenStream,
                 org.apache.lucene.index.Payload,
                 org.apache.lucene.analysis.CharReader,
                 org.apache.lucene.analysis.CharStream,
                 org.apache.solr.analysis.CharFilterFactory,
                 org.apache.solr.analysis.TokenFilterFactory,
                 org.apache.solr.analysis.TokenizerChain,
                 org.apache.solr.analysis.TokenizerFactory,
                 org.apache.solr.schema.FieldType,
                 org.apache.solr.schema.SchemaField,
                 org.apache.solr.common.util.XML,
                 javax.servlet.jsp.JspWriter,java.io.IOException
                "%>
<%@ page import="java.io.Reader"%>
<%@ page import="java.io.StringReader"%>
<%@ page import="java.util.*"%>
<%@ page import="java.math.BigInteger" %>

<%-- $Id: analysis.jsp 824045 2009-10-11 10:04:01Z koji $ --%>
<%-- $Source: /cvs/main/searching/org.apache.solrolarServer/resources/admin/analysis.jsp,v $ --%>
<%-- $Name:  $ --%>

<%@include file="header.jsp" %>

<%
  // is name a field name or a type name?
  String nt = request.getParameter("nt");
  if (nt==null || nt.length()==0) nt="name"; // assume field name
  nt = nt.toLowerCase().trim();
  String name = request.getParameter("name");
  if (name==null || name.length()==0) name="";
  String val = request.getParameter("val");
  if (val==null || val.length()==0) val="";
  String qval = request.getParameter("qval");
  if (qval==null || qval.length()==0) qval="";
  String verboseS = request.getParameter("verbose");
  boolean verbose = verboseS!=null && verboseS.equalsIgnoreCase("on");
  String qverboseS = request.getParameter("qverbose");
  boolean qverbose = qverboseS!=null && qverboseS.equalsIgnoreCase("on");
  String highlightS = request.getParameter("highlight");
  boolean highlight = highlightS!=null && highlightS.equalsIgnoreCase("on");
%>

<br clear="all">

<h2>Field Analysis</h2>

<form method="POST" action="analysis.jsp" accept-charset="UTF-8">
<table>
<tr>
  <td>
	<strong>Field
          <select name="nt">
	  <option <%= nt.equals("name") ? "selected=\"selected\"" : "" %> >name</option>
	  <option <%= nt.equals("type") ? "selected=\"selected\"" : "" %>>type</option>
          </select></strong>
  </td>
  <td>
	<input class="std" name="name" type="text" value="<% XML.escapeCharData(name, out); %>">
  </td>
</tr>
<tr>
  <td>
	<strong>Field value (Index)</strong>
  <br/>
  verbose output
  <input name="verbose" type="checkbox"
     <%= verbose ? "checked=\"true\"" : "" %> >
    <br/>
  highlight matches
  <input name="highlight" type="checkbox"
     <%= highlight ? "checked=\"true\"" : "" %> >
  </td>
  <td>
	<textarea class="std" rows="8" cols="70" name="val"><% XML.escapeCharData(val,out); %></textarea>
  </td>
</tr>
<tr>
  <td>
	<strong>Field value (Query)</strong>
  <br/>
  verbose output
  <input name="qverbose" type="checkbox"
     <%= qverbose ? "checked=\"true\"" : "" %> >
  </td>
  <td>
	<textarea class="std" rows="1" cols="70" name="qval"><% XML.escapeCharData(qval,out); %></textarea>
  </td>
</tr>
<tr>

  <td>
  </td>

  <td>
	<input class="stdbutton" type="submit" value="analyze">
  </td>

</tr>
</table>
</form>


<%
  SchemaField field=null;

  if (name!="") {
    if (nt.equals("name")) {
      try {
        field = schema.getField(name);
      } catch (Exception e) {
        out.print("<strong>Unknown Field: ");
        XML.escapeCharData(name, out);
        out.println("</strong>");
      }
    } else {
       FieldType t = schema.getFieldTypes().get(name);
       if (null == t) {
        out.print("<strong>Unknown Field Type: ");
        XML.escapeCharData(name, out);
        out.println("</strong>");
       } else {
         field = new SchemaField("fakefieldoftype:"+name, t);
       }
    }
  }

  if (field!=null) {
    HashSet<Tok> matches = null;
    if (qval!="" && highlight) {
      Reader reader = new StringReader(qval);
      Analyzer analyzer =  field.getType().getQueryAnalyzer();
      TokenStream tstream = analyzer.reusableTokenStream(field.getName(),reader);
      tstream.reset();
      List<Token> tokens = getTokens(tstream);
      matches = new HashSet<Tok>();
      for (Token t : tokens) { matches.add( new Tok(t,0)); }
    }

    if (val!="") {
      out.println("<h3>Index Analyzer</h3>");
      doAnalyzer(out, field, val, false, verbose,matches);
    }
    if (qval!="") {
      out.println("<h3>Query Analyzer</h3>");
      doAnalyzer(out, field, qval, true, qverbose,null);
    }
  }

%>


</body>
</html>


<%!
  private static void doAnalyzer(JspWriter out, SchemaField field, String val, boolean queryAnalyser, boolean verbose, Set<Tok> match) throws Exception {

    FieldType ft = field.getType();
     Analyzer analyzer = queryAnalyser ?
             ft.getQueryAnalyzer() : ft.getAnalyzer();
     if (analyzer instanceof TokenizerChain) {
       TokenizerChain tchain = (TokenizerChain)analyzer;
       CharFilterFactory[] cfiltfacs = tchain.getCharFilterFactories();
       TokenizerFactory tfac = tchain.getTokenizerFactory();
       TokenFilterFactory[] filtfacs = tchain.getTokenFilterFactories();

       if( cfiltfacs != null ){
         String source = val;
         for(CharFilterFactory cfiltfac : cfiltfacs ){
           CharStream reader = CharReader.get(new StringReader(source));
           reader = cfiltfac.create(reader);
           if(verbose){
             writeHeader(out, cfiltfac.getClass(), cfiltfac.getArgs());
             source = writeCharStream(out, reader);
           }
         }
       }

       TokenStream tstream = tfac.create(tchain.charStream(new StringReader(val)));
       List<Token> tokens = getTokens(tstream);
       if (verbose) {
         writeHeader(out, tfac.getClass(), tfac.getArgs());
       }

       writeTokens(out, tokens, ft, verbose, match);

       for (TokenFilterFactory filtfac : filtfacs) {
         if (verbose) {
           writeHeader(out, filtfac.getClass(), filtfac.getArgs());
         }

         final Iterator<Token> iter = tokens.iterator();
         tstream = filtfac.create( new TokenStream() {
           public Token next() throws IOException {
             return iter.hasNext() ? iter.next() : null;
           }
          }
         );
         tokens = getTokens(tstream);

         writeTokens(out, tokens, ft, verbose, match);
       }

     } else {
       TokenStream tstream = analyzer.reusableTokenStream(field.getName(),new StringReader(val));
       tstream.reset();
       List<Token> tokens = getTokens(tstream);
       if (verbose) {
         writeHeader(out, analyzer.getClass(), new HashMap<String,String>());
       }
       writeTokens(out, tokens, ft, verbose, match);
     }
  }


  static List<Token> getTokens(TokenStream tstream) throws IOException {
    List<Token> tokens = new ArrayList<Token>();
    while (true) {
      Token t = tstream.next();
      if (t==null) break;
      tokens.add(t);
    }
    return tokens;
  }


  private static class Tok {
    Token token;
    int pos;
    Tok(Token token, int pos) {
      this.token=token;
      this.pos=pos;
    }

    public boolean equals(Object o) {
      return ((Tok)o).token.termText().equals(token.termText());
    }
    public int hashCode() {
      return token.termText().hashCode();
    }
    public String toString() {
      return token.termText();
    }
  }

  private static interface ToStr {
    public String toStr(Object o);
  }

  private static void printRow(JspWriter out, String header, List[] arrLst, ToStr converter, boolean multival, boolean verbose, Set<Tok> match) throws IOException {
    // find the maximum number of terms for any position
    int maxSz=1;
    if (multival) {
      for (List lst : arrLst) {
        maxSz = Math.max(lst.size(), maxSz);
      }
    }


    for (int idx=0; idx<maxSz; idx++) {
      out.println("<tr>");
      if (idx==0 && verbose) {
        if (header != null) {
          out.print("<th NOWRAP rowspan=\""+maxSz+"\">");
          XML.escapeCharData(header,out);
          out.println("</th>");
        }
      }

      for (int posIndex=0; posIndex<arrLst.length; posIndex++) {
        List<Tok> lst = arrLst[posIndex];
        if (lst.size() <= idx) continue;
        if (match!=null && match.contains(lst.get(idx))) {
          out.print("<td class=\"highlight\"");
        } else {
          out.print("<td class=\"debugdata\"");
        }

        // if the last value in the column, use up
        // the rest of the space via rowspan.
        if (lst.size() == idx+1 && lst.size() < maxSz) {
          out.print("rowspan=\""+(maxSz-lst.size()+1)+'"');
        }

        out.print('>');

        XML.escapeCharData(converter.toStr(lst.get(idx)), out);
        out.print("</td>");
      }

      out.println("</tr>");
    }

  }

  static String isPayloadString( Payload p ) {
  	String sp = new String( p.getData() );
	for( int i=0; i < sp.length(); i++ ) {
	if( !Character.isDefined( sp.charAt(i) ) || Character.isISOControl( sp.charAt(i) ) )
	  return "";
	}
	return "(" + sp + ")";
  }

  static void writeHeader(JspWriter out, Class clazz, Map<String,String> args) throws IOException {
    out.print("<h4>");
    out.print(clazz.getName());
    XML.escapeCharData("   "+args,out);
    out.println("</h4>");
  }



  // readable, raw, pos, type, start/end
  static void writeTokens(JspWriter out, List<Token> tokens, final FieldType ft, boolean verbose, Set<Tok> match) throws IOException {

    // Use a map to tell what tokens are in what positions
    // because some tokenizers/filters may do funky stuff with
    // very large increments, or negative increments.
    HashMap<Integer,List<Tok>> map = new HashMap<Integer,List<Tok>>();
    boolean needRaw=false;
    int pos=0;
    for (Token t : tokens) {
      if (!t.termText().equals(ft.indexedToReadable(t.termText()))) {
        needRaw=true;
      }

      pos += t.getPositionIncrement();
      List lst = map.get(pos);
      if (lst==null) {
        lst = new ArrayList(1);
        map.put(pos,lst);
      }
      Tok tok = new Tok(t,pos);
      lst.add(tok);
    }

    List<Tok>[] arr = (List<Tok>[])map.values().toArray(new ArrayList[map.size()]);

    /* Jetty 6.1.3 miscompiles this generics version...
    Arrays.sort(arr, new Comparator<List<Tok>>() {
      public int compare(List<Tok> toks, List<Tok> toks1) {
        return toks.get(0).pos - toks1.get(0).pos;
      }
    }
    */

    Arrays.sort(arr, new Comparator() {
      public int compare(Object toks, Object toks1) {
        return ((List<Tok>)toks).get(0).pos - ((List<Tok>)toks1).get(0).pos;
      }
    }


    );

    out.println("<table width=\"auto\" class=\"analysis\" border=\"1\">");

    if (verbose) {
      printRow(out,"term position", arr, new ToStr() {
        public String toStr(Object o) {
          return Integer.toString(((Tok)o).pos);
        }
      }
              ,false
              ,verbose
              ,null);
    }


    printRow(out,"term text", arr, new ToStr() {
      public String toStr(Object o) {
        return ft.indexedToReadable( ((Tok)o).token.termText() );
      }
    }
            ,true
            ,verbose
            ,match
   );

    if (needRaw) {
      printRow(out,"raw text", arr, new ToStr() {
        public String toStr(Object o) {
          // page is UTF-8, so anything goes.
          return ((Tok)o).token.termText();
        }
      }
              ,true
              ,verbose
              ,match
      );
    }

    if (verbose) {
      printRow(out,"term type", arr, new ToStr() {
        public String toStr(Object o) {
          String tt =  ((Tok)o).token.type();
          if (tt == null) {
             return "null";
          } else {
             return tt;
          }
        }
      }
              ,true
              ,verbose,
              null
      );
    }

    if (verbose) {
      printRow(out,"source start,end", arr, new ToStr() {
        public String toStr(Object o) {
          Token t = ((Tok)o).token;
          return Integer.toString(t.startOffset()) + ',' + t.endOffset() ;
        }
      }
              ,true
              ,verbose
              ,null
      );
    }

    if (verbose) {
      printRow(out,"payload", arr, new ToStr() {
        public String toStr(Object o) {
          Token t = ((Tok)o).token;
          Payload p = t.getPayload();
          if( null != p ) {
            BigInteger bi = new BigInteger( p.getData() );
            String ret = bi.toString( 16 );
            if (ret.length() % 2 != 0) {
              // Pad with 0
              ret = "0"+ret;
            }
            ret += isPayloadString( p );
            return ret;
          }
          return "";			
        }
      }
              ,true
              ,verbose
              ,null
      );
    }
    
    out.println("</table>");
  }

  static String writeCharStream(JspWriter out, CharStream input) throws IOException {
    out.println("<table width=\"auto\" class=\"analysis\" border=\"1\">");
    out.println("<tr>");

    out.print("<th NOWRAP>");
    XML.escapeCharData("text",out);
    out.println("</th>");

    final int BUFFER_SIZE = 1024;
    char[] buf = new char[BUFFER_SIZE];
    int len = 0;
    StringBuilder sb = new StringBuilder();
    do {
      len = input.read( buf, 0, BUFFER_SIZE );
      if( len > 0 )
        sb.append(buf, 0, len);
    } while( len == BUFFER_SIZE );
    out.print("<td class=\"debugdata\">");
    XML.escapeCharData(sb.toString(),out);
    out.println("</td>");
    
    out.println("</tr>");
    out.println("</table>");
    return sb.toString();
  }

%>
