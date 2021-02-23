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
final class Constants {
	/**
	 * This is tightly coupled to the ConfigRegistry field in skin.json.
	 * @var string
	 */
	public const SKIN_NAME = 'library';

	public const SKIN_CUSTOM_SIDENAV = array(
		'sidenav' => [
			'0' => [ 'text' => 'Home', 'href' => '/' ],
			'1' => [ 'text' => 'Login', 'href' => '/wp-login.php', 'logged' => false ],
			'2' => [ 'text' => 'Family', 'href' => '/category/family/', 'logged' => true ],
			'3' => [ 'text' => 'Life', 'href' => '/category/life/' ],
			'4' => [ 'text' => 'Travel', 'href' => '/category/travel/' ],
			'5' => [ 'text' => 'Library', 'href' => '/wiki/Main_page' ],
			'6' => [ 'text' => 'Log out', 'href' => '/wp-login.php?logout', 'logged' => true ]
		]
		);

	public const MENU_TYPE_DEFAULT = 0;
	public const MENU_TYPE_BASENAV = 1;
	public const MENU_TYPE_TABS = 2;
	public const MENU_TYPE_DROPDOWN = 3;
	public const MENU_TYPE_PORTAL = 4;

	public function getCustSideNav( bool $logged = true ) {
		$data = SELF::SKIN_CUSTOM_SIDENAV;
		foreach ( $data['sidenav'] as $name => $content ) {
			if ( $content['logged'] != $logged ) {
				unset( $data['sidenav'][$name] );
			}
		}
		return $data;
	}

	/**
	 * This class is for namespacing constants only. Forbid construction.
	 * @throws FatalError
	 */
	private function __construct() {
		throw new FatalError( "Cannot construct a utility class." );
	}
}