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
package org.apache.solr.handler.dataimport;

import static org.apache.solr.handler.dataimport.RegexTransformer.REGEX;
import static org.apache.solr.handler.dataimport.RegexTransformer.GROUP_NAMES;
import static org.apache.solr.handler.dataimport.RegexTransformer.REPLACE_WITH;
import static org.apache.solr.handler.dataimport.DataImporter.COLUMN;
import static org.apache.solr.handler.dataimport.AbstractDataImportHandlerTest.createMap;
import static org.apache.solr.handler.dataimport.AbstractDataImportHandlerTest.getContext;
import org.junit.Assert;
import org.junit.Test;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * <p> Test for RegexTransformer </p>
 *
 * @version $Id: TestRegexTransformer.java 823798 2009-10-10 06:13:55Z noble $
 * @since solr 1.3
 */
public class TestRegexTransformer {

  @Test
  public void commaSeparated() {
    List<Map<String, String>> fields = new ArrayList<Map<String, String>>();
    // <field column="col1" sourceColName="a" splitBy="," />
    fields.add(getField("col1", "string", null, "a", ","));
    Context context = AbstractDataImportHandlerTest.getContext(null, null, null, Context.FULL_DUMP, fields, null);

    Map<String, Object> src = new HashMap<String, Object>();
    src.put("a", "a,bb,cc,d");

    Map<String, Object> result = new RegexTransformer().transformRow(src, context);
    Assert.assertEquals(2, result.size());
    Assert.assertEquals(4, ((List) result.get("col1")).size());
  }


  @Test
  public void groupNames() {
    List<Map<String, String>> fields = new ArrayList<Map<String, String>>();
    // <field column="col1" regex="(\w*)(\w*) (\w*)" groupNames=",firstName,lastName"/>
    Map<String ,String > m = new HashMap<String, String>();
    m.put(COLUMN,"fullName");
    m.put(GROUP_NAMES,",firstName,lastName");
    m.put(REGEX,"(\\w*) (\\w*) (\\w*)");
    fields.add(m);
    Context context = AbstractDataImportHandlerTest.getContext(null, null, null, Context.FULL_DUMP, fields, null);
    Map<String, Object> src = new HashMap<String, Object>();
    src.put("fullName", "Mr Noble Paul");

    Map<String, Object> result = new RegexTransformer().transformRow(src, context);
    Assert.assertEquals("Noble", result.get("firstName"));
    Assert.assertEquals("Paul", result.get("lastName"));
    src= new HashMap<String, Object>();
    List<String> l= new ArrayList();
    l.add("Mr Noble Paul") ;
    l.add("Mr Shalin Mangar") ;
    src.put("fullName", l);
    result = new RegexTransformer().transformRow(src, context);
    List l1 = (List) result.get("firstName");
    List l2 = (List) result.get("lastName");
    Assert.assertEquals("Noble", l1.get(0));
    Assert.assertEquals("Shalin", l1.get(1));
    Assert.assertEquals("Paul", l2.get(0));
    Assert.assertEquals("Mangar", l2.get(1));
  }

  @Test
  public void replaceWith() {
    List<Map<String, String>> fields = new ArrayList<Map<String, String>>();
    // <field column="name" regexp="'" replaceWith="''" />
    Map<String, String> fld = getField("name", "string", "'", null, null);
    fld.put(REPLACE_WITH, "''");
    fields.add(fld);
    Context context = AbstractDataImportHandlerTest.getContext(null, null,
            null, Context.FULL_DUMP, fields, null);

    Map<String, Object> src = new HashMap<String, Object>();
    String s = "D'souza";
    src.put("name", s);

    Map<String, Object> result = new RegexTransformer().transformRow(src,
            context);
    Assert.assertEquals("D''souza", result.get("name"));
  }

  @Test
  public void mileage() {
    // init a whole pile of fields
    List<Map<String, String>> fields = getFields();

    // add another regex which reuses result from previous regex again!
    // <field column="hltCityMPG" sourceColName="rowdata" regexp="(${e.city_mileage})" />
    Map<String, String> fld = getField("hltCityMPG", "string",
            ".*(${e.city_mileage})", "rowdata", null);
    fld.put(REPLACE_WITH, "*** $1 ***");
    fields.add(fld);

    //  **ATTEMPTS** a match WITHOUT a replaceWith
    // <field column="t1" sourceColName="rowdata" regexp="duff" />
    fld = getField("t1", "string","duff", "rowdata", null);
    fields.add(fld);

    //  **ATTEMPTS** a match WITH a replaceWith
    // <field column="t2" sourceColName="rowdata" regexp="duff" replaceWith="60"/>
    fld = getField("t2", "string","duff", "rowdata", null);
    fld.put(REPLACE_WITH, "60");
    fields.add(fld);

    //  regex WITH both replaceWith and groupName (groupName ignored!)
    // <field column="t3" sourceColName="rowdata" regexp="(Range)" />
    fld = getField("t3", "string","(Range)", "rowdata", null);
    fld.put(REPLACE_WITH, "range");
    fld.put(GROUP_NAMES,"t4,t5");
    fields.add(fld);

    Map<String, Object> row = new HashMap<String, Object>();
    String s = "Fuel Economy Range: 26 mpg Hwy, 19 mpg City";
    row.put("rowdata", s);

    VariableResolverImpl resolver = new VariableResolverImpl();
    resolver.addNamespace("e", row);
    Map<String, String> eAttrs = AbstractDataImportHandlerTest.createMap("name", "e");
    Context context = AbstractDataImportHandlerTest.getContext(null, resolver, null, Context.FULL_DUMP, fields, eAttrs);

    Map<String, Object> result = new RegexTransformer().transformRow(row, context);
    Assert.assertEquals(5, result.size());
    Assert.assertEquals(s, result.get("rowdata"));
    Assert.assertEquals("26", result.get("highway_mileage"));
    Assert.assertEquals("19", result.get("city_mileage"));
    Assert.assertEquals("*** 19 *** mpg City", result.get("hltCityMPG"));
    Assert.assertEquals("Fuel Economy range: 26 mpg Hwy, 19 mpg City", result.get("t3"));
  }

  @Test
  public void testMultiValuedRegex(){
      List<Map<String, String>> fields = new ArrayList<Map<String, String>>();
//    <field column="participant" sourceColName="person" regex="(.*)" />
    Map<String, String> fld = getField("participant", null, "(.*)", "person", null);
    fields.add(fld);
    Context context = getContext(null, null,
            null, Context.FULL_DUMP, fields, null);

    ArrayList<String> strings = new ArrayList<String>();
    strings.add("hello");
    strings.add("world");
    Map<String, Object> result = new RegexTransformer().transformRow(createMap("person", strings), context);
    Assert.assertEquals(strings,result.get("participant"));


  }

  public static List<Map<String, String>> getFields() {
    List<Map<String, String>> fields = new ArrayList<Map<String, String>>();

    // <field column="city_mileage" sourceColName="rowdata" regexp=
    //    "Fuel Economy Range:\\s*?\\d*?\\s*?mpg Hwy,\\s*?(\\d*?)\\s*?mpg City"
    fields.add(getField("city_mileage", "sint",
            "Fuel Economy Range:\\s*?\\d*?\\s*?mpg Hwy,\\s*?(\\d*?)\\s*?mpg City",
            "rowdata", null));

    // <field column="highway_mileage" sourceColName="rowdata" regexp=
    //    "Fuel Economy Range:\\s*?(\\d*?)\\s*?mpg Hwy,\\s*?\\d*?\\s*?mpg City"
    fields.add(getField("highway_mileage", "sint",
            "Fuel Economy Range:\\s*?(\\d*?)\\s*?mpg Hwy,\\s*?\\d*?\\s*?mpg City",
            "rowdata", null));

    // <field column="seating_capacity" sourceColName="rowdata" regexp="Seating capacity:(.*)"
    fields.add(getField("seating_capacity", "sint", "Seating capacity:(.*)",
            "rowdata", null));

    // <field column="warranty" sourceColName="rowdata" regexp="Warranty:(.*)" />
    fields.add(getField("warranty", "string", "Warranty:(.*)", "rowdata", null));

    // <field column="rowdata" sourceColName="rowdata" />
    fields.add(getField("rowdata", "string", null, "rowdata", null));
    return fields;
  }

  public static Map<String, String> getField(String col, String type,
                                             String re, String srcCol, String splitBy) {
    HashMap<String, String> vals = new HashMap<String, String>();
    vals.put("column", col);
    vals.put("type", type);
    vals.put("regex", re);
    vals.put("sourceColName", srcCol);
    vals.put("splitBy", splitBy);
    return vals;
  }
}
