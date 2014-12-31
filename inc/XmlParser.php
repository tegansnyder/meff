<?php
/**
 * XmlParser.php
 *
 * @author    Tegan Snyder <tsnyder@tegdesign.com>
 * @license   MIT
 */
class XmlParser extends ExtensionXml
{

	public static $root_nodes = array(
		'global', 
		'frontend', 
		'adminhtml'
	);

	public static $sub_nodes = array(
		'layout', 
		'translate', 
		'template'
	);

	public $likely_match = array();

	/**
	 * Retrieves node specific data
	 *
	 * @param string $xml (path to extensions config.xml file)
	 * @param array $root_nodes (global, frontend, adminhtml)
	 * @param array $node_type (layout, translate, template)
	 * @return array
	 */
	function getXmlNode($root_node, $node_type) 
	{

		$this->debugOut('getXmlNode', 
			':root_node = ' . $root_node .
			' :node_type = ' . $node_type,
			'FUNCTION');
		

		$this->debugOut(
			'Parsing', 
			parent::$extension_base_dir . '/etc/config.xml', 
			'getXmlNode'
		);

		$xml = parent::$extension_config_xml;

		$xml_data = array();

		if (isset($xml->$root_node)) {

			$this->debugOut('(true) - isset(root_node)', $root_node, 'FUNCTION');

			$node = $xml->$root_node;

			if ($node_type == 'layout') {

				$xml_data = $this->getLayoutFiles($node);

			} elseif ($node_type == 'translate') {

				$xml_data = $this->getTranslateFiles($node);

			}  elseif ($node_type == 'template') {

				$xml_data = $this->getEmailTemplates($node);

			}
			
		} else {
			$this->debugOut('(false) - isset(root_node)', $root_node, 'FUNCTION');
		}

		return $xml_data;

	}

	/**
	 * Loop through the 
	 *
	 * @param string $xml (path to extensions config.xml file)
	 * @return array
	 */
	function getFilesFromXml() 
	{

		$this->debugOut('getFilesFromXml', null ,'FUNCTION');
		
		$data = array();
		$xml = parent::$extension_config_xml;

		foreach (self::$sub_nodes as $node) {

			$updates = array();

			foreach (self::$root_nodes as $root_node) {

				$updates[$root_node] = $this->getXmlNode($root_node, $node);
			}

			$data[$node] = $updates;

		}

		return $data;

	}

	/**
	 * Get that Magento layout xml files
	 *
	 * @param string $main_node (global, frontend, adminhtml)
	 * @return array
	 */
	function getLayoutFiles($main_node) 
	{

		$this->debugOut('getLayoutFiles', null ,'FUNCTION');

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
	function getTranslateFiles($main_node) 
	{

		$this->debugOut('getTranslateFiles', null ,'FUNCTION');

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
	function getEmailTemplates($main_node) 
	{

		$this->debugOut('getEmailTemplates', null ,'FUNCTION');

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
	 * Provides a 3 dimensional array listing of all xml updates
	 * found in the extensions config xml. (layout, translate, template)
	 *
	 * @return array
	 */
	function getXmlUpdates() 
	{

		$this->debugOut('getXmlUpdates', null ,'FUNCTION');
		
		// this array stores any xml layout update files found
		$xml_updates = array();
		$xml_updates = $this->getFilesFromXml();

		return $xml_updates;
	}

	/**
	 * Return the same list as the getXmlUpdates function above
	 * but in a 1 dimensional listing without additional the 
	 * data provided by the getXmlUpdates function.
	 *
	 * @return array
	 */
	public function getXmlUpdatesInListFormat() {

		$data = array();

		$xml_updates = $this->getXmlUpdates();

		foreach ($xml_updates as $update_type => $definition) {
			foreach ($definition as $file_paths) {
				foreach ($file_paths as $path) {
					$data[] = $path;
				}
			}
		}

		return $data;

	}


	/**
	 * Get the full paths to layout xml files used by the Extension
	 *
	 * @return array
	 */
	function getLayoutXmlFilesFromXml() 
	{
		$this->debugOut('getLayoutXmlFilesFromXml', null, 'FUNCTION');

		$xml = $this->getXmlUpdates();

		$layout_xml_files = array();
		$file_iterator = new FileIterator();

		/*
		loop through app/design folders

			app/design
			 - adminhtml
			  	- any number of directories
			 - frontend
			  	- any number of directories

		*/
		foreach ($xml['layout'] as $layout_node => $xml_files) {

			/*
				skip global namespace for now. 
				im not sure you should/can define xml updates there
			*/

			if ($layout_node == 'global') {
				continue;
			}

			if ($layout_node == 'frontend') {
				$xml_dir = 'app/design/frontend';
			} else if ($layout_node == 'adminhtml') {
				$xml_dir = 'app/design/adminhtml';
			}

			$this->debugOut('getLayoutXmlFilesFromXml', 
				'layout_node: ' . $layout_node . ' ' .
				'xml_dir: ' . $xml_dir, 'FUNCTION'
			);

			foreach ($xml_files as $xml_file) {

				$this->debugOut('Parsing', self::$magento_dir . '/' . $xml_dir, 
					'getLayoutXmlFilesFromXml'
				);

				/*
					the iterator regex is setup just to look for the filename
					without the directory name included. we can ensure we
					get the right file by passing the full folder as the 
					folder match paramater to the iterator.
				*/

				$folder_match = null;
				if (strpos($xml_file,'/') !== false) {
					$folder_match = $xml_file;
					$xml_file = substr($xml_file, strrpos($xml_file, '/') + 1);
				}

				$this->debugOut('Looking for', $xml_file, 'getLayoutXmlFilesFromXml');

				/*
					iterate through Magento's layout folders looking for
					a xml file named the same name and return the full path
					to that file.
				*/

				$layout_xml_files[$layout_node] = $file_iterator->iterateFileSystem(
					array('#^'.$xml_file.'$#'),
					self::$magento_dir . '/' . $xml_dir, 
					true, 
					$folder_match
				);

			}

		}

		return $layout_xml_files;

	}


	/**
	 * Get the folders possible for layout, design, skin and file locations
	 *
	 * @return array
	 */
	function getFileRoots() 
	{

		$this->debugOut('getFileRoots', null ,'FUNCTION');

		// stock folder locations
		$search_paths = array(
			'app/design',
			'skin',
		);

		// loop through each main folder type (frontend, adminhtml)
		foreach (self::$root_nodes as $node) {

			// we can skip global as its not a real folder
			if ($node == 'global') {
				continue;
			}

			foreach ($search_paths as $search_path) {

				$directory = self::$magento_dir . '/' . $search_path . '/' . $node . '/';

				$this->debugOut('Searching in', $directory ,'getFileRoots');

				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator(
						$directory, 
						RecursiveDirectoryIterator::SKIP_DOTS
					),
					RecursiveIteratorIterator::SELF_FIRST
				);

				foreach ($iterator as $item) {

					if ($item->isDir()) {
				    
				    	// get relative directory with full path
				    	$tmp = str_replace($directory, '', $item->getPath());

				    	// explode on directory seperator
				    	$tmp = explode('/', $tmp);

				    	// store the first two folders as a potential search location
				    	if (isset($tmp[1]) && !empty($tmp[0])) {
				    		$data[] = $tmp[0] . '/' . $tmp[1];
				    	}

					}
				}


			}

		}

		$data = array_unique($data);
		// reset the indexes
		$data = array_values($data);

		return $data;

	}

	/**
	 * Get the full path to files reference in the extensions layout xml
	 *
	 * @return array
	 */
	function getFilesPathsFromLayoutXml() 
	{

		$this->debugOut('getFilesPathsFromLayoutXml', null ,'FUNCTION');

		$data = array();
		$layout_xml_files = $this->getLayoutXmlFilesFromXml();

		foreach ($layout_xml_files as $design_folder => $xml_files) {
			foreach ($xml_files as $xml_file) {
				$data[] = $xml_file;
			}
		}

		return $data;

	}

	/**
	 * Get the full path + design folder to files reference in the extensions layout xml
	 *
	 * @return array
	 */
	function getFilesFromLayoutXml() 
	{

		$this->debugOut('getFilesFromLayoutXml', null ,'FUNCTION');

		$data = array();
		$layout_xml_files = $this->getLayoutXmlFilesFromXml();

		/*
			Next lets loop through the layout xml files for the extension.
			Lets try and locate the full path to any action addItem included
			files
		*/
		foreach ($layout_xml_files as $design_folder => $xml_files) {

			foreach ($xml_files as $xml_file) {

				$file_path = $xml_file;

				$this->debugOut('Looking for', $file_path, 'getFilesFromLayoutXml');

				if (file_exists($file_path)) {
			
					$xml = simplexml_load_file($file_path);

					$result = $xml->xpath('//action[@method="addJs"]');

					foreach ($result as $node) {

						if (isset($node->file)) {
							$data[$design_folder]['js'][] = (string)$node->file;
						}

						if (isset($node->script)) {
							$data[$design_folder]['js'][] = (string)$node->script;
						}

						if (isset($node->name)) {
							$data[$design_folder]['js'][] = (string)$node->name;
						}

						/*
							edge case here
							magento allows you to use any 
							node convention you want. most people use
							<script>filename.js</script>
							or <file>filename.js</file>
							but you could really use <anything>filename.js</anything>
						*/

					}

					$result = $xml->xpath('//action[@method="addCss"]');

					foreach ($result as $node) {

						if (isset($node->stylesheet)) {
							$data[$design_folder]['skin_css'][] = (string)$node->stylesheet;
						}

						if (isset($node->name)) {
							$data[$design_folder]['skin_css'][] = (string)$node->name;
						}

					}

					$result = $xml->xpath('//action[@method="addItem"]');

					foreach ($result as $node) {

						if (isset($node->type)) {
							if (isset($node->name)) {
								$data[$design_folder][(string)$node->type][] = (string)$node->name;
							}
							if (isset($node->script)) {
								$data[$design_folder][(string)$node->type][] = (string)$node->script;
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

							$data[$design_folder]['helper'][] = (string)$attributes->helper;

						}

					}


					// loop through blocks
					$result = $xml->xpath('//block[not(node())]');

					foreach ($result as $node) {

						$attributes = $node->attributes();

						if (isset($attributes->type)) {

							if (isset($attributes->template)) {

								//(string)$attributes->type
								$data[$design_folder]['blocks'][] = (string)$attributes->template;

							}

						}

					}

				}

				

			}

		}

		return $data;


	}

	/**
	 * Parse a list of files and read each line looking
	 * for mentions of files
	 *
	 * @param array $file_list
	 * @param array $extensions
	 * @return array
	 */
	public function findFileMentions($file_list, $extensions) 
	{

		$this->debugOut('findFileMentions', null ,'FUNCTION', 0);

		$data = array();

		foreach ($file_list as $f) {

			$handle = fopen($f, 'r');

			if ($handle) {

			    while (($line = fgets($handle)) !== false) {

			    	$potential_filename_match = $this->parseFilenameFromLine(
			    		$extensions, 
			    		$line
			    	);

			    	if (isset($potential_filename_match) &&
			    		!empty($potential_filename_match)
			    	) {
			    		$data[] = $potential_filename_match;
			    	}

			    }
			}

		}

		// normalize the array
		$new_data = array();
		foreach ($data as $d) {
			foreach ($d as $t) {
				$new_data[] = $t;
			}
		}

		$data = $new_data;

		return $data;

	}

	/**
	 * Parse source code looking for new class declarations
	 * that do not match a set of reserved class declartions
	 * to attempt to try to to find files that are in
	 * Magento's /lib directory
	 *
	 * @param string $line
	 * @return string
	 */
	public function parseSourceForLibs($file_list) 
	{

		$data = array();

		$this->debugOut('parseSourceForLibs', null ,'FUNCTION', 0);

		foreach ($file_list as $f) {

			$handle = fopen($f, 'r');

			if ($handle) {

			    while (($line = fgets($handle)) !== false) {

			    	echo $line . PHP_EOL;

					$tmp = $this->get_string_between($line, 'new ', ');');

			       	// do we have something and is the first character 
			       	// capitalized like a class instance
			       	if (!empty($tmp) && preg_match('/[A-Z]/', $tmp[0])) {

			       		$tmp = 'new ' . $tmp;

			       		// what reserved classes we looking to skip
						if (0 !== strpos($tmp, 'new Mage') &&
							0 !== strpos($tmp, 'new Enterprise') &&
							0 !== strpos($tmp, 'new Exception') &&
							0 !== strpos($tmp, 'new Zend')  &&
							0 !== strpos($tmp, 'new Varien')
						) {

							$tmp = $this->get_string_between($tmp, 'new ', '(');
							if (!empty($tmp)) {
			       				$data[] = $tmp;
			       			}
						}

			       	}
			    }
			}

		}

		$this->debugOut('Potential Libs', 
						print_r($data, true), 
						'parseSourceForLibs'
						);

		$lib_classes = array();
		foreach ($data as $c) {

			// skip if a space is found
			if (preg_match('/\s/',$c)) {
				continue;
		 	}

			if (strpos($c,'_') !== false) {

				// explode out on _ to find folder names
				$tmp = explode('_', $c);

				$look_path = self::$magento_dir . '/lib';

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
		$data = array_values($lib_classes);

       	return $data;

	}

	/**
	 * Extract a filename from a line of code. This function trys it's best
	 * to extract a filename from a line of code. It assumes that filenames
	 * are listed in code by enclosing them in quotes. This is probably not
	 * the most acurate way to search for filenames in code and may be
	 * revisted in the future
	 *
	 * @param array $ext (file extensions)
	 * @param string $line
	 * @return string
	 */
	public function parseFilenameFromLine($exts, $line) 
	{

		$this->debugOut('parseFilenameFromLine', null ,'FUNCTION', 0);
		
		$data = array();

		foreach ($exts as $e) {

		   	if (strpos($line, $e) !== FALSE) {
		   		
				$tmp = explode($e, $line);

				foreach ($tmp as $t) {
					$t = str_replace('"', "'", $t);
					$t = str_replace(';', "", $t);
					$t = str_replace(')', "", $t);
					$t = str_replace('(', "", $t);
					$t = array_pop(explode("'", $t)) . $e;
					$t = trim($t);

					if (empty($t)) {
						continue;
					}
	
					if ($t != $e) {

						// make sure the filename doesn't contain a space
						if (strpos($t, ' ') === FALSE) {

							// make sure the filename does contains ending tag
							if (strpos($t, '/>') === FALSE) {
								$data[] = $t;
							}
						}
					}
				}
		   	}

		}

		return $data;

	}


	/**
	 * lets figure out if any of the mentions in xml_updates contain a / indicating
	 * a subfolder. if we find a subfolder lets grab it into a variable we can use
	 * for search paths later. its very likely that an extension developer will
	 * include this folder as part of other resources that the extension uses
	 *
	 * @return array
	 */
	public function getLikelySubFolderSearchPaths() {

		$this->debugOut('getLikelySubFolderSearchPaths', null, 'FUNCTION');

		$data = array();

		$file_paths = $this->getXmlUpdatesInListFormat();

		$y = 0;

		foreach ($file_paths as $path) {

			$x = 0;

			// split the file path on / to find all the folders
			$tmp = explode('/', $path);

			$cnt = count($tmp) - 1;

			if ($cnt >= 1) {

				// remove last element in array if it is a filename
				if (strpos($tmp[$cnt], '.') !== FALSE) {
					array_pop($tmp);
					$cnt = $cnt - 1;
				}

				// the reason for code is to create a potential list of all folders
				// that could be searchable. that is why we are building a path
				// as we go. this way we have a full array of search locations
				foreach ($tmp as $t) {
					if (isset($data[$y][$x-1])) {
						$data[$y][$x] = $data[$y][$x-1] . '/' . $t;
					} else {
						$data[$y][$x] = $t;
					}
					$x = $x + 1;
				}

			}

			$y = $y + 1;

		}

		// normalize in a nice list
		$new_data = array();
		foreach ($data as $tmp) {
			foreach ($tmp as $d) {
				$new_data[] = $d;
			}
		}
		$data = $new_data;
		$data = array_unique($data);
		$data = array_values($data);

		return $data;

	}


	/**
	 * Loop through a list of files found in the extensions 
	 * config.xml and determine if they can be found in 
	 * common search paths and scopes identified 
	 *
	 * @return array
	 */
	public function identifyConfigXmlFilePaths() 
	{

		$this->debugOut('identifyFilePaths', null, 'FUNCTION');

		$data = array();

		$xml_updates = $this->getXmlUpdates();

		foreach ($xml_updates as $node_type => $scope) {

			/*
			node_type = layout, translate, template
			scope = global, frontend, adminhtml
			*/

			foreach ($scope as $scope_data) {
				foreach ($scope_data as $file_path) {
					$data[] = $this->identifyFileExistance($file_path, $node_type);
				}
			}
		}

		return $data;

	}

	/**
	 * Given a file path and node_type perform basic file
	 * iterator tasks and check for file existance in
	 * common search paths and scopes identified 
	 *
	 * @param string $file_path
	 * @param string $search_type
	 * @return array
	 */
	public function identifyFileExistance($file_path, $search_type)
	{

		$data = array();

		$this->debugOut('identifyFileExistance', 
			':file_path = ' . $file_path .
			' :search_type = ' . $search_type,
			'FUNCTION', 0);

		// instantiate some resuable vars
		$file_iterator = new FileIterator();
		$file_roots = $this->getFileRoots();
		$likely_search_subfolder_paths = $this->getLikelySubFolderSearchPaths();

		switch ($search_type) {

			case 'layout':

				/*
				in this switch-case we are looking any design related 
				files (usually xml files) declared in layout nodes
				of an extensions config.xml
				*/

				foreach ($file_roots as $file_root) {

					/*
					loop through all base/default default/default and 
					any other folder base identified by the getFileRoots function
					*/

					foreach (self::$root_nodes as $design_node) {

						if ($design_node == 'global') {
							// we can skip global as it is not
							// really a folder
							continue;
						}

						$full_path = self::$magento_dir . 
									'/app/design/' . $design_node . '/' . 
									$file_root . '/' . 
									'layout' . '/' .
									$file_path;

						$this->debugOut(
							'does file_exist', 
							$full_path, 
							'identifyFileExistance',
							0
						);

						if (file_exists($full_path)) {
							$data[] = $full_path;
						}

					}

				}

			break;

			case 'translate':
			case 'template':

				/*
				in this switch-case we are looking for email templates 
				defined in the config.xml and we are also looking for 
				translate csv files defined in the config.xml of
				the extension.
				*/

				$search_directory = self::$magento_dir . '/app/locale/';

				/*
					the iterator regex is setup just to look for the filename
					without the directory name included. we can ensure we
					get the right file by passing the full folder as the 
					folder match paramater to the iterator.
				*/

				$folder_match = null;
				if (strpos($file_path,'/') !== false) {
					$folder_match = $file_path;
					$file_path = substr($file_path, strrpos($file_path, '/') + 1);
				}

				$this->debugOut(
					'iterating', 
					'file_path: ' . $file_path . ' ' .
					'folder_match: ' . $folder_match, 
					'identifyFileExistance',
					0
				);

				$tmp = array();
				$tmp = $file_iterator->iterateFileSystem(
					array('#^'.$file_path.'$#'),
					$search_directory, 
					true, 
					$folder_match
				);

				if (!empty($tmp)) {

					// normalize array
					foreach ($tmp as $t) {
						$data[] = $t;
					}
					
				}

			break;

			case 'skin_css':

				/*
				lets try and find any css associated with the extension
				that has been defined in a skin css node in the extension's layout xml
				*/

				foreach ($file_roots as $file_root) {

					/*
					loop through the root folders in the skin folder
					*/

					foreach (self::$root_nodes as $design_node) {

						if ($design_node == 'global') {
							// we can skip global as it is not
							// really a folder
							continue;
						}

					}

				}

			break;

			case 'helper':

				/*

				@todo:

				An extension can define a dynamic path to a file using a helper.

				Example:

				<action method="addItem">
        			<type>skin_css</type>
                	<name helper="tegdesign_emailcollector/data/getCSSFilename" />
        		</action>

        		we will need to instantiate a Mage instance and call the
        		helper function to determine the filename

				*/

			break;

			case 'blocks':

				/*
				here we are searching the block templates declared in
				the extensions layout xml files looking for the full path
				to the files
				*/

				foreach ($file_roots as $file_root) {

					/*
					loop through all base/default default/default and 
					any other folder base identified by the getFileRoots function
					*/

					foreach (self::$root_nodes as $design_node) {

						if ($design_node == 'global') {
							// we can skip global as it is not
							// really a folder
							continue;
						}

						foreach ($likely_search_subfolder_paths as $likely_folder) {

							// if the file path doesn't already contain the likely folder
							if (strpos($file_path, $likely_folder) === FALSE) {

								$full_path = self::$magento_dir . 
											'/app/design/' . $design_node . '/' . 
											$file_root . '/' . 
											'template' . '/' .
											$likely_folder . '/' . 
											$file_path;

							} else {

								$full_path = self::$magento_dir . 
										'/app/design/' . $design_node . '/' . 
										$file_root . '/' . 
										'template' . '/' .
										$file_path;

							}

							$this->debugOut(
								'does file_exist', 
								$full_path, 
								'identifyFileExistance',
								0
							);

							if (file_exists($full_path)) {

								$this->debugOut(
									'likely_match', 
									$full_path, 
									'identifyFileExistance',
									0
								);

								// we found a good match
								$this->likely_match[$design_node][$file_path] = 1;

								$data[] = $full_path;

							}

						}

					}

					/*
					We attempted to find files in a list of likely subfolders
					that most developers would have put the files in so lets
					do a fuzzy wide scan of the entire template folder.
					note: this could yeild false positives as other extensions
					could name file names the same
					*/

					foreach (self::$root_nodes as $design_node) {

						if ($design_node == 'global') {
							// we can skip global as it is not
							// really a folder
							continue;
						}

						if (
							empty($likely_search_subfolder_paths) || 
							isset(
								$this->likely_match[$design_node][$file_path]) && 
								!$this->likely_match[$design_node][$file_path]
							) {

							$search_directory = self::$magento_dir . 
										'/app/design/' . $design_node . '/' . 
										$file_root . '/' . 
										'template' . '/';

							if (!file_exists($search_directory)) {
								// if search directory doesn't exist lets skip this
								continue;
							}

							$this->debugOut(
								'fuzzy scan dir', 
								$search_directory, 
								'identifyFileExistance',
								0
							);

							/*
								the iterator regex is setup just to look for the filename
								without the directory name included. we can ensure we
								get the right file by passing the full folder as the 
								folder match paramater to the iterator.
							*/

							$folder_match = null;
							if (strpos($file_path,'/') !== false) {
								$folder_match = $file_path;
								$file_path = substr($file_path, strrpos($file_path, '/') + 1);
							}

							$tmp = array();
							$tmp = $file_iterator->iterateFileSystem(
								array('#^'.$file_path.'$#'),
								$search_directory, 
								true, 
								$folder_match
							);


							// reset file_path
							if (isset($folder_match)) {
								$file_path = $folder_match;
							}

							if (!empty($tmp)) {

								// normalize array
								foreach ($tmp as $t) {
									$data[] = $t;
								}
								
							}

						}

					}

				}

			break;

			/*
			Lets attempt to locate files mentioned in the source code.
			This is experimental and may be revisted at a later date.
			I welcome ideas as PRs
			*/
			case 'file_mentions':

// DEBUG: file_mentions_php - Array
// (
//     [0] => dashboard/newrelic.phtml
//     [1] => newrelic.js
//     [2] => newrelic.js
// )

				$search_paths = array();
				$search_path_templates = array(
					'js',
					'skin/[design_node]/[file_root]',
					'skin/[design_node]/[file_root]/css',
					'skin/[design_node]/[file_root]/images',
					'skin/[design_node]/[file_root]/js',
					'app/design/[design_node]/[file_root]/template',
					'app/design/[design_node]/[file_root]/locale',
					'app/design/[design_node]/[file_root]/layout',
				);

				foreach ($file_roots as $file_root) {

					// setup the search paths
					foreach ($search_path_templates as $search_path_template) {

						$search_paths[] = str_replace(
							'[file_root]', 
							$file_root, 
							$search_path_template
						);

					}

				}

				// temp array to hold design node specific values
				$new_search_paths = array();				
				foreach (self::$root_nodes as $design_node) {

					if ($design_node == 'global') {
						// we can skip global as it is not
						// really a folder
						continue;
					}

					foreach ($search_paths as $search_path) {

						$new_search_paths[] = str_replace(
							'[design_node]', 
							$design_node, 
							$search_path
						);

					}

				}

				// housekeeping put the design node specific
				// values back in original array
				$search_paths = $new_search_paths;
			
				// store the full fuzzy paths
				$full_paths = array();

				foreach ($search_paths as $partial_path) {

					foreach ($likely_search_subfolder_paths as $likely_folder) {

						// if the file path doesn't already contain the likely folder
						if (strpos($file_path, $likely_folder) === FALSE) {

							$full_paths['likely'][] = self::$magento_dir . '/' .
										$partial_path . '/' .
										$likely_folder . '/' . 
										$file_path;
						}

					}

					$full_paths['fuzzy'][] = self::$magento_dir . 
										'/' . $partial_path;

				}

				foreach ($full_paths as $iterator_type => $iterator_paths) {

					foreach ($iterator_paths as $iterator_path) {

						if (!file_exists($iterator_path)) {
							// if search directory doesn't exist lets skip this
							continue;
						}

						// look for exact matches
						$exact_match_path = $iterator_path . '/' . $file_path;

						$this->debugOut(
							'trying', 
							'exact_match_path: ' . $exact_match_path,
							'identifyFileExistance',
							0
						);

						if (file_exists($exact_match_path)) {
							$data[] = $exact_match_path;
							continue;
						}

						$folder_match = null;
						if (strpos($file_path,'/') !== false) {
							$folder_match = $file_path;
							$file_path = substr($file_path, strrpos($file_path, '/') + 1);
						}

						$this->debugOut(
							'iterating', 
							'file_path: ' . $file_path . ' ' .
							'folder_match: ' . $folder_match, 
							'identifyFileExistance',
							0
						);

						$tmp = array();
						$tmp = $file_iterator->iterateFileSystem(
							array('#^'.$file_path.'$#'),
							$iterator_path, 
							true, 
							$folder_match
						);

						// reset file_path
						if (isset($folder_match)) {
							$file_path = $folder_match;
						}

						if (!empty($tmp)) {

							// normalize array
							foreach ($tmp as $t) {
								$data[] = $t;
							}
							
						}

					}

				}

			break;

		}

		return $data;

	}

	/**
	 * Try to identify the full path to files referenced
	 * in the extensions layout xml files
	 *
	 * @return array
	 */
	public function identifyFilesFromLayoutXml() {

		$this->debugOut('identifyFilesFromLayoutXml', null, 'FUNCTION');

		$data = array();

		$layout_xml_files = $this->getFilesFromLayoutXml();

		foreach ($layout_xml_files as $scope => $file_type) {

			/*
			scope = global, frontend, adminhtml
			file_type = skin_css, helper, blocks
			*/

			foreach ($file_type as $search_type => $file_paths) {
				foreach ($file_paths as $file_path) {

					if (!empty($file_path)) {

						$tmp = $this->identifyFileExistance($file_path, $search_type);

						if (!empty($tmp)) {
							$data[] = $tmp;
						}

					}
				}
			}

		}


		return $data;

	}

	/**
	 * Try to identify the full path to files mentioned in the
	 * source code of the extensions files
	 *
	 * @return array
	 */

	public function identifyFileMentionsPaths($file_list) {

		$this->debugOut('identifyFileMentionsPaths', null, 'FUNCTION');

		$data = array();

		foreach ($file_list as $file_path) {

			$tmp = $this->identifyFileExistance($file_path, 'file_mentions');

			if (!empty($tmp)) {
				$data[] = $tmp;
			}

		}

		// normalize
		$new_data = array();
		foreach ($data as $t) {
			foreach ($t as $d) {
				$new_data[] = $d;
			}
		}

		$new_data = array_unique($new_data);
		// reset the indexes
		$data = array_values($new_data);

		return $data;

	}

}