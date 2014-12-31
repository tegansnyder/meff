<?php
/**
 * Cli.php
 *
 * @author    Tegan Snyder <tsnyder@tegdesign.com>
 * @license   MIT
 */
class Cli extends Meff
{

	/**
	 * Instantiate PHP CLI class methods and variables
	 *
	 * @param array $argv
	 */
	public function __construct($argv) {

		// grab the command line paramters
		$this->params = $argv;

		// perform some basic checks
		$this->isExtensionNameProvided();
		$this->isExtensionNameFormatedCorrectly();
		$this->isMagentoDirectoryProvided();

		$this->getMagentoDir();
		$this->getExtensionFullName();
		$this->getCompanyName();
		$this->getExtensionName();
		$this->getAppEtcModulePath();
	}

	/**
	 * Check to ensure an extension name is sent via command line
	 */
	private function isExtensionNameProvided() 
	{

		if (!isset($this->params[1])) {
			$this->displayError('Extension name not given.');
		}

	}

	/**
	 * Check to ensure a path to a Magento directory is sent via command line
	 */
	private function isMagentoDirectoryProvided() 
	{

		if (!isset($this->params[2])) {
			$this->displayError('Magento directory path not given.');
		}

	}

	/**
	 * Check to ensure the extension name has an underscore in it
	 */
	private function isExtensionNameFormatedCorrectly() 
	{

		$extension = $this->params[1];

		if (strpos($extension,'_') === false) {
			$this->displayError('Extension format wrong.');
		}

	}

	/**
	 * Retrieves the extension name
	 *
	 * @return string
	 */
	public function getExtensionFullName() 
	{

		$extension = $this->params[1];

		// ensure that the first letter is capitalized for Magento standards
		$tmp = explode('_', $extension);
		$extension_full_name = ucwords($tmp[0]) . '_' . ucwords($tmp[1]);

		parent::$extension_full_name = $extension_full_name;
		return $extension_full_name;

	}

	/**
	 * Retrieves the Magento Directory
	 *
	 * @return string
	 */
	public function getMagentoDir() 
	{

		$magento_dir = rtrim($this->params[2], '/');

		parent::$magento_dir = $magento_dir;
		return $magento_dir;
		
	}

	/**
	 * Retrieves the extension's Company name
	 *
	 * @return string
	 */
	public function getCompanyName() 
	{

		//$extension = $this->getExtensionFullName();
		$extension = parent::$extension_full_name;
		$company_name = explode('_', $extension)[0];

		parent::$company_name = $company_name;
		return $company_name;
		
	}

	/**
	 * Retrieves the extension name
	 *
	 * @return string
	 */
	public function getExtensionName() 
	{

		$extension = $this->getExtensionFullName();
		$extension_name = explode('_', $extension)[1];

		parent::$extension_name = $extension_name;
		return $extension_name;
		
	}

	/**
	 * Get the path to the Magento extensions app/etc/module xml
	 *
	 * @return string
	 */
	public function getAppEtcModulePath() 
	{

		// path to module config.xml
		$app_etc_module_path = parent::$magento_dir . 
							   '/app/etc/modules/' . 
							   parent::$extension_full_name . '.xml';

		if (!file_exists($app_etc_module_path)) {
			$this->displayError('Cant find path to app etc module.');
		}

		parent::$app_etc_module_path = $app_etc_module_path;
		return $app_etc_module_path;
		
	}

}