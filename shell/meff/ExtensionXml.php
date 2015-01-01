<?php
/**
 * ExtXml.php
 *
 * @author    Tegan Snyder <tsnyder@tegdesign.com>
 * @license   MIT
 */
class ExtensionXml extends Meff
{

	/**
	 * Setup xml parsing activities
	 */
	public function __construct() 
	{

		$this->loadModuleXml();
		$this->getCodePool();
		$this->getMainExtensionDir();
		$this->getExtensionConfigXml();

	}

	/**
	 * Load the Magento extensions app/etc/module/*.xml file
	 *
	 * @return string
	 */
	private function loadModuleXml() 
	{

		// load in config.xml to simplexml parser
		parent::$base_xml = simplexml_load_file(parent::$app_etc_module_path);

	}

	/**
	 * Get the Magento extensions code pool directory
	 *
	 * @return string
	 */
	private function getCodePool() 
	{

		// determine what the extension code pool is
		$extension = parent::$extension_full_name;
		$xml = parent::$base_xml;
		$code_pool = $xml->modules[0]->$extension->codePool[0];

		if (!isset($code_pool)) {
			$this->displayError('The code pool is not there.');
		}

		parent::$code_pool = $code_pool;

		return $code_pool;

	}

	/**
	 * Get the path to the Magento extensions main code directory
	 *
	 * @return string
	 */
	private function getMainExtensionDir() 
	{

		// main directory to the extension
		$extension_base_dir = parent::$magento_dir . 
							  '/app/code/' . 
							  parent::$code_pool . '/' .
							  parent::$company_name . '/' . 
							  parent::$extension_name;

		if (!file_exists($extension_base_dir)) {
			$this->displayError('Cant find your extensions base directory.');
		}

		parent::$extension_base_dir = $extension_base_dir;

		return $extension_base_dir;

	}

	/**
	 * Load the Magento extensions main config.xml file
	 */
	private function getExtensionConfigXml() 
	{

		// path to the extensions config.xml
		$extension_config_xml_path = parent::$extension_base_dir . '/etc/config.xml';

		if (!file_exists($extension_config_xml_path)) {
			$this->displayError('Cant find path to extension config xml.');
		}

		$extension_config_xml = simplexml_load_file($extension_config_xml_path);

		parent::$extension_config_xml = $extension_config_xml;

	}

}