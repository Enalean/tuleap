package org.apache.solr.search;
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

import org.apache.solr.common.SolrException;
import org.apache.solr.common.util.ConcurrentLRUCache;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.core.SolrCore;

import java.io.IOException;
import java.io.Serializable;
import java.net.URL;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.CopyOnWriteArrayList;

/**
 * SolrCache based on ConcurrentLRUCache implementation.
 * <p/>
 * This implementation does not use a separate cleanup thread. Instead it uses the calling thread
 * itself to do the cleanup when the size of the cache exceeds certain limits.
 * <p/>
 * Also see <a href="http://wiki.apache.org/solr/SolrCaching">SolrCaching</a>
 *
 * @version $Id: FastLRUCache.java 776449 2009-05-19 20:33:20Z gsingers $
 * @see org.apache.solr.common.util.ConcurrentLRUCache
 * @see org.apache.solr.search.SolrCache
 * @since solr 1.4
 */
public class FastLRUCache implements SolrCache {

  private List<ConcurrentLRUCache.Stats> cumulativeStats;

  private long warmupTime = 0;

  private String name;
  private int autowarmCount;
  private State state;
  private CacheRegenerator regenerator;
  private String description = "Concurrent LRU Cache";
  private ConcurrentLRUCache cache;
  private int showItems = 0;

  public Object init(Map args, Object persistence, CacheRegenerator regenerator) {
    state = State.CREATED;
    this.regenerator = regenerator;
    name = (String) args.get("name");
    String str = (String) args.get("size");
    int limit = str == null ? 1024 : Integer.parseInt(str);
    int minLimit;
    str = (String) args.get("minSize");
    if (str == null) {
      minLimit = (int) (limit * 0.9);
    } else {
      minLimit = Integer.parseInt(str);
    }
    if (minLimit==0) minLimit=1;
    if (limit <= minLimit) limit=minLimit+1;

    int acceptableLimit;
    str = (String) args.get("acceptableSize");
    if (str == null) {
      acceptableLimit = (int) (limit * 0.95);
    } else {
      acceptableLimit = Integer.parseInt(str);
    }
    // acceptable limit should be somewhere between minLimit and limit
    acceptableLimit = Math.max(minLimit, acceptableLimit);

    str = (String) args.get("initialSize");
    final int initialSize = str == null ? limit : Integer.parseInt(str);
    str = (String) args.get("autowarmCount");
    autowarmCount = str == null ? 0 : Integer.parseInt(str);
    str = (String) args.get("cleanupThread");
    boolean newThread = str == null ? false : Boolean.parseBoolean(str);

    str = (String) args.get("showItems");
    showItems = str == null ? 0 : Integer.parseInt(str);


    description = "Concurrent LRU Cache(maxSize=" + limit + ", initialSize=" + initialSize +
            ", minSize="+minLimit + ", acceptableSize="+acceptableLimit+", cleanupThread="+newThread;
    if (autowarmCount > 0) {
      description += ", autowarmCount=" + autowarmCount + ", regenerator=" + regenerator;
    }
    description += ')';

    cache = new ConcurrentLRUCache(limit, minLimit, acceptableLimit, initialSize, newThread, false, null);
    cache.setAlive(false);

    if (persistence == null) {
      // must be the first time a cache of this type is being created
      // Use a CopyOnWriteArrayList since puts are very rare and iteration may be a frequent operation
      // because it is used in getStatistics()
      persistence = new CopyOnWriteArrayList<ConcurrentLRUCache.Stats>();
    }

    cumulativeStats = (List<ConcurrentLRUCache.Stats>) persistence;
    cumulativeStats.add(cache.getStats());
    return cumulativeStats;
  }

  public String name() {
    return name;
  }

  public int size() {
    return cache.size();

  }

  public Object put(Object key, Object value) {
    return cache.put(key, value);
  }

  public Object get(Object key) {
    return cache.get(key);

  }

  public void clear() {
    cache.clear();
  }

  public void setState(State state) {
    this.state = state;
    cache.setAlive(state == State.LIVE);
  }

  public State getState() {
    return state;
  }

  public void warm(SolrIndexSearcher searcher, SolrCache old) throws IOException {
    if (regenerator == null) return;
    long warmingStartTime = System.currentTimeMillis();
    FastLRUCache other = (FastLRUCache) old;
    // warm entries
    if (autowarmCount != 0) {
      int sz = other.size();
      if (autowarmCount != -1) sz = Math.min(sz, autowarmCount);
      Map items = other.cache.getLatestAccessedItems(sz);
      Map.Entry[] itemsArr = new Map.Entry[items.size()];
      int counter = 0;
      for (Object mapEntry : items.entrySet()) {
        itemsArr[counter++] = (Map.Entry) mapEntry;
      }
      for (int i = itemsArr.length - 1; i >= 0; i--) {
        try {
          boolean continueRegen = regenerator.regenerateItem(searcher,
                  this, old, itemsArr[i].getKey(), itemsArr[i].getValue());
          if (!continueRegen) break;
        }
        catch (Throwable e) {
          SolrException.log(log, "Error during auto-warming of key:" + itemsArr[i].getKey(), e);
        }
      }
    }
    warmupTime = System.currentTimeMillis() - warmingStartTime;
  }


  public void close() {
    cache.destroy();
  }

  //////////////////////// SolrInfoMBeans methods //////////////////////
  public String getName() {
    return FastLRUCache.class.getName();
  }

  public String getVersion() {
    return SolrCore.version;
  }

  public String getDescription() {
    return description;
  }

  public Category getCategory() {
    return Category.CACHE;
  }

  public String getSourceId() {
    return "$Id: FastLRUCache.java 776449 2009-05-19 20:33:20Z gsingers $";
  }

  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/search/FastLRUCache.java $";
  }

  public URL[] getDocs() {
    return null;
  }

  // returns a ratio, not a percent.
  private static String calcHitRatio(long lookups, long hits) {
    if (lookups == 0) return "0.00";
    if (lookups == hits) return "1.00";
    int hundredths = (int) (hits * 100 / lookups);   // rounded down
    if (hundredths < 10) return "0.0" + hundredths;
    return "0." + hundredths;
  }

  public NamedList getStatistics() {
    NamedList<Serializable> lst = new SimpleOrderedMap<Serializable>();
    if (cache == null)  return lst;
    ConcurrentLRUCache.Stats stats = cache.getStats();
    long lookups = stats.getCumulativeLookups();
    long hits = stats.getCumulativeHits();
    long inserts = stats.getCumulativePuts();
    long evictions = stats.getCumulativeEvictions();
    long size = stats.getCurrentSize();

    lst.add("lookups", lookups);
    lst.add("hits", hits);
    lst.add("hitratio", calcHitRatio(lookups, hits));
    lst.add("inserts", inserts);
    lst.add("evictions", evictions);
    lst.add("size", size);

    lst.add("warmupTime", warmupTime);

    long clookups = 0;
    long chits = 0;
    long cinserts = 0;
    long cevictions = 0;

    // NOTE: It is safe to iterate on a CopyOnWriteArrayList
    for (ConcurrentLRUCache.Stats statistiscs : cumulativeStats) {
      clookups += statistiscs.getCumulativeLookups();
      chits += statistiscs.getCumulativeHits();
      cinserts += statistiscs.getCumulativePuts();
      cevictions += statistiscs.getCumulativeEvictions();
    }
    lst.add("cumulative_lookups", clookups);
    lst.add("cumulative_hits", chits);
    lst.add("cumulative_hitratio", calcHitRatio(clookups, chits));
    lst.add("cumulative_inserts", cinserts);
    lst.add("cumulative_evictions", cevictions);

    if (showItems != 0) {
      Map items = cache.getLatestAccessedItems( showItems == -1 ? Integer.MAX_VALUE : showItems );
      for (Map.Entry e : (Set <Map.Entry>)items.entrySet()) {
        Object k = e.getKey();
        Object v = e.getValue();

        String ks = "item_" + k;
        String vs = v.toString();
        lst.add(ks,vs);
      }
      
    }

    return lst;
  }

  public String toString() {
    return name + getStatistics().toString();
  }
}



