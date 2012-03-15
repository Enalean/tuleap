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

package org.apache.solr.search;

import org.apache.lucene.search.Query;
import org.apache.lucene.search.BooleanQuery;
import org.apache.lucene.search.BooleanClause;
import org.apache.lucene.search.MatchAllDocsQuery;

import java.util.List;

/**
 * @version $Id: QueryUtils.java 565144 2007-08-12 20:47:42Z ryan $
 */
public class QueryUtils {

  /** return true if this query has no positive components */
  static boolean isNegative(Query q) {
    if (!(q instanceof BooleanQuery)) return false;
    BooleanQuery bq = (BooleanQuery)q;
    List<BooleanClause> clauses = bq.clauses();
    if (clauses.size()==0) return false;
    for (BooleanClause clause : clauses) {
      if (!clause.isProhibited()) return false;
    }
    return true;
  }

  /** Returns the original query if it was already a positive query, otherwise
   * return the negative of the query (i.e., a positive query).
   * <p>
   * Example: both id:10 and id:-10 will return id:10
   * <p>
   * The caller can tell the sign of the original by a reference comparison between
   * the original and returned query.
   * @param q
   * @return
   */
  static Query getAbs(Query q) {
    if (!(q instanceof BooleanQuery)) return q;
    BooleanQuery bq = (BooleanQuery)q;

    List<BooleanClause> clauses = bq.clauses();
    if (clauses.size()==0) return q;


    for (BooleanClause clause : clauses) {
      if (!clause.isProhibited()) return q;
    }

    if (clauses.size()==1) {
      // if only one clause, dispense with the wrapping BooleanQuery
      Query negClause = clauses.get(0).getQuery();
      // we shouldn't need to worry about adjusting the boosts since the negative
      // clause would have never been selected in a positive query, and hence would
      // not contribute to a score.
      return negClause;
    } else {
      BooleanQuery newBq = new BooleanQuery(bq.isCoordDisabled());
      newBq.setBoost(bq.getBoost());
      // ignore minNrShouldMatch... it doesn't make sense for a negative query

      // the inverse of -a -b is a OR b
      for (BooleanClause clause : clauses) {
        newBq.add(clause.getQuery(), BooleanClause.Occur.SHOULD);
      }
      return newBq;
    }
  }

  /** Makes negative queries suitable for querying by
   * lucene.
   */
  static Query makeQueryable(Query q) {
    return isNegative(q) ? fixNegativeQuery(q) : q;
  }

  /** Fixes a negative query by adding a MatchAllDocs query clause.
   * The query passed in *must* be a negative query.
   */
  static Query fixNegativeQuery(Query q) {
    BooleanQuery newBq = (BooleanQuery)q.clone();
    newBq.add(new MatchAllDocsQuery(), BooleanClause.Occur.MUST);
    return newBq;    
  }

}
