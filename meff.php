<?php
/**
 * Meff.php
 *
 * @author    Tegan Snyder <tsnyder@tegdesign.com>
 * @license   MIT
 */

function __autoload($class_name) {
	$class_path = 'inc/' . $class_name . '.php';
    include $class_path;
}

class Meff
{

	const DISPLAY_CLI_ERRORS = true;
	const DEBUG_MODE = false;
	/*
	log levels:
		0 = all
		1 = normal
		2 = file list only
	*/
	const DEBUG_LEVEL = 2;

	protected static $params;
	protected static $magento_dir;
	protected static $extension_full_name;
	protected static $company_name;
	protected static $extension_name;
	protected static $app_etc_module_path;
	protected static $code_pool;
	protected static $extension_base_dir;

	// @SimpleXMLElement app/etc/modules/extension_full_name.xml 
	protected static $base_xml; 

	// @SimpleXMLElement app/code_pool/company_name/extension_name
	protected static $extension_config_xml;

	public $cli_txt;

	/**
	 * Instantiate all variables, class instances, and do the work
	 *
	 * @param array $argv
	 */
	public function __construct($argv) 
	{

		self::$params = $argv;

		$cli = new Cli(self::$params);
		$this->cli_txt = new CliColors();
		$extension_xml = new ExtensionXml();
		$xml_parser = new XmlParser();
		$file_iterator = new FileIterator();

		// identify all the extensions php files
		$php_files = $file_iterator->iterateFileSystem(
			array('php'), 
			self::$extension_base_dir
		);

		$layout_xml_files_from_xml = $xml_parser->getLayoutXmlFilesFromXml();

		$file_roots = $xml_parser->getFileRoots();

		$files_from_layout_xml = $xml_parser->getFilesFromLayoutXml();

		$file_mentions_php = $xml_parser->findFileMentions(
			$php_files, 
			array('.phtml', '.js', '.css', '.php')
		);

		$file_mentions_xml = $xml_parser->findFileMentions(
			$xml_parser->getFilesPathsFromLayoutXml(), 
			array('.phtml', '.js', '.css', '.php')
		);

		$likely_search_subfolder_paths = $xml_parser->getLikelySubFolderSearchPaths();
		$config_xml_file_paths = $xml_parser->identifyConfigXmlFilePaths();
		$layout_xml_file_paths = $xml_parser->identifyFilesFromLayoutXml();
		$file_mentions_file_paths = $xml_parser->identifyFileMentionsPaths($file_mentions_php);
		$lib_mentions = $xml_parser->parseSourceForLibs($php_files);
		
		$this->debugOut('magento_dir', self::$magento_dir);
		$this->debugOut('extension_full_name', self::$extension_full_name);
		$this->debugOut('company_name', self::$company_name);
		$this->debugOut('extension_name', self::$extension_name);
		$this->debugOut('app_etc_module_path', self::$app_etc_module_path);
		$this->debugOut('extension_base_dir', self::$extension_base_dir);
		$this->debugOut('code_pool', self::$code_pool);
		$this->debugOut('xml_updates', print_r($xml_parser->getXmlUpdates(), true), null, 2);
		$this->debugOut('xml_updates_in_list_format', print_r($xml_parser->getXmlUpdatesInListFormat(), true), null, 2);
		$this->debugOut('php_files', print_r($php_files, true), null, 2);
		$this->debugOut('layout_xml_files', print_r($layout_xml_files_from_xml, true), null, 2);
		$this->debugOut('file_roots', print_r($file_roots, true), null, 2);
		$this->debugOut('files_from_layout_xml', print_r($files_from_layout_xml, true), null, 2);
		$this->debugOut('file_mentions_php', print_r($file_mentions_php, true), null, 2);
		$this->debugOut('file_mentions_xml', print_r($file_mentions_xml, true), null, 2);
		$this->debugOut('likely_search_subfolder_paths', print_r($likely_search_subfolder_paths, true), null, 2);
		$this->debugOut('config_xml_file_paths', print_r($config_xml_file_paths, true), null, 2);
		$this->debugOut('layout_xml_file_paths', print_r($layout_xml_file_paths, true), null, 2);
		$this->debugOut('file_mentions_file_paths', print_r($file_mentions_file_paths, true), null, 2);
		$this->debugOut('lib_mentions', print_r($lib_mentions, true), null, 2);

		// now lets output what we think are the files involved in this extension

		$this->cli_txt->write('FILES IDENTIFIED FOR', self::$extension_full_name);

		echo self::$extension_base_dir . PHP_EOL;
		echo self::$app_etc_module_path . PHP_EOL;

		foreach ($config_xml_file_paths as $f_paths) {
			foreach ($f_paths as $p) {
				echo $p . PHP_EOL;
			}
		}

		foreach ($layout_xml_file_paths as $f_paths) {
			foreach ($f_paths as $p) {
				echo $p . PHP_EOL;
			}
		}

		foreach ($file_mentions_file_paths as $p) {
			echo $p . PHP_EOL;
		}

		foreach ($lib_mentions as $p) {
			echo $p . PHP_EOL;
		}


	}

	/**
	 * Get substring between two strings
	 *
	 * @param string $string
	 * @param string $start
	 * @param string $end
	 * @return string
	 */
	public function get_string_between($string, $start, $end) 
	{
		$string = " ".$string;
		$ini = strpos($string,$start);
		if ($ini == 0) return "";
		$ini += strlen($start);
		$len = strpos($string,$end,$ini) - $ini;
		return substr($string,$ini,$len);
	}

	/**
	* Output an error if a condition is not met
	*/
	public function displayError($error_string) 
	{
		if (self::DISPLAY_CLI_ERRORS) {
			die($this->cli_txt->write('ERROR', $error_string));
		}
	}



	public function debugOut(
		$info = null, 
		$extra_msg = null, 
		$debug_type = null, 
		$level = 1
	) {

		if (self::DEBUG_MODE) {

			$debug = false;
			if (self::DEBUG_LEVEL == 0) {
				$debug = true;
			} elseif (self::DEBUG_LEVEL == $level) {
				$debug = true;
			}

			if ($debug) {

				if ($debug_type != null) {
					$this->cli_txt->write('DEBUG', $debug_type . ' - ' . $info, $extra_msg);
				} else {
					$this->cli_txt->write('DEBUG', $info, $extra_msg);	
				}

			}

		}

	}

}


// do the magic
$meff = new Meff($argv);



