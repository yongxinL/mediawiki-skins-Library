<?php
/**
 * Library - Modern version of MonoBook with fresh look and Bootstrap framework supported
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
 * @file
 * @ingroup Skins
 */

use MediaWiki\MediaWikiServices;
use Library\Constants;

/**
 * Skin subclass for Library
 * @ingroup Skins
 * @final skins extending SkinLibrary are not supported
 * @unstable
 */
class SkinLibrary extends SkinTemplate {
	public $skinname = Constants::SKIN_NAME;
	public $stylename = 'Library';
	public $template = 'LibraryTemplate';

	private $responsiveMode = false;

	/**
	 * Enables the responsive mode
	 */
	public function enableResponsiveMode() {
		if ( !$this->responsiveMode ) {
			$out = $this->getOutput();
			$out->addMeta( 'viewport', 'width=device-width, initial-scale=1' );
			$out->addModuleStyles( 'skins.library.styles.responsive' );
			$this->responsiveMode = true;
		}
	}

	/**
	 * Initializes output page and sets up skin-specific parameters
	 * @param OutputPage $out Object to initialize
	 */
	public function initPage( OutputPage $out ) {
		parent::initPage( $out );

		// Load metadata
		$out->addMeta( 'description', 'theLijia Library');
		$out->addMeta( 'author', 'Lijia-YongxinL');
		$out->addMeta( 'HandheldFriendly', 'true' );
		$out->addMeta( 'apple-mobile-web-app-capable', 'YES' );

		// Load custom/vendor styles
		$out->addModuleStyles( 'skins.library.vendor.styles' );

		// Load custom/vendor modules
		$out->addModules( 'skins.library.vendor.js' );

	}

	/**
	 * Called by OutputPage::headElement when it is creating the
	 * `<body>` tag. Overrides method in Skin class.
	 * @param OutputPage $out
	 * @param array &$bodyAttrs
	 */
	public function addToBodyAttributes( $out, &$bodyAttrs ) {

		// Load custom styles into body tag
		$bodyAttrs['class'] .= ' mw-body mw-light';
	}

	/**
	 * @inheritDoc
	 * @return array
	 */
	public function getDefaultModules() {
		$modules = parent::getDefaultModules();

		$modules['styles'] = array_merge(
			$modules['styles'],
			[ 'skins.library.styles', 'mediawiki.ui.icon' ]
		);
		$modules[Constants::SKIN_NAME][] = 'skins.library.js';

		return $modules;
	}

	/**
	 * Set up the LibraryTemplate. Overrides the default behaviour of SkinTemplate allowing
	 * the safe calling of constructor with additional arguments. If dropping this method
	 * please ensure that LibraryTemplate constructor arguments match those in SkinTemplate.
	 *
	 * @internal
	 * @param string $classname
	 * @return LibraryTemplate
	 */
	protected function setupTemplate( $classname ) {
		$tp = new TemplateParser( __DIR__ . '/templates' );
		return new LibraryTemplate( $this->getConfig(), $tp, $this->isLegacy() );
	}

	/**
	 * Whether the logo should be preloaded with an HTTP link header or not
	 * @since 1.29
	 * @return bool
	 */
	public function shouldPreloadLogo() {
		return true;
	}

	/**
	 * Whether or not the legacy version of the skin is being used.
	 *
	 * @return bool
	 */
	private function isLegacy() : bool {
		$isLatestSkinFeatureEnabled = MediaWikiServices::getInstance()
			->getService( Constants::SERVICE_FEATURE_MANAGER )
			->isFeatureEnabled( Constants::FEATURE_LATEST_SKIN );

		return !$isLatestSkinFeatureEnabled;
	}
}
