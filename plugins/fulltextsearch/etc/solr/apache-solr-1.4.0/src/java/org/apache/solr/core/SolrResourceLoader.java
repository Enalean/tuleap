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

package org.apache.solr.core;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileFilter;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLClassLoader;
import java.util.*;
import java.util.concurrent.ConcurrentHashMap;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import java.nio.charset.Charset;
import java.lang.reflect.Constructor;

import javax.naming.Context;
import javax.naming.InitialContext;
import javax.naming.NamingException;
import javax.naming.NoInitialContextException;

import org.apache.solr.analysis.CharFilterFactory;
import org.apache.solr.analysis.TokenFilterFactory;
import org.apache.solr.analysis.TokenizerFactory;
import org.apache.solr.common.util.FileUtils;
import org.apache.solr.common.ResourceLoader;
import org.apache.solr.common.SolrException;
import org.apache.solr.handler.component.SearchComponent;
import org.apache.solr.request.QueryResponseWriter;
import org.apache.solr.request.SolrRequestHandler;
import org.apache.solr.schema.FieldType;
import org.apache.solr.update.processor.UpdateRequestProcessorFactory;
import org.apache.solr.util.plugin.ResourceLoaderAware;
import org.apache.solr.util.plugin.SolrCoreAware;

/**
 * @since solr 1.3
 */ 
public class SolrResourceLoader implements ResourceLoader
{
  public static final Logger log = LoggerFactory.getLogger(SolrResourceLoader.class);

  static final String project = "solr";
  static final String base = "org.apache" + "." + project;
  static final String[] packages = {"","analysis.","schema.","handler.","search.","update.","core.","request.","update.processor.","util.", "spelling.", "handler.component.", "handler.dataimport"};

  private URLClassLoader classLoader;
  private final String instanceDir;
  private String dataDir;
  
  private final List<SolrCoreAware> waitingForCore = new ArrayList<SolrCoreAware>();
  private final List<SolrInfoMBean> infoMBeans = new ArrayList<SolrInfoMBean>();
  private final List<ResourceLoaderAware> waitingForResources = new ArrayList<ResourceLoaderAware>();
  private static final Charset UTF_8 = Charset.forName("UTF-8");

  private final Properties coreProperties;

  /**
   * <p>
   * This loader will delegate to the context classloader when possible,
   * otherwise it will attempt to resolve resources using any jar files
   * found in the "lib/" directory in the specified instance directory.
   * If the instance directory is not specified (=null), SolrResourceLoader#locateInstanceDir will provide one.
   * <p>
   */
  public SolrResourceLoader( String instanceDir, ClassLoader parent, Properties coreProperties )
  {
    if( instanceDir == null ) {
      this.instanceDir = SolrResourceLoader.locateSolrHome();
    } else{
      this.instanceDir = normalizeDir(instanceDir);
    }
    log.info("Solr home set to '" + this.instanceDir + "'");
    
    this.classLoader = createClassLoader(null, parent);
    addToClassLoader("./lib/", null);
    
    this.coreProperties = coreProperties;
  }

  /**
   * <p>
   * This loader will delegate to the context classloader when possible,
   * otherwise it will attempt to resolve resources using any jar files
   * found in the "lib/" directory in the specified instance directory.
   * If the instance directory is not specified (=null), SolrResourceLoader#locateInstanceDir will provide one.
   * <p>
   */
  public SolrResourceLoader( String instanceDir, ClassLoader parent )
  {
    this(instanceDir, parent, null);
  }

  /**
   * Adds every file/dir found in the baseDir which passes the specified Filter
   * to the ClassLoader used by this ResourceLoader.  This method <b>MUST</b>
   * only be called prior to using this ResourceLoader to get any resources, otherwise
   * it's behavior will be non-deterministic.
   *
   * @param baseDir base directory whose children (either jars or directories of
   *                classes) will be in the classpath, will be resolved relative
   *                the instance dir.
   * @param filter The filter files must satisfy, if null all files will be accepted.
   */
  void addToClassLoader(final String baseDir, final FileFilter filter) {
    File base = FileUtils.resolvePath(new File(getInstanceDir()), baseDir);
    this.classLoader = replaceClassLoader(classLoader, base, filter);
  }
  
  /**
   * Adds the specific file/dir specified to the ClassLoader used by this
   * ResourceLoader.  This method <b>MUST</b>
   * only be called prior to using this ResourceLoader to get any resources, otherwise
   * it's behavior will be non-deterministic.
   *
   * @param path A jar file (or directory of classes) to be added to the classpath,
   *             will be resolved relative the instance dir.
   */
  void addToClassLoader(final String path) {
    final File file = FileUtils.resolvePath(new File(getInstanceDir()), path);
    if (file.canRead()) {
      this.classLoader = replaceClassLoader(classLoader, file.getParentFile(),
                                            new FileFilter() {
                                              public boolean accept(File pathname) {
                                                return pathname.equals(file);
                                              }
                                            });
    } else {
      log.error("Can't find (or read) file to add to classloader: " + file);
    }
  }
  
  private static URLClassLoader replaceClassLoader(final URLClassLoader oldLoader,
                                                   final File base,
                                                   final FileFilter filter) {
    if (null != base && base.canRead() && base.isDirectory()) {
      File[] files = base.listFiles(filter);
      
      if (null == files || 0 == files.length) return oldLoader;
      
      URL[] oldElements = oldLoader.getURLs();
      URL[] elements = new URL[oldElements.length + files.length];
      System.arraycopy(oldElements, 0, elements, 0, oldElements.length);
      
      for (int j = 0; j < files.length; j++) {
        try {
          URL element = files[j].toURI().normalize().toURL();
          log.info("Adding '" + element.toString() + "' to classloader");
          elements[oldElements.length + j] = element;
        } catch (MalformedURLException e) {
          SolrException.log(log, "Can't add element to classloader: " + files[j], e);
        }
      }
      return URLClassLoader.newInstance(elements, oldLoader.getParent());
    }
    // are we still here?
    return oldLoader;
  }
  
  /**
   * Convenience method for getting a new ClassLoader using all files found
   * in the specified lib directory.
   */
  static URLClassLoader createClassLoader(final File libDir, ClassLoader parent) {
    if ( null == parent ) {
      parent = Thread.currentThread().getContextClassLoader();
    }
    return replaceClassLoader(URLClassLoader.newInstance(new URL[0], parent),
                              libDir, null);
  }
  
  public SolrResourceLoader( String instanceDir )
  {
    this( instanceDir, null, null );
  }
  
  /** Ensures a directory name always ends with a '/'. */
  public  static String normalizeDir(String path) {
    return ( path != null && (!(path.endsWith("/") || path.endsWith("\\"))) )? path + File.separator : path;
  }

  public String getConfigDir() {
    return instanceDir + "conf/";
  }
  
  public String getDataDir()    {
    return dataDir;
  }

  public Properties getCoreProperties() {
    return coreProperties;
  }

  /** Opens a schema resource by its name.
   * Override this method to customize loading schema resources.
   *@return the stream for the named schema
   */
  public InputStream openSchema(String name) {
    return openResource(name);
  }
  
  /** Opens a config resource by its name.
   * Override this method to customize loading config resources.
   *@return the stream for the named configuration
   */
  public InputStream openConfig(String name) {
    return openResource(name);
  }
  
  /** Opens any resource by its name.
   * By default, this will look in multiple locations to load the resource:
   * $configDir/$resource (if resource is not absolute)
   * $CWD/$resource
   * otherwise, it will look for it in any jar accessible through the class loader.
   * Override this method to customize loading resources.
   *@return the stream for the named resource
   */
  public InputStream openResource(String resource) {
    InputStream is=null;
    try {
      File f0 = new File(resource);
      File f = f0;
      if (!f.isAbsolute()) {
        // try $CWD/$configDir/$resource
        f = new File(getConfigDir() + resource);
      }
      if (f.isFile() && f.canRead()) {
        return new FileInputStream(f);
      } else if (f != f0) { // no success with $CWD/$configDir/$resource
        if (f0.isFile() && f0.canRead())
          return new FileInputStream(f0);
      }
      // delegate to the class loader (looking into $INSTANCE_DIR/lib jars)
      is = classLoader.getResourceAsStream(resource);
    } catch (Exception e) {
      throw new RuntimeException("Error opening " + resource, e);
    }
    if (is==null) {
      throw new RuntimeException("Can't find resource '" + resource + "' in classpath or '" + getConfigDir() + "', cwd="+System.getProperty("user.dir"));
    }
    return is;
  }

  /**
   * Accesses a resource by name and returns the (non comment) lines
   * containing data.
   *
   * <p>
   * A comment line is any line that starts with the character "#"
   * </p>
   *
   * @param resource
   * @return a list of non-blank non-comment lines with whitespace trimmed
   * from front and back.
   * @throws IOException
   */
  public List<String> getLines(String resource) throws IOException {
    return getLines(resource, UTF_8);
  }

  /**
   * Accesses a resource by name and returns the (non comment) lines containing
   * data using the given character encoding.
   *
   * <p>
   * A comment line is any line that starts with the character "#"
   * </p>
   *
   * @param resource the file to be read
   * @param encoding
   * @return a list of non-blank non-comment lines with whitespace trimmed
   * @throws IOException
   */
  public List<String> getLines(String resource,
      String encoding) throws IOException {
    return getLines(resource, Charset.forName(encoding));
  }


  public List<String> getLines(String resource, Charset charset) throws IOException{
    BufferedReader input = null;
    ArrayList<String> lines;
    try {
      input = new BufferedReader(new InputStreamReader(openResource(resource),
          charset));

      lines = new ArrayList<String>();
      for (String word=null; (word=input.readLine())!=null;) {
        // skip comments
        if (word.startsWith("#")) continue;
        word=word.trim();
        // skip blank lines
        if (word.length()==0) continue;
        lines.add(word);
      }
    } finally {
      if (input != null)
        input.close();
    }
    return lines;
  }

  /*
   * A static map of short class name to fully qualified class name 
   */
  private static Map<String, String> classNameCache = new ConcurrentHashMap<String, String>();

  /**
   * This method loads a class either with it's FQN or a short-name (solr.class-simplename or class-simplename).
   * It tries to load the class with the name that is given first and if it fails, it tries all the known
   * solr packages. This method caches the FQN of a short-name in a static map in-order to make subsequent lookups
   * for the same class faster. The caching is done only if the class is loaded by the webapp classloader and it
   * is loaded using a shortname.
   *
   * @param cname The name or the short name of the class.
   * @param subpackages the packages to be tried if the cnams starts with solr.
   * @return the loaded class. An exception is thrown if it fails
   */
  public Class findClass(String cname, String... subpackages) {
    if (subpackages == null || subpackages.length == 0 || subpackages == packages) {
      subpackages = packages;
      String  c = classNameCache.get(cname);
      if(c != null) {
        try {
          return Class.forName(c, true, classLoader);
        } catch (ClassNotFoundException e) {
          //this is unlikely
          log.error("Unable to load cached class-name :  "+ c +" for shortname : "+cname + e);
        }

      }
    }
    Class clazz = null;
    // first try cname == full name
    try {
      return Class.forName(cname, true, classLoader);
    } catch (ClassNotFoundException e) {
      String newName=cname;
      if (newName.startsWith(project)) {
        newName = cname.substring(project.length()+1);
      }
      for (String subpackage : subpackages) {
        try {
          String name = base + '.' + subpackage + newName;
          log.trace("Trying class name " + name);
          return clazz = Class.forName(name,true,classLoader);
        } catch (ClassNotFoundException e1) {
          // ignore... assume first exception is best.
        }
      }
  
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, "Error loading class '" + cname + "'", e, false);
    }finally{
      //cache the shortname vs FQN if it is loaded by the webapp classloader  and it is loaded
      // using a shortname
      if ( clazz != null &&
              clazz.getClassLoader() == SolrResourceLoader.class.getClassLoader() &&
              !cname.equals(clazz.getName()) &&
              (subpackages.length == 0 || subpackages == packages)) {
        //store in the cache
        classNameCache.put(cname, clazz.getName());
      }
    }
  }

  public Object newInstance(String cname, String ... subpackages) {
    Class clazz = findClass(cname,subpackages);
    if( clazz == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Can not find class: "+cname + " in " + classLoader, false);
    }
    
    Object obj = null;
    try {
      obj = clazz.newInstance();
    } 
    catch (Exception e) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Error instantiating class: '" + clazz.getName()+"'", e, false );
    }
    
    if( obj instanceof SolrCoreAware ) {
      assertAwareCompatibility( SolrCoreAware.class, obj );
      waitingForCore.add( (SolrCoreAware)obj );
    }
    if( obj instanceof ResourceLoaderAware ) {
      assertAwareCompatibility( ResourceLoaderAware.class, obj );
      waitingForResources.add( (ResourceLoaderAware)obj );
    }
    if (obj instanceof SolrInfoMBean){
      //TODO: Assert here?
      infoMBeans.add((SolrInfoMBean) obj);
    }
    return obj;
  }

  public Object newAdminHandlerInstance(final CoreContainer coreContainer, String cname, String ... subpackages) {
    Class clazz = findClass(cname,subpackages);
    if( clazz == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Can not find class: "+cname + " in " + classLoader, false);
    }
    
    Object obj = null;
    try {
      Constructor ctor = clazz.getConstructor(CoreContainer.class);
       obj = ctor.newInstance(coreContainer);
    } 
    catch (Exception e) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Error instantiating class: '" + clazz.getName()+"'", e, false );
    }
    //TODO: Does SolrCoreAware make sense here since in a multi-core context
    // which core are we talking about ? 
    if( obj instanceof ResourceLoaderAware ) {
      assertAwareCompatibility( ResourceLoaderAware.class, obj );
      waitingForResources.add( (ResourceLoaderAware)obj );
    }
    return obj;
  }

 

  public Object newInstance(String cName, String [] subPackages, Class[] params, Object[] args){
    Class clazz = findClass(cName,subPackages);
    if( clazz == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Can not find class: "+cName + " in " + classLoader, false);
    }

    Object obj = null;
    try {

      Constructor constructor = clazz.getConstructor(params);
      obj = constructor.newInstance(args);
    }
    catch (Exception e) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Error instantiating class: '" + clazz.getName()+"'", e, false );
    }

    if( obj instanceof SolrCoreAware ) {
      assertAwareCompatibility( SolrCoreAware.class, obj );
      waitingForCore.add( (SolrCoreAware)obj );
    }
    if( obj instanceof ResourceLoaderAware ) {
      assertAwareCompatibility( ResourceLoaderAware.class, obj );
      waitingForResources.add( (ResourceLoaderAware)obj );
    }
    if (obj instanceof SolrInfoMBean){
      //TODO: Assert here?
      infoMBeans.add((SolrInfoMBean) obj);
    }
    return obj;
  }

  
  /**
   * Tell all {@link SolrCoreAware} instances about the SolrCore
   */
  public void inform(SolrCore core) 
  {
    this.dataDir = core.getDataDir();
    for( SolrCoreAware aware : waitingForCore ) {
      aware.inform( core );
    }
    waitingForCore.clear();
  }
  
  /**
   * Tell all {@link ResourceLoaderAware} instances about the loader
   */
  public void inform( ResourceLoader loader ) 
  {
    for( ResourceLoaderAware aware : waitingForResources ) {
      aware.inform( loader );
    }
    waitingForResources.clear();
  }

  /**
   * Register any {@link org.apache.solr.core.SolrInfoMBean}s
   * @param infoRegistry The Info Registry
   */
  public void inform(Map<String, SolrInfoMBean> infoRegistry) {
    for (SolrInfoMBean bean : infoMBeans) {
      infoRegistry.put(bean.getName(), bean);
    }
  }
  /**
   * Determines the solrhome from the environment.
   * Tries JNDI (java:comp/env/solr/home) then system property (solr.solr.home);
   * if both fail, defaults to solr/
   * @return the instance directory name
   */
  /**
   * Finds the solrhome based on looking up the value in one of three places:
   * <ol>
   *  <li>JNDI: via java:comp/env/solr/home</li>
   *  <li>The system property solr.solr.home</li>
   *  <li>Look in the current working directory for a solr/ directory</li> 
   * </ol>
   *
   * The return value is normalized.  Normalization essentially means it ends in a trailing slash.
   * @return A normalized solrhome
   * @see #normalizeDir(String)
   */
  public static String locateSolrHome() {
    String home = null;
    // Try JNDI
    try {
      Context c = new InitialContext();
      home = (String)c.lookup("java:comp/env/"+project+"/home");
      log.info("Using JNDI solr.home: "+home );
    } catch (NoInitialContextException e) {
      log.info("JNDI not configured for "+project+" (NoInitialContextEx)");
    } catch (NamingException e) {
      log.info("No /"+project+"/home in JNDI");
    } catch( RuntimeException ex ) {
      log.warn("Odd RuntimeException while testing for JNDI: " + ex.getMessage());
    } 
    
    // Now try system property
    if( home == null ) {
      String prop = project + ".solr.home";
      home = System.getProperty(prop);
      if( home != null ) {
        log.info("using system property "+prop+": " + home );
      }
    }
    
    // if all else fails, try 
    if( home == null ) {
      home = project + '/';
      log.info(project + " home defaulted to '" + home + "' (could not find system property or JNDI)");
    }
    return normalizeDir( home );
  }
  @Deprecated
  public static String locateInstanceDir() {
    return locateSolrHome();
  }

  public String getInstanceDir() {
    return instanceDir;
  }
  
  /**
   * Keep a list of classes that are allowed to implement each 'Aware' interface
   */
  private static final Map<Class, Class[]> awareCompatibility;
  static {
    awareCompatibility = new HashMap<Class, Class[]>();
    awareCompatibility.put( 
      SolrCoreAware.class, new Class[] {
        SolrRequestHandler.class,
        QueryResponseWriter.class,
        SearchComponent.class,
        UpdateRequestProcessorFactory.class
      }
    );

    awareCompatibility.put(
      ResourceLoaderAware.class, new Class[] {
        CharFilterFactory.class,
        TokenFilterFactory.class,
        TokenizerFactory.class,
        FieldType.class
      }
    );
  }

  /**
   * Utility function to throw an exception if the class is invalid
   */
  void assertAwareCompatibility( Class aware, Object obj )
  {
    Class[] valid = awareCompatibility.get( aware );
    if( valid == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Unknown Aware interface: "+aware );
    }
    for( Class v : valid ) {
      if( v.isInstance( obj ) ) {
        return;
      }
    }
    StringBuilder builder = new StringBuilder();
    builder.append( "Invalid 'Aware' object: " ).append( obj );
    builder.append( " -- ").append( aware.getName() );
    builder.append(  " must be an instance of: " );
    for( Class v : valid ) {
      builder.append( "[" ).append( v.getName() ).append( "] ") ;
    }
    throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, builder.toString() );
  }
}
