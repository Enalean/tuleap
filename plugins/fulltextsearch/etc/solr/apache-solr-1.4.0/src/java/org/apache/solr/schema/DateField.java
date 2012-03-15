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

package org.apache.solr.schema;

import org.apache.lucene.document.Fieldable;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.SortField;
import org.apache.lucene.search.TermRangeQuery;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.util.DateUtil;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.TextResponseWriter;
import org.apache.solr.request.XMLWriter;
import org.apache.solr.search.QParser;
import org.apache.solr.search.function.*;
import org.apache.solr.util.DateMathParser;

import java.io.IOException;
import java.text.*;
import java.util.*;

// TODO: make a FlexibleDateField that can accept dates in multiple
// formats, better for human entered dates.

// TODO: make a DayField that only stores the day?


/**
 * FieldType that can represent any Date/Time with millisecond precision.
 * <p>
 * Date Format for the XML, incoming and outgoing:
 * </p>
 * <blockquote>
 * A date field shall be of the form 1995-12-31T23:59:59Z
 * The trailing "Z" designates UTC time and is mandatory
 * (See below for an explanation of UTC).
 * Optional fractional seconds are allowed, as long as they do not end
 * in a trailing 0 (but any precision beyond milliseconds will be ignored).
 * All other parts are mandatory.
 * </blockquote>
 * <p>
 * This format was derived to be standards compliant (ISO 8601) and is a more
 * restricted form of the
 * <a href="http://www.w3.org/TR/xmlschema-2/#dateTime-canonical-representation">canonical
 * representation of dateTime</a> from XML schema part 2.  Examples...
 * </p>
 * <ul>
 *   <li>1995-12-31T23:59:59Z</li>
 *   <li>1995-12-31T23:59:59.9Z</li>
 *   <li>1995-12-31T23:59:59.99Z</li>
 *   <li>1995-12-31T23:59:59.999Z</li>
 * </ul>
 * <p>
 * Note that DateField is lenient with regards to parsing fractional
 * seconds that end in trailing zeros and will ensure that those values
 * are indexed in the correct canonical format.
 * </p>
 * <p>
 * This FieldType also supports incoming "Date Math" strings for computing
 * values by adding/rounding internals of time relative either an explicit
 * datetime (in the format specified above) or the literal string "NOW",
 * ie: "NOW+1YEAR", "NOW/DAY", "1995-12-31T23:59:59.999Z+5MINUTES", etc...
 * -- see {@link DateMathParser} for more examples.
 * </p>
 *
 * <p>
 * Explanation of "UTC"...
 * </p>
 * <blockquote>
 * "In 1970 the Coordinated Universal Time system was devised by an
 * international advisory group of technical experts within the International
 * Telecommunication Union (ITU).  The ITU felt it was best to designate a
 * single abbreviation for use in all languages in order to minimize
 * confusion.  Since unanimous agreement could not be achieved on using
 * either the English word order, CUT, or the French word order, TUC, the
 * acronym UTC was chosen as a compromise."
 * </blockquote>
 *
 * @version $Id: DateField.java 816202 2009-09-17 14:08:13Z yonik $
 * @see <a href="http://www.w3.org/TR/xmlschema-2/#dateTime">XML schema part 2</a>
 *
 */
public class DateField extends FieldType {

  public static TimeZone UTC = TimeZone.getTimeZone("UTC");

  /* :TODO: let Locale/TimeZone come from init args for rounding only */

  /** TimeZone for DateMath (UTC) */
  protected static final TimeZone MATH_TZ = UTC;
  /** Locale for DateMath (Locale.US) */
  protected static final Locale MATH_LOCALE = Locale.US;

  /** 
   * Fixed TimeZone (UTC) needed for parsing/formating Dates in the 
   * canonical representation.
   */
  protected static final TimeZone CANONICAL_TZ = UTC;
  /** 
   * Fixed Locale needed for parsing/formating Milliseconds in the 
   * canonical representation.
   */
  protected static final Locale CANONICAL_LOCALE = Locale.US;
  
  // The XML (external) date format will sort correctly, except if
  // fractions of seconds are present (because '.' is lower than 'Z').
  // The easiest fix is to simply remove the 'Z' for the internal
  // format.
  
  protected void init(IndexSchema schema, Map<String,String> args) {
  }

  protected static String NOW = "NOW";
  protected static char Z = 'Z';
  
  public String toInternal(String val) {
    return toInternal(parseMath(null, val));
  }

  /**
   * Parses a String which may be a date (in the standard format)
   * followed by an optional math expression.
   * @param now an optional fixed date to use as "NOW" in the DateMathParser
   * @param val the string to parse
   */
  public Date parseMath(Date now, String val) {
    String math = null;
    final DateMathParser p = new DateMathParser(MATH_TZ, MATH_LOCALE);
    
    if (null != now) p.setNow(now);
    
    if (val.startsWith(NOW)) {
      math = val.substring(NOW.length());
    } else {
      final int zz = val.indexOf(Z);
      if (0 < zz) {
        math = val.substring(zz+1);
        try {
          // p.setNow(toObject(val.substring(0,zz)));
          p.setNow(parseDate(val.substring(0,zz+1)));
        } catch (ParseException e) {
          throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,
                                   "Invalid Date in Date Math String:'"
                                   +val+'\'',e);
        }
      } else {
        throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,
                                 "Invalid Date String:'" +val+'\'');
      }
    }

    if (null == math || math.equals("")) {
      return p.getNow();
    }
    
    try {
      return p.parseMath(math);
    } catch (ParseException e) {
      throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,
                               "Invalid Date Math String:'" +val+'\'',e);
    }
  }
  
  public String toInternal(Date val) {
    return formatDate(val);
  }

  public String indexedToReadable(String indexedForm) {
    return indexedForm + Z;
  }

  public String toExternal(Fieldable f) {
    return indexedToReadable(f.stringValue());
  }

  public Date toObject(String indexedForm) throws java.text.ParseException {
    return parseDate(indexedToReadable(indexedForm));
  }

  @Override
  public Date toObject(Fieldable f) {
    try {
      return parseDate( toExternal(f) );
    }
    catch( ParseException ex ) {
      throw new RuntimeException( ex );
    }
  }

  public SortField getSortField(SchemaField field,boolean reverse) {
    return getStringSort(field,reverse);
  }

  public ValueSource getValueSource(SchemaField field) {
    return new OrdFieldSource(field.name);
  }

  public void write(XMLWriter xmlWriter, String name, Fieldable f) throws IOException {
    xmlWriter.writeDate(name, toExternal(f));
  }

  public void write(TextResponseWriter writer, String name, Fieldable f) throws IOException {
    writer.writeDate(name, toExternal(f));
  }

  /**
   * Returns a formatter that can be use by the current thread if needed to
   * convert Date objects to the Internal representation.
   *
   * Only the <tt>format(Date)</tt> can be used safely.
   * 
   * @deprecated - use formatDate(Date) instead
   */
  protected DateFormat getThreadLocalDateFormat() {
    return fmtThreadLocal.get();
  }

  /**
   * Thread safe method that can be used by subclasses to format a Date
   * using the Internal representation.
   */
  protected String formatDate(Date d) {
    return fmtThreadLocal.get().format(d);
  }

  /**
   * Return the standard human readable form of the date
   */
  public String toExternal(Date d) {
    return fmtThreadLocal.get().format(d) + 'Z';  
  }

  /**
   * Thread safe method that can be used by subclasses to parse a Date
   * that is already in the internal representation
   */
   protected Date parseDate(String s) throws ParseException {
     return fmtThreadLocal.get().parse(s);
   }

  /** Parse a date string in the standard format, or any supported by DateUtil.parseDate */
   public Date parseDateLenient(String s, SolrQueryRequest req) throws ParseException {
     // request could define timezone in the future
     try {
       return fmtThreadLocal.get().parse(s);
     } catch (Exception e) {
       return DateUtil.parseDate(s);
     }
   }

  /**
   * Parses a String which may be a date
   * followed by an optional math expression.
   * @param now an optional fixed date to use as "NOW" in the DateMathParser
   * @param val the string to parse
   */
  public Date parseMathLenient(Date now, String val, SolrQueryRequest req) {
    String math = null;
    final DateMathParser p = new DateMathParser(MATH_TZ, MATH_LOCALE);

    if (null != now) p.setNow(now);

    if (val.startsWith(NOW)) {
      math = val.substring(NOW.length());
    } else {
      final int zz = val.indexOf(Z);
      if (0 < zz) {
        math = val.substring(zz+1);
        try {
          // p.setNow(toObject(val.substring(0,zz)));
          p.setNow(parseDateLenient(val.substring(0,zz+1), req));
        } catch (ParseException e) {
          throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,
                                   "Invalid Date in Date Math String:'"
                                   +val+'\'',e);
        }
      } else {
        throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,
                                 "Invalid Date String:'" +val+'\'');
      }
    }

    if (null == math || math.equals("")) {
      return p.getNow();
    }

    try {
      return p.parseMath(math);
    } catch (ParseException e) {
      throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,
                               "Invalid Date Math String:'" +val+'\'',e);
    }
  }


  
  /**
   * Thread safe DateFormat that can <b>format</b> in the canonical
   * ISO8601 date format, not including the trailing "Z" (since it is
   * left off in the internal indexed values)
   */
  private final static ThreadLocalDateFormat fmtThreadLocal
    = new ThreadLocalDateFormat(new ISO8601CanonicalDateFormat());
  
  private static class ISO8601CanonicalDateFormat extends SimpleDateFormat {
    
    protected NumberFormat millisParser
      = NumberFormat.getIntegerInstance(CANONICAL_LOCALE);

    protected NumberFormat millisFormat = new DecimalFormat(".###", 
      new DecimalFormatSymbols(CANONICAL_LOCALE));

    public ISO8601CanonicalDateFormat() {
      super("yyyy-MM-dd'T'HH:mm:ss", CANONICAL_LOCALE);
      this.setTimeZone(CANONICAL_TZ);
    }

    public Date parse(String i, ParsePosition p) {
      /* delegate to SimpleDateFormat for easy stuff */
      Date d = super.parse(i, p);
      int milliIndex = p.getIndex();
      /* worry aboutthe milliseconds ourselves */
      if (null != d &&
          -1 == p.getErrorIndex() &&
          milliIndex + 1 < i.length() &&
          '.' == i.charAt(milliIndex)) {
        p.setIndex( ++milliIndex ); // NOTE: ++ to chomp '.'
        Number millis = millisParser.parse(i, p);
        if (-1 == p.getErrorIndex()) {
          int endIndex = p.getIndex();
            d = new Date(d.getTime()
                         + (long)(millis.doubleValue() *
                                  Math.pow(10, (3-endIndex+milliIndex))));
        }
      }
      return d;
    }

    public StringBuffer format(Date d, StringBuffer toAppendTo,
                               FieldPosition pos) {
      /* delegate to SimpleDateFormat for easy stuff */
      super.format(d, toAppendTo, pos);
      /* worry aboutthe milliseconds ourselves */
      long millis = d.getTime() % 1000l;
      if (0l == millis) {
        return toAppendTo;
      }
      int posBegin = toAppendTo.length();
      toAppendTo.append(millisFormat.format(millis / 1000d));
      if (DateFormat.MILLISECOND_FIELD == pos.getField()) {
        pos.setBeginIndex(posBegin);
        pos.setEndIndex(toAppendTo.length());
      }
      return toAppendTo;
    }

    public Object clone() {
      ISO8601CanonicalDateFormat c
        = (ISO8601CanonicalDateFormat) super.clone();
      c.millisParser = NumberFormat.getIntegerInstance(CANONICAL_LOCALE);
      c.millisFormat = new DecimalFormat(".###", 
        new DecimalFormatSymbols(CANONICAL_LOCALE));
      return c;
    }
  }
  
  private static class ThreadLocalDateFormat extends ThreadLocal<DateFormat> {
    DateFormat proto;
    public ThreadLocalDateFormat(DateFormat d) {
      super();
      proto = d;
    }
    protected DateFormat initialValue() {
      return (DateFormat) proto.clone();
    }
  }

  @Override
  public ValueSource getValueSource(SchemaField field, QParser parser) {
    return new DateFieldSource(field.getName(), field.getType());
  }

  /** DateField specific range query */
  public Query getRangeQuery(QParser parser, SchemaField sf, Date part1, Date part2, boolean minInclusive, boolean maxInclusive) {
    return new TermRangeQuery(
            sf.getName(),
            part1 == null ? null : toInternal(part1),
            part2 == null ? null : toInternal(part2),
            minInclusive, maxInclusive);
  }

}



class DateFieldSource extends FieldCacheSource {
  // NOTE: this is bad for serialization... but we currently need the fieldType for toInternal()
  FieldType ft;

  public DateFieldSource(String name, FieldType ft) {
    super(name);
    this.ft = ft;
  }

  public String description() {
    return "date(" + field + ')';
  }

  public DocValues getValues(Map context, IndexReader reader) throws IOException {
    return new StringIndexDocValues(this, reader, field) {
      protected String toTerm(String readableValue) {
        // needed for frange queries to work properly
        return ft.toInternal(readableValue);
      }

      public float floatVal(int doc) {
        return (float)intVal(doc);
      }

      public int intVal(int doc) {
        int ord=order[doc];
        return ord;
      }

      public long longVal(int doc) {
        return (long)intVal(doc);
      }

      public double doubleVal(int doc) {
        return (double)intVal(doc);
      }

      public String strVal(int doc) {
        int ord=order[doc];
        return ft.indexedToReadable(lookup[ord]);
      }

      public String toString(int doc) {
        return description() + '=' + intVal(doc);
      }
    };
  }

  public boolean equals(Object o) {
    return o instanceof DateFieldSource
            && super.equals(o);
  }

  private static int hcode = DateFieldSource.class.hashCode();
  public int hashCode() {
    return hcode + super.hashCode();
  };
}