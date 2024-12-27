<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2014 Robert Heim; this file 2024 S. McCandlish, derived from https://area51.phpbb.com/docs/dev/3.3.x/extensions/tutorial_advanced.html#using-installation-commands-in-ext-php
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace robertheim\topictags;

class ext extends \phpbb\extension\base
{
    public function is_enableable()
    {
        $config = $this->container->get('config');
        return phpbb_version_compare($config['version'], '3.3.0', '>=');
		// This will automatically enforce our needed PHP_VERSION >= 7.2.0, because phpBB 3.3.x itself requires that.
    }
}