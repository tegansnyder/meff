<?php
/**
 * Magento Extension File Finder
 * meff.php - attempt to find all files that make up a extension
 * 
 * usage:
 *   php meff.php Aoe_Scheduler /var/www/magento_root
 *
 * @todo - how do we get /lib files or base /js folder files?
 * @author      Tegan Snyder <tsnyder@tegdesign.com>
 * @copyright   Do whatever the heck you want with it.
 */


// command line parameter for extension name
if (!isset($argv[1])) {
	die('ERROR: Extension name not given.') . PHP_EOL;
}

if (!isset($argv[2])) {
	die('ERROR: Magento directory path not given.') . PHP_EOL;
}

$extension = $argv[1];

if (strpos($extension,'_') === false) {
	die('ERROR: Extension format wrong.') . PHP_EOL;
}

$tmp = explode('_', $extension);
$extension = ucwords($tmp[0]) . '_' . ucwords($tmp[1]);

$magento_dir = rtrim($argv[2], '/');

$company = explode('_', $extension)[0];
$extension_name = explode('_', $extension)[1];


// path to module config.xml
$app_etc_module_path = $magento_dir . '/app/etc/modules/' . $extension . '.xml';

if (!file_exists($app_etc_module_path)) {
	die('ERROR: Cant find path to app etc module.') . PHP_EOL;
}

// load in config.xml to simplexml parser
$xml = simplexml_load_file($app_etc_module_path);

// determine what the extension code pool is
$code_pool = $xml->modules[0]->$extension->codePool[0];

if (!isset($code_pool)) {
	die('ERROR: The code pool is not there.') . PHP_EOL;
}


// main directory to the extension
$extension_base_dir = $magento_dir . '/app/code/' . $code_pool . '/' . $company . '/' . $extension_name;

if (!file_exists($extension_base_dir)) {
	die('ERROR: Cant find your extensions base directory.') . PHP_EOL;
}



// path to the extensions config.xml
$extension_config_xml_path = $extension_base_dir . '/etc/config.xml';

if (!file_exists($extension_config_xml_path)) {
	die('ERROR: Cant find path to extension config xml.') . PHP_EOL;
}

$extension_config_xml = simplexml_load_file($extension_config_xml_path);


/**
 * Retrieves XML data
 *
 * @param string $node_type
 * @param string $xml (path to extensions config.xml file)
 * @param array $root_nodes (global, frontend, adminhtml)
 * @return array
 */
function getXmlData($node_type, $xml, $root_nodes) {

	$updates = array();

	foreach ($root_nodes as $root_node) {
		$updates[$root_node] = getXmlNode($xml, $root_node, $node_type);
	}

	return $updates;
}

/**
 * Retrieves node specific data
 *
 * @param string $xml (path to extensions config.xml file)
 * @param array $root_nodes (global, frontend, adminhtml)
 * @param array $node_type (layout, translate, template)
 * @return array
 */
function getXmlNode($xml, $root_node, $node_type) {

	$xml_data = array();

	if (isset($xml->$root_node)) {

		$node = $xml->$root_node;

		if ($node_type == 'layout') {

			$xml_data = getLayoutFiles($node);

		} elseif ($node_type == 'translate') {

			$xml_data = getTranslateFiles($node);

		}  elseif ($node_type == 'template') {

			$xml_data = getEmailTemplates($node);

		}
		
	}

	return $xml_data;

}

/**
 * Retrieve the XML files from config.xml
 *
 * @param string $xml (path to extensions config.xml file)
 * @param array $xml_node (layout, translate, template)
 * @return array
 */
function getFilesFromXml($xml, $xml_node) {

	$data = array();

	foreach ($xml_node as $node) {

		$data[$node] = getXmlData($node, $xml, 
			array(
				'global', 
				'frontend', 
				'adminhtml'
			)
		);

	}

	return $data;

}

/**
 * Get that Magento layout xml files
 *
 * @param string $main_node (global, frontend, adminhtml)
 * @return array
 */
function getLayoutFiles($main_node) {

	$xml_files = array();

	foreach ($main_node as $node) {

		if (isset($node->layout)) {

			foreach ($node->layout as $layout) {

				if (isset($layout->updates)) {

					foreach ($layout->updates->children() as $layout_update) {

						foreach ($layout_update->file as $xml_file) {
							$xml_files[] = (string)$xml_file;
						}

					}

				}

			}

		}

	}

	return $xml_files;

}

/**
 * Get that Magento translate csvs
 *
 * @param string $main_node (global, frontend, adminhtml)
 * @return array
 */
function getTranslateFiles($main_node) {

	$csv_files = array();

	foreach ($main_node as $node) {

		if (isset($node->translate)) {

			foreach ($node->translate as $translate) {

				if (isset($translate->modules)) {

					foreach ($translate->modules->children() as $translate_node) {

						foreach ($translate_node->files->children() as $csv_file) {
							$csv_files[] = (string)$csv_file;

						}

					}

				}

			}

		}

	}

	return $csv_files;

}

/**
 * Get that Magento transactional email templates
 *
 * @param string $main_node (global, frontend, adminhtml)
 * @return array
 */
function getEmailTemplates($main_node) {

	$email_templates = array();

	foreach ($main_node as $node) {

		if (isset($node->template)) {

			foreach ($node->template as $template) {

				if (isset($template->email)) {

					foreach ($template->email->children() as $email_node) {

						foreach ($email_node->file as $email_template_file) {
							$email_templates[] = (string)$email_template_file;

						}

					}

				}

			}

		}

	}

	return $email_templates;

}




/**
 * Get substring between two strings
 *
 * @param string $string
 * @param string $start
 * @param string $end
 * @return string
 */
function get_string_between($string, $start, $end) {
	$string = " ".$string;
	$ini = strpos($string,$start);
	if ($ini == 0) return "";
	$ini += strlen($start);
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
}

/**
 * Recursively looks through directories returning files found
 *
 * @param array $look_dir array(base_dir, relative_path)
 * @param string $pattern (regex pattern for searching directories)
 * @param string $extract_between (optional start and end to extract text)
 * @return array
 */
function iterateFileSystem($look_dir, $pattern, $extract_between = array()) {

	$data = array();

	if (!isset($look_dir['base_dir'])) {
		return $data;
	}

	$directory = new RecursiveDirectoryIterator(
	    $look_dir['base_dir'],
	    RecursiveDirectoryIterator::KEY_AS_FILENAME | 
	    RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
	);

	$files = new RegexIterator(
	    new RecursiveIteratorIterator($directory),
	    $pattern,
	    RegexIterator::MATCH,
	    RegexIterator::USE_KEY
	);


	foreach ($files as $f) {

		$filename = (string)$f;

		if (isset($extract_between['start']) && isset($extract_between['end'])) {

			$handle = fopen($filename, 'r');
			if ($handle) {

				if (isset($look_dir['relative_path'])) {
					$path_key = str_replace($look_dir['relative_path'], '', $filename);
				} else {
					$path_key = $filename;
				}

			    while (($line = fgets($handle)) !== false) {

			        $parsed = get_string_between($line, $extract_between['start'], $extract_between['end']);

			        if (!empty($parsed)) {
			        	$data[$path_key][] = $parsed;
			    	}

			    }
			}
			fclose($handle);

		} else {

			if (isset($look_dir['relative_path'])) {
				$data[] = ltrim(str_replace($look_dir['relative_path'], '', $filename),'/');
			} else {
				$data[] = $filename;
			}

		}

	}

	return $data;


}


// this array stores any xml layout update files found
$xml_updates = array();
$xml_updates = getFilesFromXml($extension_config_xml,
	array(
		'layout', 
		'translate', 
		'template'
		)
);



// loop through files in app directory looking for setTemplate
// this is useful information because extensions can dynamically
// output phtml via this method
$setTemplate_phtml_files = array();
$setTemplate_phtml_files = iterateFileSystem(
	array(
		'relative_path' => $magento_dir, 
		'base_dir' => $extension_base_dir
	),
	'/^.+\.php$/i', 
	array(
		'start' => 'setTemplate(',
		'end' => ')'
	)
);


/*
loop through app/design folders

app/design
 - adminhtml
  - any number of directories
 - frontend
  - any number of directories

*/
$layout_xml_files = array();

foreach ($xml_updates['layout'] as $layout_node => $xml_files) {

	if ($layout_node == 'frontend') {
		$xml_dir = 'app/design/frontend';
	} else if ($layout_node == 'adminhtml') {
		$xml_dir = 'app/design/adminhtml';
	}

	foreach ($xml_files as $xml_file) {

		// get just the filename after the directory
		if (strpos($xml_file,'/') !== false) {
			$xml_file = substr($xml_file, strrpos($xml_file, '/') + 1);
		}

		// we are looking for full path to a layout xml file
		$layout_xml_files[$layout_node][] = iterateFileSystem(
			array(
				'relative_path' => $magento_dir,
				'base_dir' => $magento_dir . '/' . $xml_dir . '/'
			),
			'#^'.$xml_file.'$#'
		);

	}

}


$layout_roots = array();
$design_files = array();

/*
Next lets loop through the layout xml files for the extension.
Lets try and locate the full path to any action addItem included
files
*/
foreach ($layout_xml_files as $design_folder => $xml_files) {

	foreach ($xml_files as $xml_file) {

		foreach ($xml_file as $f) {

			$layout_roots[] = get_string_between($f, 'app/design/' . $design_folder, 'layout');

			$file_path = $magento_dir . '/' . $f;

			if (file_exists($file_path)) {
		
				$xml = simplexml_load_file($file_path);

				$result = $xml->xpath('//action[@method="addJs"]');

				foreach ($result as $node) {

					if (isset($node->file)) {
						$design_files[$design_folder]['js'][] = (string)$node->file;
					}

					if (isset($node->script)) {
						$design_files[$design_folder]['js'][] = (string)$node->script;
					}

					if (isset($node->name)) {
						$design_files[$design_folder]['js'][] = (string)$node->name;
					}

					// edge case here
					// magento allows you to use any 
					// node convention you want. most people use
					// <script>filename.js</script>
					// or <file>filename.js</file>
					// but you could really use <anything>filename.js</anything>

				}

				$result = $xml->xpath('//action[@method="addCss"]');

				foreach ($result as $node) {

					if (isset($node->stylesheet)) {
						$design_files[$design_folder]['skin_css'][] = (string)$node->stylesheet;
					}

					if (isset($node->name)) {
						$design_files[$design_folder]['skin_css'][] = (string)$node->name;
					}

				}

				$result = $xml->xpath('//action[@method="addItem"]');

				foreach ($result as $node) {

					if (isset($node->type)) {
						if (isset($node->name)) {
							$design_files[$design_folder][(string)$node->type][] = (string)$node->name;
						}
						if (isset($node->script)) {
							$design_files[$design_folder][(string)$node->type][] = (string)$node->script;
						}
					}

				}


				// @todo: revist thsis as addItem can use a helper method function to find
				// path to file:
				// i.e.: <name helper="tegdesign_emailcollector/data/getCSSFilename" />

				$result = $xml->xpath('//action[@method="addItem"]//name[@helper]');

				foreach ($result as $node) {

					$attributes = $node->attributes();

					if (isset($attributes->helper)) {

						$design_files[$design_folder]['helper'][] = (string)$attributes->helper;

					}

				}


				// loop through blocks
				$result = $xml->xpath('//block[not(node())]');

				foreach ($result as $node) {

					$attributes = $node->attributes();

					if (isset($attributes->type)) {

						if (isset($attributes->template)) {

							$design_files[$design_folder]['blocks'][(string)$attributes->type][] = (string)$attributes->template;

						}

					}

				}

			}

		}

	}

}

// echo 'design_files: ' . PHP_EOL;
// echo '<pre>';
// print_r($design_files);
// echo '</pre>';

// clean up the base to package roots

// add stock layout roots
$layout_roots[] = '/base/default/';
$layout_roots[] = '/default/default/';
$layout_roots[] = '/enterprise/default/';
$layout_roots[] = '/pro/default/';
$layout_roots[] = '/rwd/default/';

$layout_roots = array_unique($layout_roots);

// echo '<pre>';
// print_r($layout_roots);
// echo '</pre>';


$extension_files = array();

// base extension app/etc/modules config and directory
$extension_files[] = $app_etc_module_path;
$extension_files[] = $extension_base_dir;

// loop through the layout files and determine if they exist
foreach ($layout_xml_files as $design_folder => $xml_files) {

	foreach ($xml_files as $xml_file) {

		foreach ($xml_file as $f) {

			$fp = $magento_dir . '/' . $f;
			if (file_exists($fp)) {
				// if they exist add them to a array
				$extension_files[] = $fp;
			}

		}

	}

}

// @todo - this can be removed as extension_file_incs will replace it
// loop through the files included via setTemplate method
foreach ($setTemplate_phtml_files as $file_location => $file_paths) {

	foreach ($file_paths as $f) {

		$f = str_replace("'", '', $f);

		foreach ($layout_roots as $root_folder) {

			// we are looking in both design packages
			// if we find a file exists we add it to array
			$fp = $magento_dir . '/app/design/adminhtml' . $root_folder . 'template/' . $f;
			if (file_exists($fp)) {
				$extension_files[] = $fp;
			}

			$fp = $magento_dir . '/app/design/frontend' . $root_folder . 'template/' . $f;
			if (file_exists($fp)) {
				$extension_files[] = $fp;
			}

		}
		
	}
}

// extension_file_incs files found in the php files of the extension base dir
// loop through the files included via setTemplate method
foreach ($setTemplate_phtml_files as $file_location => $file_paths) {

	foreach ($file_paths as $f) {

		$f = str_replace("'", '', $f);

		foreach ($layout_roots as $root_folder) {

			// we are looking in both design packages
			// if we find a file exists we add it to array
			$fp = $magento_dir . '/app/design/adminhtml' . $root_folder . 'template/' . $f;
			if (file_exists($fp)) {
				$extension_files[] = $fp;
			}

			$fp = $magento_dir . '/app/design/frontend' . $root_folder . 'template/' . $f;
			if (file_exists($fp)) {
				$extension_files[] = $fp;
			}

		}
		
	}
}


if (isset($design_files['frontend']['blocks'])) {

	foreach ($design_files['frontend']['blocks'] as $block_type => $blocks) {

		foreach ($blocks as $f) {

			foreach ($layout_roots as $root_folder) {

				$fp = $magento_dir . '/app/design/frontend' . $root_folder . 'template/' . $f;
				if (file_exists($fp)) {
					$extension_files[] = $fp;
				}

			}

		}

	}

}

if (isset($design_files['adminhtml']['blocks'])) {

	foreach ($design_files['adminhtml']['blocks'] as $block_type => $blocks) {

		foreach ($blocks as $f) {

			foreach ($layout_roots as $root_folder) {

				$fp = $magento_dir . '/app/design/adminhtml' . $root_folder . 'template/' . $f;
				if (file_exists($fp)) {
					$extension_files[] = $fp;
				}

			}

		}

	}

}

if (isset($design_files['frontend']['helper'])) {

	foreach ($design_files['frontend']['helper'] as $h) {

		//@todo
		//this is where we would need to load in Magento
		// and actually call the helper method

		/*
		require_once($magento_dir . '/app/Mage.php');
		umask(0);
		Mage::app();

		$tmp = explode('/', $h);
		$helper_name = $tmp[0];
		$helper_method = $tmp[2];

		$tmp = Mage::helper($helper_name)->$helper_method;
		*/

	}

}

if (isset($design_files['adminhtml']['helper'])) {

	foreach ($design_files['frontend']['helper'] as $h) {

		//@todo
		//this is where we would need to load in Magento
		// and actually call the helper method

	}

}


/* 
skin_css: relative to skin directory of your theme.
*/
if (isset($design_files['frontend']['skin_css'])) {

	foreach ($design_files['frontend']['skin_css'] as $f) {

		foreach ($layout_roots as $root_folder) {

			$fp = $magento_dir . '/skin/frontend' . $root_folder . $f;
			if (file_exists($fp)) {
				// if the file exists lets add it to our array
				$extension_files[] = $fp;
			}

		}

	}

}

/* 
skin_css: relative to skin directory of your theme.
*/
if (isset($design_files['adminhtml']['skin_css'])) {

	foreach ($design_files['adminhtml']['skin_css'] as $f) {

		foreach ($layout_roots as $root_folder) {

			$fp = $magento_dir . '/skin/adminhtml' . $root_folder . $f;
			if (file_exists($fp)) {
				// if the file exists lets add it to our array
				$extension_files[] = $fp;
			}

		}

	}

}

/*
skin_js: relative to skin/js directory of your theme.
*/
if (isset($design_files['frontend']['skin_js'])) {

	foreach ($design_files['frontend']['skin_js'] as $f) {

		foreach ($layout_roots as $root_folder) {

			$fp = $magento_dir . '/skin/frontend' . $root_folder . $f;
			if (file_exists($fp)) {
				// if the file exists lets add it to our array
				$extension_files[] = $fp;
			}

		}

	}

}

/*
skin_js: relative to skin/js directory of your theme.
*/
if (isset($design_files['adminhtml']['skin_js'])) {

	foreach ($design_files['adminhtml']['skin_js'] as $f) {

		foreach ($layout_roots as $root_folder) {

			$fp = $magento_dir . '/skin/adminhtml' . $root_folder . $f;

			if (file_exists($fp)) {
				// if the file exists lets add it to our array
				$extension_files[] = $fp;
			}

		}

	}

}

/*
js: relative to /js directory of your Magento installation.
*/
if (isset($design_files['frontend']['js'])) {

	foreach ($design_files['frontend']['js'] as $f) {

		$fp = $magento_dir . '/js/' . $f;
		if (file_exists($fp)) {
			// if the file exists lets add it to our array
			$extension_files[] = $fp;
		}
	}

}

/*
js: relative to /js directory of your Magento installation.
*/
if (isset($design_files['adminhtml']['js'])) {

	foreach ($design_files['adminhtml']['js'] as $f) {

		$fp = $magento_dir . '/js/' . $f;
		if (file_exists($fp)) {
			// if the file exists lets add it to our array
			$extension_files[] = $fp;
		}
	}

}

/*
js_css: css relative to the /js directory
*/
if (isset($design_files['frontend']['js_css'])) {

	foreach ($design_files['frontend']['js_css'] as $f) {

		$fp = $magento_dir . '/js/' . $f;
		if (file_exists($fp)) {
			// if the file exists lets add it to our array
			$extension_files[] = $fp;
		}
	}

}

/*
js_css: css relative to the /js directory
*/
if (isset($design_files['adminhtml']['js_css'])) {

	foreach ($design_files['adminhtml']['js_css'] as $f) {

		$fp = $magento_dir . '/js/' . $f;
		if (file_exists($fp)) {
			// if the file exists lets add it to our array
			$extension_files[] = $fp;
		}
	}

}


$translate_csv_files = array();
// look through the translate csvs
foreach ($xml_updates['translate'] as $layout_node => $csv_files) {

	foreach ($csv_files as $csv_file) {

		$fp = iterateFileSystem(
			array(
				'relative_path' => $magento_dir,
				'base_dir' => $magento_dir . '/app/locale/'
			),
			'#^'.$csv_file.'$#'
		);

		$translate_csv_files[$layout_node][] = $fp;

		foreach ($fp as $f) {
			$f = $magento_dir . '/' . $f;
			if (file_exists($f)) {
				// if the file exists lets add it to our array
				$extension_files[] = $f;
			}
		}

	}

}


// get a array of all the PHP files involved in the extension
$extension_php_files = array();
$extension_php_files = iterateFileSystem(
	array(
		'base_dir' => $extension_base_dir
	),
	'/^.+\.php$/i'
);

// get a array of all the PHP files involved in the extension
$extension_xml_files = array();
$extension_xml_files = iterateFileSystem(
	array(
		'base_dir' => $extension_base_dir
	),
	'/^.+\.xml$/i'
);

// echo 'extension_php_files: ' . PHP_EOL;
// echo '<pre>';
// print_r($extension_php_files);
// echo '</pre>';


// look php files for references to /lib files
$new_class_declarations = array();
foreach ($extension_php_files as $f) {

	$handle = fopen($f, 'r');
	if ($handle) {

	    while (($line = fgets($handle)) !== false) {

	       	$tmp = get_string_between($line, 'new ', ');');

	       	// do we have something and is the first character capitalized like a class instance
	       	if (!empty($tmp) && preg_match('/[A-Z]/', $tmp[0])) {

	       		$tmp = 'new ' . $tmp;

	       		// what reserved classes we looking to skip
				if (0 !== strpos($tmp, 'new Mage') &&
					0 !== strpos($tmp, 'new Enterprise') &&
					0 !== strpos($tmp, 'new Exception') &&
					0 !== strpos($tmp, 'new Zend')  &&
					0 !== strpos($tmp, 'new Varien')
				) {

					$tmp = get_string_between($tmp, 'new ', '(');
					if (!empty($tmp)) {
	       				$new_class_declarations[] = $tmp;
	       			}
				}

	       	}

	    }
	}
	fclose($handle);

}

// echo PHP_EOL;
// echo 'new_class_declarations' . PHP_EOL;
// echo '<pre>';
// print_r($new_class_declarations);
// echo '</pre>';


$lib_classes = array();
foreach ($new_class_declarations as $c) {

	// skip if a space is found
	if (preg_match('/\s/',$c)) {
		continue;
 	}

	if (strpos($c,'_') !== false) {

		// explode out on _ to find folder names
		$tmp = explode('_', $c);

		$look_path = $magento_dir . '/lib';

		$i = 0;
		$len = count($tmp);

		foreach ($tmp as $p) {
			if ($i == $len - 1) {
				$p = $p . '.php';
			}
			$look_path .= '/' . $p;
			$i++;
		}

		if (file_exists($look_path)) {
			$lib_classes[] = $look_path . PHP_EOL;
		}

	}

}

$lib_classes = array_unique($lib_classes);
$lib_classes = array_values($lib_classes);

// echo 'lib_classes' . PHP_EOL;
// echo '<pre>';
// print_r($lib_classes);
// echo '</pre>';

foreach ($lib_classes as $f) {
	$extension_files[] = $f;
}



function parseFilenameFromLine($ext, $line) {

	$data = array();

   	if (strpos($line, $ext) !== FALSE) {
   		// find references to phtml files
		$tmp = explode($ext, $line);
		foreach ($tmp as $t) {
			$t = str_replace('"', "'", $t);
			$t = str_replace(';', "", $t);
			$t = str_replace(')', "", $t);
			$t = str_replace('(', "", $t);
			$t = array_pop(explode("'", $t)) . $ext;
			$t = trim($t);
			if (empty($t)) {
				continue;
			}
			// if its not blank
			if ($t != $ext) {
				$data[] = $t;
			}
		}
   	}

   	return $data;

}

function initFileLineSearcher($exts, $line) {
	
	$data = array();

	foreach ($exts as $e) {

		$tmp = parseFilenameFromLine($e, $line);

    	if (isset($tmp) && !empty($tmp)) {
    		foreach ($tmp as $d) {
    			$data['.phtml'][] = $d;
    		}
    	}

	}

	return $data;

}


// lets find any mention of filename in an extension
$extension_file_incs = array();
foreach ($extension_php_files as $f) {

	$handle = fopen($f, 'r');

	if ($handle) {

	    while (($line = fgets($handle)) !== false) {

	    	$data = initFileLineSearcher(
	    		array('.phtml', 
	    			  '.js',
	    			  '.css',
	    			  '.php'
	    		), $line
	    	);

	    	if (isset($data) && !empty($data)) {
	    		$extension_file_incs[] = $data;
	    	}

	    }
	}

}

foreach ($extension_xml_files as $f) {

	$handle = fopen($f, 'r');

	if ($handle) {

	    while (($line = fgets($handle)) !== false) {

	    	$data = initFileLineSearcher(
	    		array('.phtml', 
	    			  '.js',
	    			  '.css',
	    			  '.php'
	    		), $line
	    	);

	    	if (isset($data) && !empty($data)) {
	    		$extension_file_incs[] = $data;
	    	}

	    }
	}

}

function normalizeFileIncs($file_incs) {

	$data = array();

	foreach ($file_incs as $fp) {
		foreach ($fp as $file_type => $f) {
			foreach ($f as $d) {
				$data[$file_type][] = $d;
			}
		}
	}

	return $data;

}

$extension_file_incs = normalizeFileIncs($extension_file_incs);



//  files found in the php files of the extension base dir
foreach ($extension_file_incs as $file_type => $fp) {

	foreach ($fp as $f) {

		foreach ($layout_roots as $root_folder) {

			$fp = $magento_dir . '/app/design/adminhtml' . $root_folder . 'template/' . $f;

			if (file_exists($fp)) {
				$extension_files[] = $fp;
			}

			$fp = $magento_dir . '/app/design/frontend' . $root_folder . 'template/' . $f;
			if (file_exists($fp)) {
				$extension_files[] = $fp;
			}

		}

	}
	
}

/*
This next code is designed to try and find the minimum base
folder for the design folder. 
@todo: refactor and add more comments
*/

$base_folders = array();

foreach ($extension_files as $f) {
	
	foreach ($layout_roots as $root) {

		if (strpos($f, $root) !== false) {

			$tmp = ltrim($f, $magento_dir);

			$tmp = explode($root, $tmp)[1];

			if (0 === strpos($tmp, 'template/')) {

				$tmp = ltrim($tmp, 'template/');

				if (strpos($tmp, '/') !== false) {

					$pos = strrpos($tmp, '/');
					if ($pos !== false) {
						$tmp = substr($tmp, 0, $pos);

						if (isset($base_folders[$root]['design'])) {

							if (!in_array($tmp, $base_folders[$root]['design'])) {
								$base_folders[$root]['design'][] = $tmp;
							}

						} else {

							$base_folders[$root]['design'][] = $tmp;

						}

						
					}

				}

			} elseif (0 === strpos($tmp, 'layout/')) {

				$tmp = ltrim($tmp, 'layout/');

				if (isset($base_folders[$root]['layout'])) {

					if (!in_array($tmp, $base_folders[$root]['layout'])) {
						$base_folders[$root]['layout'][] = $tmp;
					}

				} else {
					$base_folders[$root]['layout'][] = $tmp;
				}


			} else {

				// does the path contain / indicating we have
				// a folder path
				if (strpos($tmp, '/') !== false) {

					$pos = strrpos($tmp, '/');
					$tmp = substr($tmp, 0, $pos);

					if (isset($base_folders[$root]['skin'])) {

						if (!in_array($tmp, $base_folders[$root]['skin'])) {
							$base_folders[$root]['skin'][] = $tmp;
						}

					} else {
						$base_folders[$root]['skin'][] = $tmp;
					}

				}

			}

		}

	}
}


/*
@todo: refactor and add more comments
*/
$base_design_folders = array();

foreach ($base_folders as $package => $folders) {

	if (isset($folders['design'])) {

		foreach ($folders['design'] as $f) {

			if (substr_count($f, '/') > 1) {
				$tmp = explode('/', $f);
				$base_design_folders[] = $tmp[0] . '/' . $tmp[1];
			} else {
				$base_design_folders[] = $f;
			}

		}


	}

}

/*
@todo: refactor and add more comments
*/
$base_skin_folders = array();

foreach ($base_folders as $package => $folders) {

	if (isset($folders['skin'])) {

		foreach ($folders['skin'] as $f) {

			if (substr_count($f, '/') >= 1) {
				$tmp = explode('/', $f);
				$base_skin_folders[] = $tmp[0];
			} else {
				$base_skin_folders[] = $f;
			}

		}


	}

}

// echo '<pre>';
// print_r($base_folders);
// echo '</pre>';

$base_skin_folders = array_unique($base_skin_folders);

// echo '<pre>';
// print_r($base_skin_folders);
// echo '</pre>';

$base_design_folders = array_unique($base_design_folders);

// echo '<pre>';
// print_r($base_design_folders);
// echo '</pre>';



// loop through base folders, determine if they exist and add them to array
$base_files = array();

foreach ($base_folders as $package => $folders) {

	if (isset($folders['design'])) {

		foreach ($folders['design'] as $f) {

			foreach ($base_design_folders as $base_f) {

				$fp = $magento_dir . '/app/design/frontend' . $package . 'template/' . $base_f;
				if (is_dir($fp)) {
					$base_files[] = $fp;
				}

				$fp = $magento_dir . '/app/design/adminhtml' . $package . 'template/' . $base_f;
				if (is_dir($fp)) {
					$base_files[] = $fp;
				}
				
			} 
			
		}

	}

	if (isset($folders['skin'])) {

		foreach ($folders['skin'] as $f) {

			foreach ($base_skin_folders as $base_s) {

				$fp = $magento_dir . '/skin/frontend' . $package . $base_s;
				if (is_dir($fp)) {
					$base_files[] = $fp;
				}

				$fp = $magento_dir . '/skin/adminhtml' . $package . $base_s;
				if (is_dir($fp)) {
					$base_files[] = $fp;
				}

			} 
			
		}
	
	}

}


foreach ($extension_file_incs as $file_type => $fp) {

	foreach ($fp as $f) {

		foreach ($base_folders as $package => $folders) {

			foreach ($base_skin_folders as $base_s) {

				if (stripos(strrev($f), 'lmthp.') === 0) {
					// skip phtml files
					continue;
				}

				$fp = $magento_dir . '/skin/frontend' . $package . $base_s . $f;

				if (file_exists($fp)) {
					$extension_files[] = $fp;
				}

				$fp = $magento_dir . '/skin/adminhtml' . $package . $base_s . $f;
				if (file_exists($fp)) {
					$extension_files[] = $fp;
				}

			}

		}
	}
}



// remove dups
$base_files = array_unique($base_files);

$extension_files = array_unique($extension_files);

/*
loop through all the extension files and compare them against
the base paths to folders containing the files.

this is a way to clean up the extension file list so we 
dont have to include every file found in the extension and
can instead just use the path to the folder

@todo: refactor
*/
foreach ($extension_files as $f) {

	$found = false;

	foreach ($base_files as $b) {

		if (strpos($f, $b) !== false) {
			$found = true;
	    	break;
		}

	}

	if ($found) {
		continue;
	}

	$base_files[] = $f;

}

// reset indexes
$base_files = array_values($base_files);


// output results
foreach ($base_files as $f) {
	echo $f . PHP_EOL;
}

