<?php

/**
 * Library - A fresh modren look of MediaWiki with Bootstrap framework supported.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @ingroup Skins
 */

namespace Library;

use FatalError;

/**
 * A namespace for Library constants for internal Library usage only. **Do not rely on this file as an
 * API as it may change without warning at any time.**
 * @package Library
 * @internal
 */
final class Constants
{
	/**
	 * @var string
	 */
	public const SKIN_NAME = 'library';
<<<<<<< HEAD
	/**
	 * custom navigation menu for linking to another system / website
	 * @var array
	 */
	public const CONFIG_ARRAY_CUST_NAVIGATION = [
		'main' => [
			'home' => ['text' => 'Home', 'href' => '/'],
			'family' => ['text' => 'Family', 'href' => '/category/family/', 'perm' => 1],
			'life' => ['text' => 'Life', 'href' => '/category/life/', 'perm' => 1],
			'travel' => ['text' => 'Travel', 'href' => '/category/travel/', 'perm' => 1],
			'library' => ['text' => 'Library', 'href' => '/w/', 'perm' => 1],
			'scholar' => ['text' => 'Scholar', 'href' => '/scholar/', 'perm' => 1]
=======
	public const SKIN_CUST_NAVIGATION = [
		'backhome' => [
			'home' => [ 'text' => 'Home', 'href' => '/' ],
			'members' => [ 'text' => 'Members', 'href' => '/members/', 'perm' => 1 ],
			'groups' => [ 'text' => 'Groups', 'href' => '/groups/', 'perm' => 1 ],
			'blog' => [ 'text' => 'Blog', 'href' => '/category/family/', 'perm' => 1 ],
			'library' => [ 'text' => 'Library', 'href' => '/w/', 'perm' => 1 ]
>>>>>>> c62c1b9a5b5885dd070290d85307d9b0067b9f6e
		],
		'shortcut' => [
			'home' => ['text' => 'Home', 'href' => '/wiki/Main_Page'],
			'explore' => ['text' => 'Explore', 'href' => '/wiki/Special:Random', 'perm' => 1],
			'history' => ['text' => 'History', 'href' => '/wiki/Special:RecentChanges', 'perm' => 1],
			'bookmark' => ['text' => 'Bookmark', 'href' => '/wiki/Special:Watchlist', 'perm' => 1]
		],
		'bottom' => [
			'preference' => ['text' => 'Preference', 'href' => '/wiki/Special:Preferences', 'perm' => 1],
			'help' => ['text' => 'Help', 'href' => '/wiki/Help:Contents']
		]
	];
<<<<<<< HEAD
=======

>>>>>>> c62c1b9a5b5885dd070290d85307d9b0067b9f6e
	/**
	 * return custom navigation menu based on permission level [0, 1]
	 * @param	int 	$perm	permission level
	 * @return 	array 			navigation menu
	 */
<<<<<<< HEAD
	public static function getCustomNavigationData(int $perm  = 0)
	{
		$data = SELF::CONFIG_ARRAY_CUST_NAVIGATION;
=======
	public static function getCustomMenuData( int $perm  = 0) {
		$data = SELF::SKIN_CUST_NAVIGATION;
>>>>>>> c62c1b9a5b5885dd070290d85307d9b0067b9f6e
		foreach ($data as $parentKey => $parentItem) {
			if (is_array($parentItem)) {
				foreach ($data[$parentKey] as $childKey => $childItem) {
					if ((isset($childItem['perm'])) && ($childItem['perm'] > $perm)) {
						unset($data[$parentKey][$childKey]);
					}
					unset($data[$parentKey][$childKey]['perm']);
				}
			}
		}
		return $data;
	}

	/**
	 * This class is for namespacing constants only. Forbid construction.
	 * @throws FatalError
	 */
	private function __construct()
	{
		throw new FatalError("Cannot construct a utility class.");
	}

	/**
	 * Logs message/variables to browser console within PHP
	 * 	@param string	$msg: 	message to be whown for optional data/vars.
	 *  @param mixed	$data (scalar/mixed) arrays/objects, etc. to be output.
	 *  @param boolean	$jsEval		whether to apply JavaScript eval() to objects
	 * 
	 * @return	none
	 * 
	 */
	public function outputConsoleBeforeHTML($msg, $data = NULL, $jsEval = FALSE)
	{
		if (!$msg) return false;
		$isevaled = false;
		$type = ($data || gettype($data)) ? 'Type: ' . gettype($data) : '';

		if ($jsEval && (is_array($data) || is_object($data))) {
			$data = 'eval(' . preg_replace('#[\s\r\n\t\0\x0B]+#', '', json_encode($data)) . ')';
			$isevaled = true;
		} else {
			$data = json_encode($data);
		}

		# sanitalize
		$data = $data ? $data : '';
		$search_array = array("#'#", '#""#', "#''#", "#\n#", "#\r\n#");
		$replace_array = array('"', '', '', '\\n', '\\n');
		$data = preg_replace($search_array,  $replace_array, $data);
		$data = ltrim(rtrim($data, '"'), '"');
<<<<<<< HEAD
		$data = ($isevaled ? $data : ($data[0] === "'")) ? $data : "'" . $data . "'";
=======
		$data = ( $isevaled ? $data : ($data[0] === "'") ) ? $data : "'" . $data . "'";
>>>>>>> c62c1b9a5b5885dd070290d85307d9b0067b9f6e

		$out = <<<HEREDOC
\n<script>
console.log('$msg');
console.log('------------------------------------------');
console.log('$type');
console.log($data);
console.log('\\n');
</script>
HEREDOC;
<<<<<<< HEAD
		echo $out;
=======
			echo $out;
>>>>>>> c62c1b9a5b5885dd070290d85307d9b0067b9f6e
	}
}
