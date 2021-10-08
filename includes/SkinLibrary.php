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

use Library\Constants;
use Wikimedia\WrappedStringList;

/**
 * Skin subclass for Library
 * @ingroup Skins
 * @final skins extending SkinLibrary are not supported
 * @unstable
 */
class SkinLibrary extends SkinTemplate
{

	private const MENU_TYPE_DEFAULT = 0;
	private const MENU_TYPE_DROPDOWN = 1;
	private const MENU_TYPE_SIDEBAR = 2;

	/**
	 * @var templateParser|null
	 */
	private $templateParser = null;

	/**
	 * Get the template parser, it will be lazily created if not already set.
	 *
	 * @return templateParser
	 */
	protected function getTemplateParser()
	{
		if ($this->templateParser === null) {
			$this->templateParser = new TemplateParser(__DIR__ . '/templates');
		}
		return $this->templateParser;
	}

	/**
	 * rendering data that can be passed to a Mustache template.
	 * @param 	string 	$name	of the portal e.g. p-personal => personal
	 * @param 	array 	$item 	that are accepted input to Skin:makeListItem
	 * @return 	array 			data that can be passed to a Mustache template
	 * 							and presents a single menu.
	 */
	protected function getPortletData(
		$name,
		array 	$items = []
	) : array {
		switch ( $name ) {
			case 'actions':
				$type = self::MENU_TYPE_DROPDOWN;
				break;
			case 'userpage':
				$type = self::MENU_TYPE_DROPDOWN;
				break;
			default:
				$type = self::MENU_TYPE_DEFAULT;
				break;
		}

		if ( count( $items ) > 0 ) {
			$id = Sanitizer::escapeIdForAttribute( "p-$name" );
			$portletData = [
				'id' => $id,
				'class' => Sanitizer::escapeClass( "portlet-$name" ),
				'html-tooltip' => Linker::tooltip($name),
				'html-items' => '',
				'label' => $this->getTranslation( $id ) ?: Sanitizer::escapeIdForAttribute( $name )
			];
			foreach ($items as $key => $item) {
				$portletData['html-items'] .= htmlspecialchars_decode( $this->makeListItem( $key, $this->decoratePortletClass( $item, $type )));
			}
			return $portletData;
		} else {
			return $items;
		}
	}

	/**
	 * helper for applying Library menu classes to portlet.
	 * @param 	array 	$portletData 	array data to decorate.
	 * @param 	int 	$type 			representing one of the menu types.
	 * 									MENU_TYPE_DEFAULT - a list of navigation items (nav-item),
	 * 									MENU_TYPE_DROPDOWN- a list items in dropdown or
	 * 									MENU_TYPE_LINK 	  - a list of navigaton link (nav-link).
	 * @return 	array					modified version of portletData input
	 */
	private function decoratePortletClass(
		array 	$portletData,
		int 	$type = self::MENU_TYPE_DEFAULT
	) {
		$navClasses = [
			self::MENU_TYPE_DEFAULT => 'nav-item',
			self::MENU_TYPE_DROPDOWN => 'dropdown-item'
		];

		$class = $portletData['class'];
		$portletData['class'] = trim( "$class $navClasses[$type]" );
		$label = isset( $portletData['id'] ) ? $portletData['id'] : $portletData['text'];
		$portletData['msg'] = Sanitizer::escapeIdForAttribute( Library\Constants::SKIN_NAME . '-' . $label );

		if ( isset( $portletData['links'] )) {
			foreach( $portletData['links'] as $key => $item ) {
				$class = $item['class'];
				$portletData['links'][$key]['class'] = trim( "$class nav-link" );
				$portletData['links'][$key]['msg'] = Sanitizer::escapeIdForAttribute( Library\Constants::SKIN_NAME . '-' . $label );
				$portletData['links'][$key]['text'] = null;
			}
		} else {
			$portletData['link-class'] .= ' nav-link';
		}
		// remove array['text'] in order to use translation message.
		$portletData['text'] = null;
		return $portletData;
	}

	/**
	 * Render the associated portlets (navigation) data
	 * @return array of portlet data for all portlets
	 */
	private function getPortletsTemplateData()
	{
		$contentNavigation = $this->buildContentNavigationUrls();
		$customNavigation = Library\Constants::getCustomMenuData( $this->getUser()->isRegistered() ? 1 : 0 );
		$userNavigation = $this->getStructuredPersonalTools();
		$userAuthKey = $this->getConfig()->get( 'LibraryUserAuth' ) ?: 'login';
		$navFilter = ['notifications-alert', 'notifications-notice', 'userpage', 'notifications', 'user-menu' ];
		$userpage = [];
		$portlets = [];
		$sidebar = [];

		// restrict item for un-authorized user
		if ( $this->getUser()->isRegistered() ) {
			$sidebarData = array_merge( $this->buildSidebar(), $customNavigation );
		} else {
			$sidebarData = $customNavigation;
		}

		// data array for masthead navigation
		$portlets['data-masthead'] = $this->getPortletData( 'masthead', $customNavigation['backhome'] );
		// data array for sidebar navigation
		$portlets['data-sidemenu-masthead'] = $this->getPortletData( 'sidemenu', $customNavigation['sidemenu'] );
		$portlets['data-sidemenu-bottom'] = $this->getPortletData( 'sidemenu', $customNavigation['sidebotm'] );

		// data array for usermenu and notifications
		if ( ! in_array( $userAuthKey, $navFilter )) { $navFilter[] = $userAuthKey; }
		foreach ($navFilter as $item) {
			// remove used items from $userNavigation
			if ( array_key_exists( $item, $userNavigation )) {
				$name = $item === $userAuthKey ? $name = 'login' : $name = $item;
				$portlets[ 'data-' . $name ]['id'] = $userNavigation[$item]['id'] ?: $userNavigation[$item]['links'][0]['single-id'];
				$portlets[ 'data-' . $name ]['class'] =  $userNavigation[$item]['links'][0]['class'];
				$portlets[ 'data-' . $name ]['href'] = $userNavigation[$item]['links'][0]['href'];
				$portlets[ 'data-' . $name ]['text'] = $this->getTranslation( $userNavigation[$item]['links'][0]['text'] . '-text' ) ?: $userNavigation[$item]['links'][0]['text'];
				$portlets[ 'data-' . $name ]['src'] = $this->getTranslation( $userNavigation[$item]['links'][0]['text'] . '-avatar' ) ?: $this->getTranslation( 'default-avatar' );
				$portlets[ 'data-' . $name ]['role'] = $this->getTranslation( $userNavigation[$item]['links'][0]['text'] . '-role' ) ?: 'Editor';
				$portlets[ 'data-' . $name ]['data'] = $userNavigation[$item]['links'][0]['data'];
				$portlets[ 'data-' . $name ]['active'] = $userNavigation[$item]['active'];

				if ( $item === 'userpage' ) { $userpage = $portlets[ 'data-' . $name ]; }
				unset($userNavigation[$item]);
			}
			// remove used items from $contentNavigation
			if ( array_key_exists( $item, $contentNavigation )) {
				unset($contentNavigation[$item]);
			}
		}
		// data array for userpage
		if ( isset( $userpage['id'] ) && count($userNavigation) > 0 ) {
			$portlets[ 'data-userpage' ] = $this->getPortletData( 'userpage', $userNavigation );
			$portlets[ 'data-userpage' ] = array_merge( $userpage, $portlets[ 'data-userpage'] );
		}

		// data array for sidebar
		foreach ( $sidebarData as $name => $items ) {
			if ( is_array( $items )) {
				// T73639: numeric strings gets an integer when set as key, cast back
				$name = (string)$name;
				switch ( $name ) {
					// ignore search
					case 'SEARCH':
					case 'LANGUAGES':
					case 'sidemenu':
					case 'sidebotm':
						break;
					case 'TOOLBOX':
						$sidebar[] = $this->getPortletData( 'toolbox', $items );
						break;
					default:
						$sidebar[] = $this->getPortletData( $name, $items );
						break;
				}
			}
		}
		$portlets[ 'data-sidemenu' ] = $sidebar;

		// moving watch from actions to views
		if ( isset( $contentNavigation['actions']['watch'] )) {
			$contentNavigation['views']['watch'] = $contentNavigation['actions']['watch'];
			unset( $contentNavigation['actions']['watch'] );
		} elseif ( isset( $contentNavigation['actions']['unwatch'] )) {
			$contentNavigation['views']['unwatch'] = $contentNavigation['actions']['unwatch'];
			unset( $contentNavigation['actions']['unwatch'] );
		}

		// replace VE id
		if ( isset( $contentNavigation['views']['ve-edit'] )) {
		 	$contentNavigation['views']['ve-edit']['id'] = 'ca-visual-edit';
		}

		foreach ($contentNavigation as $name => $items) {
			$portlets['data-' . $name] = $this->getPortletData($name, $items);
		}

		return $portlets;
	}

	/**
	 * return the translation/message object with its content set.
	 * @param	string 	$name	of the string which need to retrieve.
	 * @return  string 			that is human readable corresponding to the string.
	 * 							return null if not found.
	 */
	private function getTranslation( $name ) {
		$msgObj = $this->msg( Sanitizer::escapeIdForAttribute( Library\Constants::SKIN_NAME . '-' . $name ));
		// If no message exists fallback to plain text
		$labelText = $msgObj->exists() ? $msgObj->text() : null;
		return $labelText;
	}

	/**
	 * Get a message objet with its content set.
	 * 
	 * @param string $str	of the portal, e.g. tb the name is toolbox.
	 * @return string	that is human readable corresponding to the menu.
	 * 					return null if not message object found.
	 */
	private function getPortletLabel(string $str)
	{
		$str = strtolower(preg_replace('/[^a-zA-Z0-9\-]/s', '', "$str"));
		$msgObj = $this->msg(Library\Constants::SKIN_NAME . '-' . $str);
		if ($msgObj->exists()) {
			return $msgObj->text();
			// } else {
			// 	echo( 'debug-' . $str );
		}
	}

	/**
	 * Get the first category name if exists.
	 * 
	 * @return html
	 */
	private function getFirstCategory()
	{
		$catlinks = $this->getOutput()->getCategoryLinks();
		foreach ($catlinks['normal'] as $key => $item) {
			if (strpos($item, 'errors') !== false) {
				unset($catlinks['normal'][$key]);
				continue;
			}
			return $item;
		}
	}
	/**
	 * Extend the method SkinMustache::getTemplateData to add additional template data.
	 * 
	 * The data keys should be valid English words. Compounds words should be hyphenated
	 * except if they are normally written as one word. Each key should be prefixed with
	 * a type hint, this may be enforced by the class PHPUnit test.
	 * 
	 * Naming conventions for Mustache parameters.
	 *
	 * Value type (first segment):
	 * - Prefix "is" or "has" for boolean values.
	 * - Prefix "msg-" for interface message, followed by their message key.
	 * - Prefix "html-" for plain strings or raw HTML.
	 * - Prefix "array-" for plain array or lists of any values.
	 * - Prefix "data-" for an array of template parameters that should be passed directly
	 *   to template partial.
	 *
	 * Source of value (first or second segment):
	 * - Segment "page-" for data relating to the current page (e.g. Title, WikiPage, or OutputPage).
	 * - Segment "hook-" for any thing generated from a hook. It should be followed by the name
	 *   of hook in hyphenated lowercase.
	 *
	 * Conditionally used values must use null to indicate absence (not false or '').
	 * 
	 * TODO: (20210227) add scrollSpy class tags and remove TOC - table of contents from $bodyContent
	 * 
	 * @return array data for Mustache template.
	 */
	public function getTemplateData()
	{
		$out = $this->getOutput();
		$printSource = Html::rawElement('div', ['class' => 'printfooter'], $this->printSource());
		$bodyContent = $out->getHTML() . "\n" . $printSource;

		// table of content
		$dom = new DOMDocument();
		$dom->loadHTML($bodyContent);
		// $node = $dom->getElementById('toc');
		// $tableContent = $dom->saveHTML($node);

		$commonSkinData = [
			'page-isarticle' => (bool)$out->isArticle(),
			'page-istoc' => (bool)(!empty($dom->getElementById('toc'))) ? true : false,
			// array objects
			'array-indicators' => $this->getIndicatorsData($out->getIndicators()),

			// data objects
			// 'data-logos' => $this->getLogoData(),
			'data-search-box' => $this->buildSearchProps(),

			// HTML strings
			'html-site-notice' => $this->getSiteNotice() ?: null,
			'html-title' => strpos($out->getPageTitle(), 'history') ? $out->getPageTitle() : basename($out->getPageTitle()),
			'html-subtitle' => $this->prepareSubtitle(),
			'html-body-content' => $this->wrapHTML($out->getTitle(), $bodyContent),
			'html-head-category' => $this->getFirstCategory(),
			'html-categories' => $this->getCategories(),
			'html-after-content' => $this->afterContentHook(),
			'html-undelete-link' => $this->prepareUndeleteLink(),
			'html-user-language-attributes' => $this->prepareUserLanguageAttributes(),

			// messages
			'msg-sitetitle' => $this->getPortletLabel('sitetitle'),
			'msg-tagline' => $this->getPortletLabel('tagline'),
			'msg-lastedit' => preg_replace('/(.*)on(.*)\,(.*)/', '$2', $this->lastModified()) ?: null,

			// links
			'link-mainpage' => Title::newMainPage()->getLocalUrl(),
			'link-logopath' => $this->getLogoData(),
		];

		foreach ($this->options['messages'] ?? [] as $message) {
			$data["msg-{$message}"] = $this->msg($message)->text();
		}

		// print_r( $out->getHTML() );
		return $commonSkinData + $this->getPortletsTemplateData() + $this->getFooterTemplateData();
	}

	/**
	 * Get rows that make up the footer
	 * TODO: update footer contents
	 * 
	 * @return array for use in Mustache template describing the footer elements.
	 */
	private function getFooterTemplateData(): array
	{
		$data = [];
		foreach ($this->getFooterLinks() as $category => $links) {
			$items = [];
			$rowId = "footer-$category";

			foreach ($links as $key => $link) {
				// Link may be null. If so don't include it.
				if ($link) {
					$items[] = [
						// Monobook uses name rather than id.
						// We may want to change monobook to adhere to the same contract however.
						'name' => $key,
						'id' => "$rowId-$key",
						'html' => $link,
					];
				}
			}

			$data['data-' . $category] = [
				'id' => $rowId,
				'className' => null,
				'array-items' => $items
			];
		}

		// If footer icons are enabled append to the end of the rows
		$footerIcons = $this->getFooterIcons();

		if (count($footerIcons) > 0) {
			$icons = [];
			foreach ($footerIcons as $blockName => $blockIcons) {
				$html = '';
				foreach ($blockIcons as $key => $icon) {
					$html .= $this->makeFooterIcon($icon);
				}
				// For historic reasons this mimics the `icononly` option
				// for BaseTemplate::getFooterIcons. Empty rows should not be output.
				if ($html) {
					$block = htmlspecialchars($blockName);
					$icons[] = [
						'name' => $block,
						'id' => 'footer-' . $block . 'ico',
						'html' => $html,
					];
				}
			}

			// Empty rows should not be output.
			// This is how Vector has behaved historically but we can revisit later if necessary.
			if (count($icons) > 0) {
				$data['data-icons'] = [
					'id' => 'footer-icons',
					'className' => 'noprint',
					'array-items' => $icons,
				];
			}
		}

		return [
			'data-footer' => $data,
		];
	}

	/**
	 * Render the associated template. The master template is assumed
	 * to be 'skin' unless 'template has been passed in the skin options
	 *
	 * @return void
	 */
	public function generateHTML()
	{
		$this->setupTemplateContext();
		$out = $this->getOutput();
		$tp = $this->getTemplateParser();
		$template = $this->options['template'] ?? 'skin';
		$data = $this->getTemplateData();

		// T259955: OutputPage::headElement must be called last (after getTemplateData)
		// as it calls OutputPage::getR1Client, which freezes the ResourceLoader modules
		// queue for the current page load.
		$html = $out->headElement($this);
		$html .= $tp->processTemplate($template, $data);
		$html .= $this->tailElement($out);
		return $html;
	}

	/**
	 * The final bits that go to the bottom of a page.
	 * HTML document including the closing tags.
	 *
	 * @param OutputPage $out
	 * @return string
	 */
	private function tailElement($out)
	{
		$tail = [
			MWDebug::getDebugHTML($this),
			$this->bottomScripts(),
			wfReportTime($out->getCSP()->getNonce()),
			MWDebug::getHTMLDebugLog()
				. Html::closeElement('body')
				. Html::closeElement('html')
		];
		return WrappedStringList::join("\n", $tail);
	}

	/**
	 * @return string 
	 */
	private function getLogoData() : string {
		$data = ResourceLoaderSkinModule::getAvailableLogos( $this->getConfig() );
		return $data['1x'];
	}

	/**
	 * @return array
	 */
	private function buildSearchProps(): array
	{
		$config = $this->getConfig();

		$props = [
			'form-action' => $config->get('Script'),
			'html-button-search-fallback' => $this->makeSearchButton(
				'fulltext',
				['id' => 'mw-searchButton', 'class' => 'searchButton mw-fallbackSearchButton']
			),
			'html-button-search' => $this->makeSearchButton(
				'go',
				['id' => 'searchButton', 'class' => 'searchButton']
			),
			'html-input' => $this->makeSearchInput([
				'id' => 'searchInput',
				'class' => 'form-control',
				'placeholder' => 'Search for anything ...'
			]),
			'msg-search' => $this->msg('search')->text(),
			'page-title' => SpecialPage::getTitleFor('Search')->getPrefixedDBkey(),
		];

		return $props;
	}
}
