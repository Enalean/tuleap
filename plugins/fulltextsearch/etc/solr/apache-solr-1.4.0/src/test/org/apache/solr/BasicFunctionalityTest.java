/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

package org.apache.solr;

import org.apache.lucene.document.*;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.BooleanQuery;
import org.apache.solr.common.params.AppendedSolrParams;
import org.apache.solr.common.params.CommonParams;
import org.apache.solr.common.params.DefaultSolrParams;
import org.apache.solr.common.params.MapSolrParams;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.core.SolrCore;
import org.apache.solr.search.*;
import org.apache.solr.handler.*;
import org.apache.solr.request.*;
import org.apache.solr.util.*;
import org.apache.solr.schema.*;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.DocumentBuilder;
import java.io.IOException;
import java.io.StringWriter;
import java.io.ByteArrayInputStream;
import java.util.Map;
import java.util.HashMap;
import java.util.List;
import java.util.ArrayList;

/**
 * Tests some basic functionality of Solr while demonstrating good
 * Best Practices for using AbstractSolrTestCase
 */
public class BasicFunctionalityTest extends AbstractSolrTestCase {

  public String getSchemaFile() { return "schema.xml"; }
  public String getSolrConfigFile() { return "solrconfig.xml"; }
  public String getCoreName() { return "basic"; }

  public void setUp() throws Exception {
    // if you override setUp or tearDown, you better call
    // the super classes version
    super.setUp();
  }
  public void tearDown() throws Exception {
    // if you override setUp or tearDown, you better call
    // the super classes version
    super.tearDown();

  }

  // tests the performance of dynamic field creation and
  // field property testing.
  /***
  public void testFieldPerf() {
    IndexSchema schema = h.getCore().getSchema();
    SchemaField[] fields = schema.getDynamicFieldPrototypes();
    boolean createNew = false;

    long start = System.currentTimeMillis();
    int ret = 0;
    for (int i=0; i<10000000; i++) {
      for (SchemaField f : fields) {
        if (createNew) f = new SchemaField(f, "fakename");
        if (f.indexed()) ret += 1;
        if (f.isCompressed()) ret += 2;
        if (f.isRequired()) ret += 3;
        if (f.multiValued()) ret += 4;
        if (f.omitNorms()) ret += 5;
        if (f.sortMissingFirst()) ret += 6;
        if (f.sortMissingLast())ret += 7;
        if (f.stored()) ret += 8;
        if (f.storeTermOffsets()) ret += 9;
        if (f.storeTermPositions()) ret += 10;
        if (f.storeTermVector()) ret += 11;
      }
    }
    long end = System.currentTimeMillis();
    System.out.println("ret=" + ret + " time="+ (end-start));
  }
  ***/
  
  public void testIgnoredFields() throws Exception {
    lrf.args.put("version","2.0");
    assertU("adding doc with ignored field",
            adoc("id", "42", "foo_ignored", "blah blah"));
    assertU("commit",
            commit());
    
    // :TODO: the behavior of querying on an unindexed field should be better specified in the future.
    assertQ("query with ignored field",
            req("bar_ignored:yo id:42")
            ,"//*[@numFound='1']"
            ,"//int[@name='id'][.='42']"
            );
  }

  public void testSomeStuff() throws Exception {
    lrf.args.put("version","2.0");
    assertQ("test query on empty index",
            req("qlkciyopsbgzyvkylsjhchghjrdf")
            ,"//result[@numFound='0']"
            );

    // test escaping of ";"
    assertU("deleting 42 for no reason at all",
            delI("42"));
    assertU("adding doc#42",
            adoc("id", "42", "val_s", "aa;bb"));
    assertU("does commit work?",
            commit());

    assertQ("backslash escaping semicolon",
            req("id:42 AND val_s:aa\\;bb")
            ,"//*[@numFound='1']"
            ,"//int[@name='id'][.='42']"
            );

    assertQ("quote escaping semicolon",
            req("id:42 AND val_s:\"aa;bb\"")
            ,"//*[@numFound='1']"
            ,"//int[@name='id'][.='42']"
            );

    assertQ("no escaping semicolon",
            req("id:42 AND val_s:aa")
            ,"//*[@numFound='0']"
            );

    assertU(delI("42"));
    assertU(commit());
    assertQ(req("id:42")
            ,"//*[@numFound='0']"
            );

    // test allowDups default of false

    assertU(adoc("id", "42", "val_s", "AAA"));
    assertU(adoc("id", "42", "val_s", "BBB"));
    assertU(commit());
    assertQ(req("id:42")
            ,"//*[@numFound='1']"
            ,"//str[.='BBB']"
            );
    assertU(adoc("id", "42", "val_s", "CCC"));
    assertU(adoc("id", "42", "val_s", "DDD"));
    assertU(commit());
    assertQ(req("id:42")
            ,"//*[@numFound='1']"
            ,"//str[.='DDD']"
            );

    // test deletes
    String [] adds = new String[] {
      add( doc("id","101"), "allowDups", "false" ),
      add( doc("id","101"), "allowDups", "false" ),
      add( doc("id","105"), "allowDups", "true"  ),
      add( doc("id","102"), "allowDups", "false" ),
      add( doc("id","103"), "allowDups", "true"  ),
      add( doc("id","101"), "allowDups", "false" ),
    };
    for (String a : adds) {
      assertU(a, a);
    }
    assertU(commit());

    // test maxint
    assertQ(req("q","id:[100 TO 110]", "rows","2147483647")
            ,"//*[@numFound='4']"
            );

    // test big limit
    assertQ(req("q","id:[100 TO 111]", "rows","1147483647")
            ,"//*[@numFound='4']"
            );

    assertQ(req("id:[100 TO 110]")
            ,"//*[@numFound='4']"
            );
    assertU(delI("102"));
    assertU(commit());
    assertQ(req("id:[100 TO 110]")
            ,"//*[@numFound='3']"
            );
    assertU(delI("105"));
    assertU(commit());
    assertQ(req("id:[100 TO 110]")
            ,"//*[@numFound='2']"
            );
    assertU(delQ("id:[100 TO 110]"));
    assertU(commit());
    assertQ(req("id:[100 TO 110]")
            ,"//*[@numFound='0']"
            );
  }

  public void testRequestHandlerBaseException() {
    final String tmp = "BOO!";
    SolrRequestHandler handler = new RequestHandlerBase() {
        public String getDescription() { return tmp; }
        public String getSourceId() { return tmp; }
        public String getSource() { return tmp; }
        public String getVersion() { return tmp; }
        public void handleRequestBody
          ( SolrQueryRequest req, SolrQueryResponse rsp ) {
          throw new RuntimeException(tmp);
        }
      };
    handler.init(new NamedList());
    SolrQueryResponse rsp = new SolrQueryResponse();
    h.getCore().execute(handler, 
                        new LocalSolrQueryRequest(h.getCore(),
                                                  new NamedList()),
                        rsp);
    assertNotNull("should have found an exception", rsp.getException());
                        
  }

  public void testMultipleUpdatesPerAdd() {

    // big freaking kludge since the response is currently not well formed.
    String res = h.update("<add><doc><field name=\"id\">1</field></doc><doc><field name=\"id\">2</field></doc></add>");
    assertEquals("<result status=\"0\"></result>", res);
    assertU("<commit/>");
    assertQ(req("id:[0 TO 99]")
            ,"//*[@numFound='2']"
            );

  }

  public void testDocBoost() throws Exception {
    String res = h.update("<add>" + "<doc><field name=\"id\">1</field>"+
                                          "<field name=\"text\">hello</field></doc>" + 
                          "<doc boost=\"2.0\"><field name=\"id\">2</field>" +
                                          "<field name=\"text\">hello</field></doc>" + 
                          "</add>");

    assertEquals("<result status=\"0\"></result>", res);
    assertU("<commit/>");
    assertQ(req("text:hello")
            ,"//*[@numFound='2']"
            );
    String resp = h.query(lrf.makeRequest("q", "text:hello", "debugQuery", "true"));
    //System.out.println(resp);
    // second doc ranked first
    assertTrue( resp.indexOf("\"2\"") < resp.indexOf("\"1\"") );
  }

  public void testFieldBoost() throws Exception {
    String res = h.update("<add>" + "<doc><field name=\"id\">1</field>"+
                                      "<field name=\"text\">hello</field></doc>" + 
                                    "<doc><field name=\"id\">2</field>" +
                                      "<field boost=\"2.0\" name=\"text\">hello</field></doc>" + 
                          "</add>");

    assertEquals("<result status=\"0\"></result>", res);
    assertU("<commit/>");
    assertQ(req("text:hello"),
            "//*[@numFound='2']"
            );
    String resp = h.query(lrf.makeRequest("q", "text:hello", "debugQuery", "true"));
    //System.out.println(resp);
    // second doc ranked first
    assertTrue( resp.indexOf("\"2\"") < resp.indexOf("\"1\"") );
  }

  public void testXMLWriter() throws Exception {

    SolrQueryResponse rsp = new SolrQueryResponse();
    rsp.add("\"quoted\"", "\"value\"");

    StringWriter writer = new StringWriter(32000);
    XMLWriter.writeResponse(writer,req("foo"),rsp);

    DocumentBuilder builder = DocumentBuilderFactory.newInstance().newDocumentBuilder();
    builder.parse(new ByteArrayInputStream
                  (writer.toString().getBytes("UTF-8")));
  }

  public void testLocalSolrQueryRequestParams() {
    HashMap args = new HashMap();
    args.put("string", "string value");
    args.put("array", new String[] {"array", "value"});
    SolrQueryRequest req = new LocalSolrQueryRequest(null, null, null, 0, 20, args);
    assertEquals("string value", req.getParams().get("string"));
    assertEquals("array", req.getParams().get("array"));

    String[] stringParams = req.getParams().getParams("string");
    assertEquals(1, stringParams.length);
    assertEquals("string value", stringParams[0]);

    String[] arrayParams = req.getParams().getParams("array");
    assertEquals(2, arrayParams.length);
    assertEquals("array", arrayParams[0]);
    assertEquals("value", arrayParams[1]);
  }

  public void testKeywordTokenizerFactory() {

    assertU(adoc("id", "42",
                 "keywordtok", "How nOw broWn-ish C.o.w. ?"));
    assertU(commit());
    assertQ("stored value matches?",
            req("id:42")
            ,"//str[.='How nOw broWn-ish C.o.w. ?']"
            );
    assertQ("query on exact matches?",
            req("keywordtok:\"How nOw broWn-ish C.o.w. ?\"")
            ,"//str[.='How nOw broWn-ish C.o.w. ?']"
            );
  }

  /** @see org.apache.solr.analysis.TestRemoveDuplicatesTokenFilter */
  public void testRemoveDuplicatesTokenFilter() {
    Query q = QueryParsing.parseQuery("TV", "dedup",
                                      h.getCore().getSchema());
    assertTrue("not boolean?", q instanceof BooleanQuery);
    assertEquals("unexpected number of stemmed synonym tokens",
                 2, ((BooleanQuery) q).clauses().size());
  }

  
  public void testTermVectorFields() {
    
    IndexSchema ischema = new IndexSchema(solrConfig, getSchemaFile(), null);
    SchemaField f; // Solr field type
    Field luf; // Lucene field

    f = ischema.getField("test_basictv");
    luf = f.createField("test", 0f);
    assertTrue(f.storeTermVector());
    assertTrue(luf.isTermVectorStored());

    f = ischema.getField("test_notv");
    luf = f.createField("test", 0f);
    assertTrue(!f.storeTermVector());
    assertTrue(!luf.isTermVectorStored());    

    f = ischema.getField("test_postv");
    luf = f.createField("test", 0f);
    assertTrue(f.storeTermVector() && f.storeTermPositions());
    assertTrue(luf.isStorePositionWithTermVector());

    f = ischema.getField("test_offtv");
    luf = f.createField("test", 0f);
    assertTrue(f.storeTermVector() && f.storeTermOffsets());
    assertTrue(luf.isStoreOffsetWithTermVector());

    f = ischema.getField("test_posofftv");
    luf = f.createField("test", 0f);
    assertTrue(f.storeTermVector() && f.storeTermPositions() && f.storeTermOffsets());
    assertTrue(luf.isStoreOffsetWithTermVector() && luf.isStorePositionWithTermVector());

  }


  public void testSolrParams() throws Exception {
    NamedList nl = new NamedList();
    nl.add("i",555);
    nl.add("s","bbb");
    nl.add("bt","true");
    nl.add("bf","false");

    Map<String,String> m = new HashMap<String,String>();
    m.put("f.field1.i", "1000");
    m.put("s", "BBB");
    m.put("ss", "SSS");

    LocalSolrQueryRequest req = new LocalSolrQueryRequest(null,nl);
    SolrParams p = req.getParams();

    assertEquals(p.get("i"), "555");
    assertEquals(p.getInt("i").intValue(), 555);
    assertEquals(p.getInt("i",5), 555);
    assertEquals(p.getInt("iii",5), 5);
    assertEquals(p.getFieldParam("field1","i"), "555");

    req.setParams(new DefaultSolrParams(p, new MapSolrParams(m)));
    p = req.getParams();
    assertEquals(req.getOriginalParams().get("s"), "bbb");
    assertEquals(p.get("i"), "555");
    assertEquals(p.getInt("i").intValue(), 555);
    assertEquals(p.getInt("i",5), 555);
    assertEquals(p.getInt("iii",5), 5);

    assertEquals(p.getFieldParam("field1","i"), "1000");
    assertEquals(p.get("s"), "bbb");
    assertEquals(p.get("ss"), "SSS");

    assertEquals(!!p.getBool("bt"), !p.getBool("bf"));
    assertEquals(p.getBool("foo",true), true);
    assertEquals(p.getBool("foo",false), false);
    assertEquals(!!p.getBool("bt"), !p.getBool("bf"));

    NamedList more = new NamedList();
    more.add("s", "aaa");
    more.add("s", "ccc");
    more.add("ss","YYY");
    more.add("xx","XXX");
    p = new AppendedSolrParams(p, SolrParams.toSolrParams(more));
    assertEquals(3, p.getParams("s").length);
    assertEquals("bbb", p.getParams("s")[0]);
    assertEquals("aaa", p.getParams("s")[1]);
    assertEquals("ccc", p.getParams("s")[2]);
    assertEquals(3, p.getParams("s").length);
    assertEquals("SSS", p.get("ss"));
    assertEquals("XXX", p.get("xx"));

    
  }


  public void testDefaultFieldValues() {
    
    assertU(adoc("id",  "4055",
                 "subject", "Hoss the Hoss man Hostetter"));
    assertU(adoc("id",  "4056",
                 "intDefault", "4",
                 "subject", "Some Other Guy"));
    assertU(adoc("id",  "4057",
                 "multiDefault", "a",
                 "multiDefault", "b",
                 "subject", "The Dude"));
    assertU(commit());

    assertQ("everthing should have recent timestamp",
            req("timestamp:[NOW-10MINUTES TO NOW]")
            ,"*[count(//doc)=3]"
            ,"//date[@name='timestamp']"
            );
    
    assertQ("2 docs should have the default for multiDefault",
            req("multiDefault:muLti-Default")
            ,"*[count(//doc)=2]"
            ,"//arr[@name='multiDefault']"
            );
    assertQ("1 doc should have it's explicit multiDefault",
            req("multiDefault:a")
            ,"*[count(//doc)=1]"
            );

    assertQ("2 docs should have the default for intDefault",
            req("intDefault:42")
            ,"*[count(//doc)=2]"
            );
    assertQ("1 doc should have it's explicit intDefault",
            req("intDefault:[3 TO 5]")
            ,"*[count(//doc)=1]"
            );
    
  }


  public void testTokenizer() {

    assertU(adoc("id",  "4055",
            "patterntok", "Hello,There"));
    assertU(adoc("id",  "4056",
            "patterntok", "Goodbye,Now"));
    assertU(commit());

    assertQ("make sure it split ok",
            req("patterntok:Hello")
            ,"*[count(//doc)=1]"
    );
    assertQ("make sure it split ok",
            req("patterntok:Goodbye")
            ,"*[count(//doc)=1]"
    );
  }

  public void testConfigDefaults() {
    assertU(adoc("id", "42",
                 "name", "Zapp Brannigan"));
    assertU(adoc("id", "43",
                 "title", "Democratic Order of Planets"));
    assertU(adoc("id", "44",
                 "name", "The Zapper"));
    assertU(adoc("id", "45",
                 "title", "25 star General"));
    assertU(adoc("id", "46",
                 "subject", "Defeated the pacifists of the Gandhi nebula"));
    assertU(adoc("id", "47",
                 "text", "line up and fly directly at the enemy death cannons, clogging them with wreckage!"));
    assertU(commit());

    assertQ("standard request handler returns all matches",
            req("id:[42 TO 47]"),
            "*[count(//doc)=6]"
            );

    assertQ("defaults handler returns fewer matches",
            req("q", "id:[42 TO 47]",   "qt","defaults"),
            "*[count(//doc)=4]"
            );

    assertQ("defaults handler includes highlighting",
            req("q", "name:Zapp OR title:General",   "qt","defaults"),
            "//lst[@name='highlighting']"
            );

  }

  private String mkstr(int len) {
    StringBuilder sb = new StringBuilder(len);
    for (int i = 0; i < len; i++) {
      sb.append((char)(65 + i%26));
    }
    return new String(sb);
  }   
  public void testCompressableFieldType() {
    
    IndexSchema ischema = new IndexSchema(solrConfig, getSchemaFile(), null);
    SchemaField f; // Solr field type
    Field luf; // Lucene field

    f = ischema.getField("test_hlt");
    luf = f.createField("test", 0f);
    assertFalse(luf.isCompressed());
    assertTrue(luf.isStored());

    f = ischema.getField("test_hlt");
    luf = f.createField(mkstr(345), 0f);
    assertTrue(luf.isCompressed());
    assertTrue(luf.isStored());

    f = ischema.getField("test_hlt_off");
    luf = f.createField(mkstr(400), 0f);
    assertFalse(luf.isCompressed());
    assertTrue(luf.isStored());
    
  }

  public void testNotLazyField() throws IOException {
    for(int i = 0; i < 10; i++) {
      assertU(adoc("id", new Integer(i).toString(), 
                   "title", "keyword",
                   "test_hlt", mkstr(20000)));
    }
    assertU(commit());
    SolrCore core = h.getCore();
   
    SolrQueryRequest req = req("q", "title:keyword", "fl", "id,title,test_hlt");
    SolrQueryResponse rsp = new SolrQueryResponse();
    core.execute(core.getRequestHandler(req.getParams().get(CommonParams.QT)), req, rsp);

    DocList dl = (DocList) rsp.getValues().get("response");
    org.apache.lucene.document.Document d = req.getSearcher().doc(dl.iterator().nextDoc());
    // ensure field is not lazy
    assertTrue( d.getFieldable("test_hlt") instanceof Field );
    assertTrue( d.getFieldable("title") instanceof Field );
  }

  public void testLazyField() throws IOException {
    for(int i = 0; i < 10; i++) {
      assertU(adoc("id", new Integer(i).toString(), 
                   "title", "keyword",
                   "test_hlt", mkstr(20000)));
    }
    assertU(commit());
    SolrCore core = h.getCore();
    
    SolrQueryRequest req = req("q", "title:keyword", "fl", "id,title");
    SolrQueryResponse rsp = new SolrQueryResponse();
    core.execute(core.getRequestHandler(req.getParams().get(CommonParams.QT)), req, rsp);

    DocList dl = (DocList) rsp.getValues().get("response");
    DocIterator di = dl.iterator();    
    org.apache.lucene.document.Document d = req.getSearcher().doc(di.nextDoc());
    // ensure field is lazy
    assertTrue( !( d.getFieldable("test_hlt") instanceof Field ) );
    assertTrue( d.getFieldable("title") instanceof Field );
  } 
            

  /** @see org.apache.solr.util.DateMathParserTest */
  public void testDateMath() {

    // testing everything from query level is hard because
    // time marches on ... and there is no easy way to reach into the
    // bowels of DateField and muck with the definition of "now"
    //    ...
    // BUT: we can test that crazy combinations of "NOW" all work correctly,
    // assuming the test doesn't take too long to run...

    final String july4 = "1976-07-04T12:08:56.235Z";
    assertU(adoc("id", "1",  "bday", july4));
    assertU(adoc("id", "2",  "bday", "NOW"));
    assertU(adoc("id", "3",  "bday", "NOW/HOUR"));
    assertU(adoc("id", "4",  "bday", "NOW-30MINUTES"));
    assertU(adoc("id", "5",  "bday", "NOW+30MINUTES"));
    assertU(adoc("id", "6",  "bday", "NOW+2YEARS"));
    assertU(commit());

    assertQ("check math on absolute date#1",
            req("q", "bday:[* TO "+july4+"/SECOND]"),
            "*[count(//doc)=0]");
    assertQ("check math on absolute date#2",
            req("q", "bday:[* TO "+july4+"/SECOND+1SECOND]"),
            "*[count(//doc)=1]");
    assertQ("check math on absolute date#3",
            req("q", "bday:["+july4+"/SECOND TO "+july4+"/SECOND+1SECOND]"),
            "*[count(//doc)=1]");
    assertQ("check math on absolute date#4",
            req("q", "bday:["+july4+"/MINUTE+1MINUTE TO *]"),
            "*[count(//doc)=5]");
    
    assertQ("check count for before now",
            req("q", "bday:[* TO NOW]"), "*[count(//doc)=4]");

    assertQ("check count for after now",
            req("q", "bday:[NOW TO *]"), "*[count(//doc)=2]");

    assertQ("check count for old stuff",
            req("q", "bday:[* TO NOW-2YEARS]"), "*[count(//doc)=1]");

    assertQ("check count for future stuff",
            req("q", "bday:[NOW+1MONTH TO *]"), "*[count(//doc)=1]");

    assertQ("check count for near stuff",
            req("q", "bday:[NOW-1MONTH TO NOW+2HOURS]"), "*[count(//doc)=4]");
    
  }
  
  public void testPatternReplaceFilter() {

    assertU(adoc("id", "1",
                 "patternreplacefilt", "My  fine-feathered friend!"));
    assertU(adoc("id", "2",
                 "patternreplacefilt", "  What's Up Doc?"));
    assertU(commit());
 
    assertQ("don't find Up",
            req("q", "patternreplacefilt:Up"),
            "*[count(//doc)=0]");
    
    assertQ("find doc",
            req("q", "patternreplacefilt:__What_s_Up_Doc_"),
            "*[count(//doc)=1]");

    assertQ("find birds",
            req("q", "patternreplacefilt:My__fine_feathered_friend_"),
            "*[count(//doc)=1]");
  }

//   /** this doesn't work, but if it did, this is how we'd test it. */
//   public void testOverwriteFalse() {

//     assertU(adoc("id", "overwrite", "val_s", "AAA"));
//     assertU(commit());

//     assertU(add(doc("id", "overwrite", "val_s", "BBB")
//                 ,"allowDups", "false"
//                 ,"overwriteCommitted","false"
//                 ,"overwritePending","false"
//                 ));
//     assertU(commit());
//     assertQ(req("id:overwrite")
//             ,"//*[@numFound='1']"
//             ,"//str[.='AAA']"
//             );
//   }


}
