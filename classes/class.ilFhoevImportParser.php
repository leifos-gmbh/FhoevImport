<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './classes/class.ilSaxParser.php';

/**
 * Base fhoev xml parser
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
abstract class ilFhoevImportParser extends ilSaxParser
{
	protected $hasErrors = false;
	
	protected $cdata = '';
	
	protected $isUpdate = true;
	protected $soap = null;
	protected $soap_session = '';


	public function __construct($a_file) 
	{
		parent::__construct($a_file,true);
	}
	
	public function hasErrors()
	{
		return $this->hasErrors;
	}
	
	/**
	 * Set xml handlers
	 * @param type $a_xml_parser 
	 */
	public function setHandlers($a_xml_parser)
	{
		xml_set_object($a_xml_parser,$this);
		xml_set_element_handler($a_xml_parser,'handlerBeginTag','handlerEndTag');
		xml_set_character_data_handler($a_xml_parser,'handlerCharacterData');
	}
	
	abstract protected function handlerBeginTag($a_xml_parser,$a_name,$a_attribs);
	
	abstract protected function handlerEndTag($a_xml_parser,$a_name);
	
	/**
	 * end tag handler
	 *
	 * @param	ressouce	internal xml_parser_handler
	 * @param	string		data
	 * @access	private
	 */
	protected function handlerCharacterData($a_xml_parser,$a_data)
	{
		$a_data = preg_replace("/\n/","",$a_data);
		$a_data = preg_replace("/\t+/","",$a_data);
		$a_data = str_replace("<","&lt;",$a_data);
		$a_data = str_replace(">","&gt;",$a_data);

		if(!empty($a_data))
		{
			$this->cdata .= $a_data;
		}
	}
	
	/**
	 * Get Xml writer
	 * @return ilXmlWriter
	 */
	protected function getWriter()
	{
		return $this->writer;
	}
	
	/**
	 * Init writer
	 */
	protected function initWriter()
	{
		include_once './Services/Xml/classes/class.ilXmlWriter.php';
		$this->writer = new ilXmlWriter();
	}
	
	protected function isUpdate()
	{
		return $this->isUpdate;
	}
	
	protected function setUpdate($a_stat)
	{
		$this->isUpdate = $a_stat;
	}
	
	protected function lookupObjId($a_id, $a_type = '')
	{
		global $ilDB;
		
		$query = 'SELECT obj_id FROM object_data '.
				'WHERE import_id = '.$ilDB->quote($a_id,'text').' ';
		
		if($a_type)
		{
			$query  .= 'AND type = '.$ilDB->quote($a_type,'text');
		}
		
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			return $row->obj_id;
		}
		return 0;
	}
	
	/**
	 * Get soap client
	 * @return ilSoapClient
	 */
	protected function getSoapClient()
	{
		return $this->soap;
	}
	
	/**
	 * Init Soap client
	 */
	protected function initSoapClient($a_timeout = 10)
	{
		include_once './Services/WebServices/SOAP/classes/class.ilSoapClient.php';
		
		$this->soap = new ilSoapClient();
		$this->soap->setResponseTimeout($a_timeout);
		$this->soap->enableWSDL(true);
		if(!$this->soap->init())
		{
			throw new ilFhoevIOException("Error calling soap server");
		}
	}

	/**
	 * login soap
	 */
	protected function loginSoap()
	{
		$res = $this->getSoapClient()->call(
				'login', 
				array(
					CLIENT_ID,
					ilFhoevSettings::getInstance()->getSoapUser(),
					ilFhoevSettings::getInstance()->getSoapPass()
				)
		);
		$this->soap_session = $res;
	}
	
	/**
	 * Logout soap
	 */
	protected function logoutSoap()
	{
		$this->getSoapClient()->call(
				'logout',
				array(
					$this->soap_session
				)
		);
	}
	
	/**
	 * Get reference id
	 * @param type $a_id
	 * @return type 
	 */
	protected function getReferenceId($a_id)
	{
		$refs = ilObject::_getAllReferences($a_id);
		return end($refs);
	}
	
	protected function removeNode(SimpleXMLElement $ele)
	{
		$domNode = dom_import_simplexml($ele);
		return $domNode->parentNode->removeChild($domNode);
	}
	
}
?>
