<?php
/**
 * CliColors.php
 *
 * @author    Tegan Snyder <tsnyder@tegdesign.com>
 * @license   MIT
 */

class CliColors {

	private $f_colors = array();
	private $b_colors = array();

	// set this to false to not use colors
	const USE_COLOR = true;

	public function __construct() {

		// Credit to JR: http://goo.gl/BE373

		$this->f_colors['light_gray'] = '0;37';
		$this->f_colors['light_red'] = '1;31';
		$this->f_colors['red'] = '0;31';
		$this->f_colors['cyan'] = '0;36';
		$this->f_colors['light_purple'] = '1;35';
		$this->f_colors['white'] = '1;37';

		$this->b_colors['black'] = '40';

	}

	/**
	 * Make text written to the console look pretty
	 *
	 * @return string
	 */
	public function formatTxt($string, $f_color = null) {

		if (self::USE_COLOR) {

			$color_string = '';

			if (isset($this->f_colors[$f_color])) {
				$color_string .= "\033[" . $this->f_colors[$f_color] . "m";
			}

			// add string and end coloring
			$color_string .=  $string . "\033[0m";

			$string = $color_string;

		}

		return $string;
	}

	/**
	 * Write text to console
	 *
	 * @return string
	 */
	public function write($msg_type = null, $msg = null, $extra_msg = null) {

		$str = '';

		if (isset($msg_type)) {

			$msg_type_color = 'light_purple';
			$msg_color = 'cyan';
			if ($msg_type == 'ERROR') {
				$msg_type_color = 'red';
				$msg_color = 'light_red';
			}

			$str = $this->formatTxt($msg_type . ': ', $msg_type_color);

			if (isset($msg)) {
				$str .= $this->formatTxt($msg, $msg_color);
			}

			if (isset($extra_msg)) {
				$str .= $this->formatTxt(' - ' . $extra_msg, 'light_gray');
			}

		}

		echo $str . PHP_EOL;

	}
}