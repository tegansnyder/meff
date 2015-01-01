<?php
/**
 * FileIterator.php
 *
 * @author    Tegan Snyder <tsnyder@tegdesign.com>
 * @license   MIT
 */
class FileIterator extends ExtensionXml
{
	/**
	 * Recursively looks through directories returning files found
	 *
	 * @return array
	 */
	public function iterateFileSystem(
		$file_types, 
		$search_directory, 
		$file_type_contains_regex = false, 
		$folder_match = null
	) {

		$this->debugOut(
			'iterateFileSystem', 
			':file_types = ' . implode(',', $file_types) . 
			' :search_directory = ' . $search_directory,
			'FUNCTION'
		);

		$data = array();

		foreach ($file_types as $file_type) {

			if ($file_type_contains_regex) {
				$pattern = $file_type;
			} else {
				$pattern = '/^.+\.' . $file_type . '$/i';	
			}

			// setup SPL iterator calls
			// http://php.net/manual/en/book.spl.php

			$directory = new RecursiveDirectoryIterator(
			    $search_directory,
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

				if (isset($folder_match)) {

					// with a folder matching string passed we can
					// ensure that we dont match on similar filenames
					// from other extensions.

					if (strpos($filename, $folder_match) !== FALSE) {
						$data[] = $filename;
					}

				} else {
					$data[] = $filename;
				}
				
			}

		}

		return $data;

	}

}