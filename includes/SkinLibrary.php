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

use MediaWiki\MediaWikiServices;
use Library\Constants;
use Wikimedia\WrappedStringList;

/**
 * Skin subclass for Library
 * @ingroup Skins
 * @final skins extending SkinLibrary are not supported
 * package Library
 * internal
 */
class SkinLibrary extends SkinMustache
{
	/** @var	int */
	private const MENU_TYPE_DEFAULT = 0;
	/** @var	int */
	private const MENU_TYPE_DROPDOWN = 1;

	/* 	public function __construct($options = [])
	{
		$options['templateDirectory'] = __DIR__ . '/templates';
		parent::__construct($options);
	} */

	/**
	 * Extend the method SkinMustache::getTemplateData to add additional template data.
	 * 
	 * The data keys should be valid English words. Compounds words should be hyphenated
	 * except if they are normally written as one word. Each key should be prefixed with
	 * a type hint, this may be enforced by the class PHPUnit test.
	 * 
	 * @return array data for Mustache template.
	 */
	public function getTemplateData(): array
	{
		$skin = $this;
		$out = $this->getOutput();
		$parentData = parent::getTemplateData();

		// Naming conventions for Mustache parameters.
		//
		// Value type (first segment):
		// - Prefix "is" or "has" for boolean values.
		// - Prefix "msg-" for interface message, followed by their message key.
		// - Prefix "html-" for plain strings or raw HTML.
		// - Prefix "array-" for plain array or lists of any values.
		// - Prefix "data-" for an array of template parameters that should be passed directly
		//   to template partial.
		//
		// Source of value (first or second segment):
		// - Segment "page-" for data relating to the current page (e.g. Title, WikiPage, or OutputPage).
		// - Segment "hook-" for any thing generated from a hook. It should be followed by the name
		//   of hook in hyphenated lowercase.
		//
		// Conditionally used values must use null to indicate absence (not false or '').
		// for debug output, use $this
		// Library\Constants::outputConsoleBeforeHTML('DEBUG 1-1', $commonSkinData);

		$commonSkinData = array_merge(
			$parentData,
			[
				'page-isarticle' => (bool)$out->isArticle(),
				// links
				'link-mainpage' => Title::newMainPage()->getLocalUrl(),
				'link-logopath' => ResourceLoaderSkinModule::getAvailableLogos($this->getConfig())['1x'],

				// array objects
				'data-nav-main' => $this->buildCustNavData('main'),
				'data-nav-rail' => $this->buildCustNavData('shortcut'),
				'data-nav-side' => $this->buildCustNavData('sidebar'),
				'data-nav-bottom' => $this->buildCustNavData('bottom'),
				'data-nav-talk' => $this->buildActionNavData('talk'),
				'data-nav-iconview' => $this->buildActionNavData('iconview'),
				'data-nav-usermenu' => $this->buildActionNavData('usermenu'),
				'data-nav-actions' => $this->buildActionNavData('actions'),

				// HTML string
				'html-header-herobg' => $this->buildHeaderHeroBGLinks(),
				'html-header-category' => $this->buildHeaderCategoryLinks(['class' => 'breadcrumb-item nav-item', 'lnkclass' => 'nav-link']),
				'html-footer-info' => $this->buildFooterLinks('info'),
				'html-footer-category' => $this->buildHeaderCategoryLinks(['class' => 'nav-item', 'lnkclass' => 'nav-link'], false, 10),
				'html-footer-places' => $this->buildFooterLinks('places'),
				'html-footer-icons' => $this->buildFooterLinks('icon'),
				'html-search-input' => $this->makeSearchInput([
					'id' => 'searchInput',
					'class' => 'form-control',
					'placeholder' => 'Search for anything ...'
				]),

				// message string
				'msg-sitename' => $this->getTranslation('sitename'),
				'msg-article-title' => strpos($out->getPageTitle(), 'history') ? $out->getPageTitle() : basename($out->getPageTitle()),
				'msg-article-lastedit' => preg_replace('/(.*)on(.*)\,(.*)/', '$2', $this->lastModified()) ?: null
			]
		);
		return $commonSkinData;
	}

	/**
	 * HTML string for category links
	 * @return	string	HTML
	 */
	private function buildHeaderCategoryLinks(
		$options = [
			'class' => '',
			'lnkclass' => ''
		],
		$unique = true,
		$max = 3
	) {
		$lnk = $this->getOutput()->getCategoryLinks();
		if ( count( $lnk ) === 0 ) {
			return '';
		}
		$tmp = new DOMDocument();
		@$tmp->loadHTML(implode($lnk['normal']));
		$lnk = $tmp->getElementsByTagName('a');
		$remove = ['Pages using DynamicPageList parser function', ''];
		$tmp = [];
		$cnt = 1;
		$rt = '';

		foreach ($lnk as $k => $v) {
			if ($unique) {
				// get root category
				$id = current(explode('/', $v->nodeValue));
			} else {
				$id = $v->nodeValue;
			}
			// remove duplicate root category
			if ((!array_key_exists($id, $tmp)) && ($cnt <= $max) && (!array_key_exists($id, $remove))) {
				$cnt++;
				$tmp[$id] = [
					'text' => $id,
					'class' => $options['class'],
				];
				$tmp[$id]['links'][0] = [
					'text' => $id,
					'class' => $options['lnkclass'],
					'href' => $v->getAttribute('href'),
					'title' => $v->getAttribute('title')
				];
				$rt .= $this->makeListItem($k, $tmp[$id]);
			}
		}
		if (!empty($rt)) {
			return $rt;
		} else {
			return null;
		}
	}

	/**
	 *  Build array of urls for navigation menu
	 * @param	string	$name	
	 * @return 	array
	 */
	private function buildCustNavData(
		$name
	) {
		$nav = Library\Constants::getCustomNavigationData($this->getUser()->isRegistered() ? 1 : 0);
		$rt = [];
		if ($name == 'sidebar') {
			foreach (array_filter(array_merge($this->buildSidebar(), $nav)) as $k => $v) {
				if (is_array($v)) {
					$k = (string)$k;
					switch ($k) {
							// ignore items
						case 'SEARCH':
						case 'LANGUAGE':
						case 'shortcut':
						case 'bottom':
							break;
						default:
							$rt[] = $this->getPortletData(strtolower($k), $v);
							break;
					}
				}
			}
			return $rt;
		} elseif (array_key_exists($name, $nav)) {
			return $this->getPortletData($name, $nav[$name]);
		} else {
			return null;
		}
	}

	private function addClass( &$obj, $class, $field = 'class' ) {
		$classList = $obj[$field] ?? [];
		if ( is_array( $classList ) ) {
			$classList[] = $class;
			$obj[$field] = $classList;
		} else {
			$obj[$field] = $classList . ' ' . $class;
		}
	}

	/**
	 * build array of url for page actions
	 * @note: including 
	 * - notification from FLOW extension
	 * - discussion namespace from buildContentNavigationUrls()
	 * - edit action from getStructuredPersonalTools()
	 * - history and watchlist from getStructuredPersonalTools()
	 * 
	 * @return	array
	 */
	private function buildActionNavData(
		$name
	) {
		$nav = $this->buildContentNavigationUrls();
		$pt = $this->getStructuredPersonalTools();
		$talk = ['notifications-alert', 'notifications-notice'];
		$login = $this->getConfig()->get('LibraryDefaultLogin') ?: 'login';
		$iconview = $this->getConfig()->get('LibraryIconView');
		$actions = $this->getConfig()->get('LibraryBottomAction');
		$rtn = [];

		switch ($name) {
			case 'usermenu':
				// remove talk (notification) from array
				foreach ($talk as $key) {
					if (array_key_exists($key, $pt)) {
						unset($pt[$key]);
					}
				}
				// restructure data for unauthorized user
				if (array_key_exists($login, $pt)) {
					$rtn = [
						'id' => 'login',
						'text' => $this->getTranslation($pt[$login]['links'][0]['text'] . '-text') ?: $pt[$login]['links'][0]['text'],
						'href' => $pt[$login]['links'][0]['href'],
						'src' => $this->getTranslation($pt[$login]['links'][0]['text'] . '-avatar') ?:  $this->getTranslation('default-avatar')
					];
					unset($pt[$login]);
					$tmp = $this->getPortletData('usermenu', $pt);
					$rtn['html-items'] = $tmp['html-items'];
				}
				// restructure data for authorized user
				if (array_key_exists('userpage', $pt)) {
					$rtn = [
						'id' => 'userpage',
						'text' => $this->getTranslation($pt['userpage']['links'][0]['text'] . '-text') ?: $pt['userpage']['links'][0]['text'],
						'href' => $pt['userpage']['links'][0]['href'],
						'src' => $this->getTranslation($pt['userpage']['links'][0]['text'] . '-avatar') ?:  $this->getTranslation('default-avatar')
					];
					unset($pt['userpage']);
					$tmp = $this->getPortletData('usermenu', $pt);
					$rtn['html-items'] = $tmp['html-items'];
				}
				if (is_array($rtn)) {
					return $rtn;
				}
				break;
			default:
				foreach (${$name} as $key) {
					foreach ($nav as $k => $v) {
						if (array_key_exists($key, $v)) {
							$rtn[$key] = $nav[$k][$key];
							if ($name == 'iconview') {
								$this->addClass( $rtn[$key], 'icon-only' );
							}
						}
					}
					if (array_key_exists($key, $pt)) {
						$rtn[$key] = $pt[$key];
						if ($name == 'iconview') {
							$this->addClass( $rtn[$key], 'icon-only' );
						}
					}
				}
				// renmae id for visual editor
				if (array_key_exists('ve-edit', $rtn)) {
					$rtn['ve-edit']['id'] = 'ca-visual-editor';
				}
				// rename id for source editor
				if (array_key_exists('edit', $rtn)) {
					$rtn['edit']['id'] = 'ca-source-editor';
				}
				if (is_array($rtn)) {
					return $this->getPortletData($name, $rtn);
				}
				break;
		}
		return null;
	}
	/**
	 * HTML string for background of hero header
	 * @return	string	HTML
	 */
	private function buildHeaderHeroBGLinks()
	{
		$bg = $this->getConfig()->get('LibraryHeroHeaderBG');
		$i = rand(0, count($bg) - 1);
		$rt = 'class="background';
		if (current(explode('-', basename($bg[$i]))) == 'dark') {
			$rt .= ' dark';
		}
		$rt .= '" style="background-image: url(' . $bg[$i] . ');"';
		return $rt;
	}

	/**
	 * HTML string for footer links
	 * @return	string	HTML
	 */
	private function buildFooterLinks(
		$name
	) {
		$lnk = $this->getFooterLinks();
		$ico = $this->getFooterIcons('icononly');
		$info = ['lastmod', 'copyright'];
		$tmp = new DOMDocument();
		$rt = '';

		switch ($name) {
			case 'info':
				foreach (${$name} as $key) {
					if (!empty($lnk[$name][$key])) {
						$rt .= Html::rawElement(
							'p',
							[],
							$lnk[$name][$key]
						);
					}
				}
				break;
			case 'places':
				@$tmp->loadHTML(implode($lnk['places']));
				$lnk = $tmp->getElementsByTagName('a');
				$tmp = [];

				foreach ($lnk as $k => $v) {
					$id = $v->nodeValue;
					$tmp[$id] = [
						'text' => $id,
						'class' => 'nav-item',
					];
					$tmp[$id]['links'][0] = [
						'text' => $id,
						'class' => 'nav-link',
						'href' => $v->getAttribute('href'),
						'title' => $v->getAttribute('title')
					];
					$rt .= $this->makeListItem($k, $tmp[$id]);
				}
				break;
			case 'icon':
				if (count($ico) > 0) {
					foreach ($ico as $k => $v) {
						foreach ($v as $icon) {
							$icon['class'] = 'px-1';
							$rt .= $this->makeFooterIcon($icon);
						}
					}
				}
				break;
		}
		if (!empty($rt)) {
			return $rt;
		} else {
			return null;
		}
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
	): array {
		switch ($name) {
			case 'usermenu':
				$type = self::MENU_TYPE_DROPDOWN;
				break;
			default:
				$type = self::MENU_TYPE_DEFAULT;
				break;
		}
		if (count($items) > 0) {
			$id = Sanitizer::escapeIdForAttribute("p-$name");
			$portletData = [
				'id' => $id,
				'class' => Sanitizer::escapeClass("portlet-$name"),
				'html-tooltip' => Linker::tooltip($name),
				'html-items' => '',
				'label' => $this->getTranslation($id) ?: Sanitizer::escapeIdForAttribute($name)
			];
			foreach ($items as $key => $item) {
				$portletData['html-items'] .= htmlspecialchars_decode($this->makeListItem($key, $this->decoratePortletClass($item, $type, $key)));
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
	 * @param string $key to fallback to
	 * @return 	array					modified version of portletData input
	 */
	private function decoratePortletClass(
		array 	$portletData,
		int 	$type = self::MENU_TYPE_DEFAULT,
		string $key = 'item-unknown'
	) {
		// $class = $portletData['class'];
		if ($type == self::MENU_TYPE_DEFAULT) {
			$this->addClass( $portletData, 'nav-item' );
		}

		$label = isset($portletData['id']) ? $portletData['id'] : $portletData['text'] ?? $key ;
		$portletData['msg'] = Sanitizer::escapeIdForAttribute(Library\Constants::SKIN_NAME . '-' . $label);

		if (isset($portletData['links'])) {
			foreach ($portletData['links'] as $key => $item) {
				if ($type == self::MENU_TYPE_DROPDOWN) {
					$this->addClass( $portletData['links'][$key], 'dropdown-item' );
				} else {
					$this->addClass( $portletData['links'][$key], 'nav-link' );
				}
				$portletData['links'][$key]['msg'] = Sanitizer::escapeIdForAttribute(Library\Constants::SKIN_NAME . '-' . $label);
				$portletData['links'][$key]['text'] = null;
			}
		} else {
			$this->addClass( $portletData, 'nav-link', 'link-class' );
		}
		// remove array['text'] in order to use translation message.
		$portletData['text'] = null;
		return $portletData;
	}
	/**
	 * return the translation/message object with its content set.
	 * @param	string 	$name	of the string which need to retrieve.
	 * @return  string 			that is human readable corresponding to the string.
	 * 							return null if not found.
	 */
	private function getTranslation($name)
	{
		$msgObj = $this->msg(Sanitizer::escapeIdForAttribute(Library\Constants::SKIN_NAME . '-' . $name));
		// If no message exists fallback to plain text
		$labelText = $msgObj->exists() ? $msgObj->text() : null;
		return $labelText;
	}
}
