package org.apache.solr.schema;

import org.apache.lucene.search.SortField;
import org.apache.lucene.document.Fieldable;
import org.apache.solr.schema.CompressableField;
import org.apache.solr.schema.FieldType;
import org.apache.solr.schema.IndexSchema;
import org.apache.solr.schema.SchemaField;
import org.apache.solr.schema.TextField;
import org.apache.solr.request.XMLWriter;
import org.apache.solr.request.TextResponseWriter;
import org.apache.solr.common.SolrException;

import java.util.Map;
import java.io.IOException;

/**Get values from an field which contents ritch document address.
 *
  */


public class CeliExternalFileField extends TextField {//CompressableField {
	  private FieldType ftype;
	  private String keyFieldName;
	  private IndexSchema schema;
	  private String defVal;

	  protected void init(IndexSchema schema, Map<String,String> args) {
		  //restrictProps(SORT_MISSING_FIRST | SORT_MISSING_LAST);
	    //restrictProps(0x00000800 | 0x00001000); //a etudier l'utilité
	    String ftypeS = getArg("valType", args);
	    if (ftypeS!=null) {
	      ftype = schema.getFieldTypes().get(ftypeS);
	      if (ftype==null || !(ftype instanceof TextField)) {
	        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, "Only Text (TextField) is currently supported as external field type.  got " + ftypeS);
	      }
	    }   
	    
	    keyFieldName = args.remove("keyField");
	    String defValS = args.remove("defVal");
	    defVal = defValS==null ? "" : defValS;
	    super.init(schema, args);    
	    this.schema = schema;
	  }
	  /** Get field name who has ritch document address.
	  *
	   */
	  public String getKeyField(){
		  return keyFieldName;
	  }
	  public SortField getSortField(SchemaField field, boolean reverse) {
		    return getStringSort(field, reverse);
	  }

	  public void write(XMLWriter xmlWriter, String name, Fieldable f) throws IOException {
		    xmlWriter.writeStr(name, f.stringValue());
	  }
	  public void write(TextResponseWriter writer, String name, Fieldable f) throws IOException {
		    writer.writeStr(name, f.stringValue(), true);
	  }
	  



	}