<?

/******************************************

Fichero:		/php/classes/Document.php
Fecha:			22-04-2004
Autor:			Korvus

Copyright (c) Vivace Multimedia
http://www.vivacemultimedia.com

******************************************/

define( "DOM_INSERT_FIRST", 0 );
define( "DOM_INSERT_LAST", 1 );
define( "DOM_INSERT_AFTER", 1 );
define( "DOM_INSERT_BEFORE", 0 );

class Document
{

	// ==================== Atributos ==========================

	var $_path;
	
	var $encoding			=			"ISO-8859-1";
	var $version			=			"1.0";
	
	var $firstChild;
	var $_elements			=			array();
	
	// ==================== Metodos SET ========================
	
	function Document( $sFile = NULL )
	{
	 if( is_null( $sFile ) || !file_exists( $sFile ) )
	  return( false );
	 
	 $this->_path = $sFile;
	 $this->parseFromString( file_get_contents( $this->getPath() ) );
	}	

	function parseFromString( $sString )
	{
	 $sSource = preg_replace( "/>\s+</i", "><", $sString );
	 $oLevels = array();
	 
	 $oParser = xml_parser_create( $this->encoding );
	 xml_parser_set_option( $oParser, XML_OPTION_CASE_FOLDING, 0 );
	 xml_parse_into_struct( $oParser, $sSource, $oElements );
	 xml_parser_free( $oParser );
	 
	 # Situa los nodos en orden jerarquico con arrays
	 # No conserva los tags de fin de elemento ( </fin> ) en el arbol de elementos
	 
	 for( $i = 0; $i < count( $oElements ); $i++ )
	 {
	  # Si el elemento es del tipo cdata, subimos 1 nivel para que sea asignado al padre
	  # correcto, y dejamos el tag a 'NULL' para que la funcion 'createElement' cree un 'cdata'
	  
	  if( $oElements[ $i ][ "type" ] == "cdata" )
	  {
	   $oElements[ $i ][ "level" ]++;
	   $oElements[ $i ][ "tag" ] = NULL;
	  }
	  
	  if( $oElements[ $i ][ "type" ] != "close" )
	  {
	   $nIndex = array_push( &$this->_elements, $this->createElement( $oElements[ $i ][ "tag" ], $oElements[ $i ][ "value" ] ) ) - 1;
	   
	   # Propiedades del nuevo elemento XML
	   # valor, atributos, nodo padre, posicion dentro de los nodos hijos, documento raiz ...
	   
	   $this->_elements[ $nIndex ]->_root = &$this;
	   $this->_elements[ $nIndex ]->attributes = ( array ) $oElements[ $i ][ "attributes" ]; 
	   
	   $oLevels[ $oElements[ $i ][ "level" ] ] = &$this->_elements[ $nIndex ];
	   
	   if( is_object( $oLevels[ $oElements[ $i ][ "level" ] - 1 ] ) )
		$oLevels[ $oElements[ $i ][ "level" ] - 1 ]->appendChild( &$this->_elements[ $nIndex ] );
	  }
	  else
	   array_slice( $oElements, $i, 1 );
	 } 
	 
	 $this->firstChild = &$this->_elements[ 0 ];
	 $this->_elements[ 0 ]->parentNode = &$this;	
	}
	
	// ==================== Metodos GET ========================
	
	function getPath()
	{ return( $this->_path ); }
	
	# Devuelve un array con aquellos elementos cuyo tag coincida con
	# alguno de los elementos del array $oTagName
	
	function getElementsByTagName( $sTagName )
	{
	 for( $i = 0; $i < count( $this->_elements ); $i++ )
	 {
	  if( $sTagName == "*" || eregi( "^($sTagName)$", $this->_elements[ $i ]->nodeName ) )
	   $oMatches[] = &$this->_elements[ $i ];
	 }
	 
	 return( $oMatches );
	}
	
	# Devuelve el primer elemento cuyo identificativo sea igual a $sID
	
	function &getElementById( $sID )
	{ 
	 for( $i = 0; $i < count( $this->_elements ); $i++ )
	 {
	  if( strcmp( $this->_elements[ $i ]->getAttribute( "id" ), $sID ) == 0 )
	   return( $this->_elements[ $i ] );
	 }
	}
	
	# Devuelve un array de elementos cuyos atributos '$sName' tengan 
	# un valor identico al indicado en el segundo parametro '$sValue'
	
	function getElementsByAttribute( $sName, $sValue )
	{ 
	 for( $i = 0; $i < count( $this->_elements ); $i++ )
	 {
	  if( $this->_elements[ $i ]->getAttribute( $sName ) == $sValue )
	   $oMatches[] = &$this->_elements[ $i ];
	 }
	 
	 return( $oMatches );
	}	
	
	// ==================== Otros metodos ======================
	
	function isValid()
	{ return( is_object( $this->firstChild ) ); }
	
	# Crea un nuevo elemento con el tag, valor y atributos especificados
	
	function &createElement( $sTagName = NULL, $sValue = NULL, $oAttributes = array() )
	{ 
	 $oElement = new DocumentElement( $sTagName, $sValue );
	 $oElement->attributes = $oAttributes;
	 $oElement->_root = &$this;
	 
	 return( $oElement ); 
	}
	
	function createCdata( $sString )
	{ return( $this->createElement( NULL, $sString ) ); }
		
	function toString( $bFormatted = false, $bIncludeHeader = true )
	{ 
	 if( $bIncludeHeader == true )
	  $sReturn = "<?xml version=\"" . $this->version . "\" encoding=\"" . $this->encoding . "\" ?>\r\n\r\n";
	  
	 return( $sReturn . $this->firstChild->toString( true, false, $bFormatted ? 0 : -1 ) ); 
	}

}


class DocumentElement
{

	// ==================== Atributos ==========================

	var $nodeName			=		NULL;
	var $nodeValue			=		NULL;
	
	var $attributes			=		array();	
	
	var $parentNode			=		NULL;
	var $firstChild			=		NULL;
	var $lastChild			=		NULL;
	var $nextSibling		=		NULL;
	var $previousSibling	=		NULL;
		
	// ==================== Metodos SET ========================

	function DocumentElement( $sTagName, $sValue = NULL )
	{ 
	 $this->nodeName = $sTagName; 
	 $this->nodeValue = $sValue;
	}
	
	function setAttribute( $sKey, $sValue, $bOverWrite = true )
	{ 
	 if( $bOverWrite == true || !isset( $this->attributes[ $sKey ] ) )
	  $this->attributes[ $sKey ] = $sValue;
	}
	
	function removeAttribute( $sKey )
	{ unset( $this->attributes[ $sKey ] ); }
	
	// ==================== Metodos GET ========================

	function getContent()
	{
	 if( !is_null( $this->nodeValue ) || !$this->hasChildNodes() )
	  return( $this->nodeValue );	
	 else
	  return( $this );
	}
	
	function getAttribute( $sName )
	{ return( $this->attributes[ $sName ] ); }
	
	# Busca todos los elementos a partir de 'this' cuyo tag sea igual al patron
	# especificado en 'sTagName'
	# Esta funcion realiza una busqueda recursiva a la que podemos definir el numero
	# maximo de niveles a buscar ( predeterminado -1: todos los niveles )
	
	function getElementsByTagName( $sTagName, $nMaxLevels = -1, $nLevel = 0 )
	{ 
	 if( $nMaxLevels == -1 || $nLevel < $nMaxLevels )
	 {
	  $oMatches = array();
	  $oChild 	= &$this->firstChild;
	  
	  while( $oChild !== NULL )
	  {
	   if( $sTagName == "*" || eregi( "(" . $sTagName . ")", $oChild->nodeName ) )
		array_push( $oMatches, &$oChild );
		
	   $oMatches = array_merge( &$oMatches, $oChild->getElementsByTagName( $sTagName, $nMaxLevels, $nLevel + 1 ) );	   
	   $oChild	 = &$oChild->nextSibling;
	  }
	  
	  return( $oMatches );
	 }
	}
	
	function hasChildNodes()
	{ return( $this->firstChild !== NULL ); }
	
	function hasAttribute( $sName )
	{ return( isset( $this->attributes[ $sName ] ) ); }

	// ==================== Otros metodos ======================
	
	# Agrega un nuevo elemento hijo
	# Si es la primera vez que se le agrega un hijo, setearemos 
	# los punteros 'firstChild' y 'lastChild'
	
	function &appendChild( &$oNewChild, $nPosition = DOM_INSERT_LAST )
	{ 
	 $oNewChild->unlink();
	 $oNewChild->parentNode = &$this;
	
	 if( $this->firstChild === NULL )
	 {
	  $this->firstChild = &$oNewChild;
	  $this->lastChild  = &$oNewChild;
	 }
	 else
	 {
	  switch( $nPosition )
	  {
	   case DOM_INSERT_FIRST:
	   
	    $oNewChild->nextSibling = &$this->firstChild;
		$this->firstChild->previousSibling = &$oNewChild;
		$this->firstChild = &$oNewChild;
	   
	   break;
	  
	   case DOM_INSERT_LAST: 
	  
	  	$oNewChild->previousSibling = &$this->lastChild;
	  	$this->lastChild->nextSibling = &$oNewChild;
  	  	$this->lastChild = &$oNewChild;
	
	   break;
	  }
	 }
	 
	 return( $oNewChild );
	}
	
	# Agrega un nuevo elemento hermano, en el mismo nivel que el actual
	# y a continuacion del mismo. Tiene en cuenta si el nuevo nodo tiene
	# tambien nodos en su mismo nivel, y los enlaza correctamente
	
	function &appendSibling( &$oNewSibling, $nPosition = DOM_INSERT_AFTER )
	{
	 $oNewSibling->unlink();
	 $oNewSibling->parentNode = &$this->parentNode;
	 	
	 switch( $nPosition )
	 {
	  case DOM_INSERT_AFTER:
	  
	   if( $this->nextSibling !== NULL )
	   {
		$oNewSibling->nextSibling = &$this->nextSibling;
		$this->nextSibling->previousSibling = &$oNewSibling;
	   }
	   else
		$this->parentNode->lastChild = &$oNewSibling;
	  
	   $oNewSibling->previousSibling = &$this;
	   $this->nextSibling = &$oNewSibling;
	   
	  return( $oNewSibling );
	  
	  case DOM_INSERT_BEFORE:
	  
	   if( $this->previousSibling !== NULL )
	   {
	    $oNewSibling->previousSibling = &$this->previousSibling;
		$this->previousSibling->nextSibling = &$oNewSibling;
	   }
	   else
	    $this->parentNode->firstChild = &$oNewSibling;
		
	   $oNewSibling->nextSibling = &$this;
	   $this->previousSibling = &$oNewSibling;
	   
	  return( $oNewSibling );
	 }
	}
	
	# Reemplaza un nodo
	
	function &replace( $oNode )
	{
	 $this->nodeName   = $oNode->nodeName;
	 $this->nodeValue  = $oNode->nodeValue;
	 $this->attributes = $oNode->attributes;
	 
	 unset( $this->firstChild, $this->lastChild );
	 
	 if( $oNode->hasChildNodes() )
	 {
	  $this->firstChild = &$oNode->firstChild;
	  $this->lastChild  = &$oNode->lastChild;
	  $oNextSibling 	= &$oNode->firstChild;
	  
	  while( $oNextSibling !== NULL )
	  {
	   $oNextSibling->parentNode = &$this;
	   $oNextSibling = &$oNextSibling->nextSibling;
	  }
	 }
	 
	 return( $this );
	}
	
	# Clona un nodo y todos sus subhijos ( estos ultimos solo en caso de que
	# el primer parametro sea 'true'
	
	function &copy( $bCloneChildren = true )
	{
	 $oNewNode = $this->_root->createElement( $this->nodeName, $this->nodeValue );
	 $oNewNode->attributes = $this->attributes;
	 $oNewNode->_root = &$this->_root;
	 
	 if( $bCloneChildren == true && $oChild = &$this->firstChild )
	 {
	  while( $oChild !== NULL )
	  {
	   $oNewChild = &$oChild->copy( $bCloneChildren );
	   $oNewNode->appendChild( &$oNewChild, DOM_INSERT_LAST );
	   $oChild = &$oChild->nextSibling;
	  }
	 }
	
	 return( $oNewNode );
	}
	
	# Desvincula el nodo del arbol de elementos

	function unlink()
	{ 
	 if( $this->nextSibling !== NULL ) $this->nextSibling->previousSibling = &$this->previousSibling;
	 else $this->parentNode->lastChild = &$this->previousSibling;
	  
	 if( $this->previousSibling !== NULL ) $this->previousSibling->nextSibling = &$this->nextSibling;
	 else $this->parentNode->firstChild = &$this->nextSibling;
	  
	 unset( $this->parentNode, $this->nextSibling, $this->previousSibling );
	}
	
	# Devuelve el codigo del nodo y sus hijos
	# Recibe un argumento opcional 'nTabs' utilizado internamente que indica
	# el numero de tabuladores asociados al nodo actual ( nivel )
	
	function toString( $bIncludeChildren = true, $bOnlyChildren = false, $nTabs = -1 )
	{
	 # Cdata section. No tiene en cuenta atributos ni hijos
	
	 if( $this->nodeName === NULL )
	  return( "<![CDATA[" . $this->nodeValue . "]]>" );
	 
	 # Atributos del elemento
	  
	 foreach( $this->attributes as $sKey => $sValue )
	  $sAttributes .= " " . $sKey . "=\"" . $sValue . "\"";
	  
	 # Si tiene hijos almacenamos el codigo de estos en 'sChildren'
	 # Ademas lo tabulamos mediante la variable 'nTabs'
	  
	 if( $bIncludeChildren == true && $this->hasChildNodes() )
	 {
	  $oCurrentNode = &$this->firstChild;
	  
	  if( $nTabs != -1 && ( $this->firstChild->nextSibling !== NULL || $this->firstChild->hasChildNodes() ) )
	   $sTabCode = "\n" . str_repeat( "\t", $nTabs + 1 );
	  
	  while( $oCurrentNode !== NULL )
	  { 
	   $sChildren   .=  $sTabCode . $oCurrentNode->toString( true, false, ( $nTabs == -1 ? $nTabs : $nTabs + 1 ) );
	   $oCurrentNode =  &$oCurrentNode->nextSibling;
	  }
	  
	  if( $nTabs != -1 && ( $this->firstChild->nextSibling !== NULL || $this->firstChild->hasChildNodes() ) )
	   $sChildren .= "\n" . str_repeat( "\t", $nTabs );
	 }
	 
	 if( $bOnlyChildren == true )
	  return( $sChildren );
	 
	 # Finalmente devolvemos el codigo pertinente dependiendo de si el nodo
	 # tiene hijos o no y si tiene un valor no nulo
	 
	 if( $this->nodeValue === NULL && !isset( $sChildren ) )
	  return( "<" . $this->nodeName . $sAttributes . " />" );
	 else
 	  return( "<" . $this->nodeName . $sAttributes . ">" . $this->nodeValue . $sChildren . "</" . $this->nodeName . ">" );
	}


}

?>