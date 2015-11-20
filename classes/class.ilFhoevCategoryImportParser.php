<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Category import parser
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilFhoevCategoryImportParser extends ilFhoevImportParser
{
	protected $import_id = 0;
	
	
	/**
	 * constructor
	 * @param type $a_file 
	 */
	public function __construct($a_file)
	{
		parent::__construct($a_file);
		
		$this->initSoapClient();
		$this->loginSoap();
		
	}

	/**
	 * Begin tag
	 * @param type $a_xml_parser
	 * @param type $a_name
	 * @param type $a_attribs 
	 */
	protected function handlerBeginTag($a_xml_parser, $a_name, $a_attribs) 
	{
		
		switch($a_name)
		{
			case 'Categories':
				break;

			case 'Category':
				$this->import_id = $a_attribs['id'];
				$this->initWriter();
				$this->getWriter()->xmlStartTag('Objects');
				$obj_id = $this->lookupObjId($a_attribs['id'],'cat');
				if($obj_id)
				{
					$this->setUpdate(true);
					$this->getWriter()->xmlStartTag('Object', array('obj_id' => $obj_id, 'type' => 'cat'));
				}
				else
				{
					$this->setUpdate(false);
					$this->parent_id = $this->lookupParentId($a_attribs['parentId'],'cat');
					$this->getWriter()->xmlStartTag('Object', array('obj_id' => $obj_id, 'type' => 'cat'));
				}
				break;

			case 'Translations':
				break;

			case 'Translation':
				break;

			case 'Title':
				break;

			case 'Description':
				break;

			case 'Sorting':
				break;
		}
		
	}
	
	protected function handlerEndTag($a_xml_parser, $a_name) 
	{
		switch($a_name)
		{
			case 'Categories':
				break;

			case 'Category':
				$this->getWriter()->xmlEndTag('Object');
				$this->getWriter()->xmlEndTag('Objects');
				
				if($this->isUpdate())
				{
					$this->updateCategory();
				}
				else
				{
					$this->createCategory();
				}
				
				break;

			case 'Translations':
				break;

			case 'Translation':
				break;

			case 'Title':
				$this->getWriter()->xmlElement('Title', array(), $this->cdata);
				break;

			case 'Description':
				$this->getWriter()->xmlElement('Description', array(), $this->cdata);
				$this->getWriter()->xmlElement('ImportId',array(),$this->import_id);
				break;

			case 'Sorting':
				break;
		}
		
		$this->cdata = '';
	}
	
	/**
	 * Update category
	 */
	protected function updateCategory()
	{
		ilFhoevLogger::getLogger()->write('Update category: ' . $this->getWriter()->xmlDumpMem());
		$this->getSoapClient()->call(
				'updateObjects',
				array(
					$this->soap_session,
					$this->getWriter()->xmlDumpMem(false)
				)
		);
	}
	
	
	protected function createCategory()
	{
		ilFhoevLogger::getLogger()->write('Update category: ' . $this->getWriter()->xmlDumpMem());

		if(!$this->parent_id)
		{
			ilFhoevLogger::getLogger()->write('Invalid parent id given for: '. $this->getWriter()->xmlDumpMem(false));
			return;
		}
		ilFhoevLogger::getLogger()->write('Calling add object');
		$this->getSoapClient()->call(
				'addObject',
				array(
					$this->soap_session,
					$this->parent_id,
					$this->getWriter()->xmlDumpMem(false)
				)
		);
	}
	
	/**
	 * Return ref_id
	 * @param type $a_id
	 * @return int
	 */
	protected function lookupParentId($a_id,$a_type)
	{
		ilFhoevLogger::getLogger()->write('Lookup parent id for: '. $a_id);
		if($a_id == 0)
		{
			return 1;
		}
		$obj_id = $this->lookupObjId($a_id,$a_type);
		ilFhoevLogger::getLogger()->write('obj id is: '. $obj_id);
		$refs = ilObject::_getAllReferences($obj_id);
		$ref_id = end($refs);
		ilFhoevLogger::getLogger()->write('ref_id is: '. $ref_id);
		return $ref_id;
	}
}
?>
