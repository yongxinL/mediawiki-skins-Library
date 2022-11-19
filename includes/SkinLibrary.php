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
	/** @var int */
	private const MENU_TYPE_DEFAULT = 0;
	/** @var int */
	private const MENU_TYPE_DROPDOWN = 1;
	/**
	 * nav item keys for StructuredDiscussions (flow) extension.
	 */
	private const CONFIG_ARRAY_TALK = [
		'notifications-alert',
		'notifications-notice'
	];
	private const CONFIG_ARRAY_VIEWS = [
		'view',
		've-edit'
	];
	/**
	 * item will be remove from category links
	 */
	private const CONFIG_CATEGORY_FILTER = [
		'Pages using DynamicPageList parser function',
		'Pages with syntax highlighting errors'
	];

	public function __construct($options = [])
	{
		$options['templateDirectory'] = __DIR__ . '/templates';
		parent::__construct($options);
	}
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
				'page-isheroheader' => $this->isHeroHeader(),
				// links
				'link-mainpage' => Title::newMainPage()->getLocalUrl(),
				'link-logopath' => ResourceLoaderSkinModule::getAvailableLogos($this->getConfig())['1x'],

				// array objects
				'data-nav-main' => $this->buildCustNavData('main'),
				'data-nav-rail' => $this->buildCustNavData('shortcut'),
				'data-nav-side' => $this->buildCustNavData('sidebar'),
				'data-nav-bottom' => $this->buildCustNavData('bottom'),
				'data-nav-actions' => $this->buildActionNavData('actions'),
				'data-nav-iconview' => $this->buildActionNavData('iconviews'),
				'data-nav-namespace' => $this->buildActionNavData('namespace'),
				'data-nav-talk' => $this->buildActionNavData('talk'),
				'data-nav-usermenu' => $this->buildActionNavData('usermenu'),
				'data-nav-views' => $this->buildActionNavData('views'),

				// HTML string
				'html-header-herobg' => $this->buildHeaderHeroBGLinks(),
				'html-header-category' => $this->buildCategoryLinks(['class' => 'breadcrumb-item nav-item', 'lnkclass' => 'nav-link']),
				'html-footer-info' => $this->buildFooterLinks('info'),
				'html-footer-category' => $this->buildCategoryLinks(['class' => 'nav-item', 'lnkclass' => 'nav-link'], false, 10),
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
	private function buildCategoryLinks(
		$options = [
			'class' => '',
			'lnkclass' => ''
		],
		$unique = true,
		$max = 3
	) {
		$lnk = $this->getOutput()->getCategoryLinks();
		if (count($lnk['normal'] ?? []) === 0) {
			return null;
		} else {
			$tmp = new DOMDocument();
			@$tmp->loadHTML(implode($lnk['normal']));
			$lnk = $tmp->getElementsByTagName('a');
			$tmp = [];
			$cnt = 1;
			$rtn = '';

			foreach ($lnk as $k => $v) {
				if ($unique) {
					// get root category
					$id = current(explode('/', $v->nodeValue));
				} else {
					$id = $v->nodeValue;
				}
				// remove duplicate root category
				if ((!array_key_exists($id, $tmp)) && ($cnt <= $max) && (!in_array($id, self::CONFIG_CATEGORY_FILTER))) {
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
					$rtn .= $this->makeListItem($k, $tmp[$id]);
				}
			}
		}
		return $rtn;
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
		$rtn = [];
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
							$rtn[] = $this->getLibraryPortletData(strtolower($k), $v);
							break;
					}
				}
			}
			return $rtn;
		} elseif (array_key_exists($name, $nav)) {
			return $this->getLibraryPortletData($name, $nav[$name]);
		} else {
			return null;
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
		$login = $this->getConfig()->get('LibraryDefaultLogin') ?: 'login';
		$talk = self::CONFIG_ARRAY_TALK;
		$views = self::CONFIG_ARRAY_VIEWS;
		$iconviews = $this->getConfig()->get('LibraryIconViews');
		$actions = $this->getConfig()->get('LibraryBottomActions');
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
					$tmp = $this->getLibraryPortletData('usermenu', $pt);
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
					$tmp = $this->getLibraryPortletData('usermenu', $pt);
					$rtn['html-items'] = $tmp['html-items'];
				}
				break;
			case 'namespace':
				foreach ($nav['namespaces'] as $k => $v) {
					if ($v['context'] == 'subject') {
						$rtn = [
							'id' => $k,
							'text' => $v['text'],
							'href' => $v['href'],
							'html-items' => Html::rawElement(
								'a',
								[
									'class' => $v['class'] . ' article-header__subtitle',
									'href' => $v['href'],
									'nsid' => $k,
									'title' => $v['text']
								],
								$this->getTranslation($v['id']) ?? $v['text']
							)
						];
						break;
					}
				}
				break;
			default:
				$tmp = null;
				foreach (${$name} as $key) {
					foreach ($nav as $k => $v) {
						if (array_key_exists($key, $v)) {
							$tmp[$key] = $nav[$k][$key];
							$tmp[$key]['id'] = $name . '-' . $tmp[$key]['id'];
							if ($name == 'iconviews') {
								$this->addClass($tmp[$key], 'icon-only d-none d-lg-block');
							}
						}
					}
					if (array_key_exists($key, $pt)) {
						$tmp[$key] = $pt[$key];
						$tmp[$key]['id'] = $name . '-' . $tmp[$key]['id'];
						if (($name == 'iconviews') || ($name == 'talk')) {
							$this->addClass($tmp[$key]['links'][0], 'icon-only d-none d-md-block');
						}
					}
				}
				if (is_array($tmp)) {
					$rtn = $this->getLibraryPortletData($name, $tmp);
				}
				break;
		}
		return $rtn ?? null;
	}
	/**
	 * set whether to display hero header.
	 * @return	bool
	 */
	private function isHeroHeader()
	{
		$current = $this->buildActionNavData('namespace');
		$ns = $this->getConfig()->get('LibraryHeroHeaderNS');

		if ((in_array($current['id'], $ns)) && ($this->getOutput()->isArticle())) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * HTML string for background of hero header
	 * @return	string	HTML
	 */
	private function buildHeaderHeroBGLinks()
	{
		$bg = $this->getConfig()->get('LibraryHeroHeaderBG');
		$i = rand(0, count($bg) - 1);
		$rtn = 'class="background';
		if (current(explode('-', basename($bg[$i]))) == 'dark') {
			$rtn .= ' dark';
		}
		$rtn .= '" style="background-image: url(' . $bg[$i] . ');"';
		return $rtn;
	}
	/**
	 * Restores the getFooterLinks method that was removed in
	 * https://gerrit.wikimedia.org/r/c/mediawiki/core/+/808913
	 *
	 * @return string[] Map of (key => HTML) for 'privacy', 'about', 'disclaimer'
	 */
	private function getSiteFooterLinks() {
		return [
			'privacy' => $this->footerLink( 'privacy', 'privacypage' ),
			'about' => $this->footerLink( 'aboutsite', 'aboutpage' ),
			'disclaimer' => $this->footerLink( 'disclaimers', 'disclaimerpage' )
		];
	}
	/**
	 * Restores the getFooterLinks method that was removed in
	 * https://gerrit.wikimedia.org/r/c/mediawiki/core/+/808913
	 *
	 * @internal
	 * @return array
	 */
	protected function getFooterLinks(): array {
		$out = $this->getOutput();
		$title = $out->getTitle();
		$titleExists = $title->exists();
		$config = $this->getConfig();
		$maxCredits = $config->get( 'MaxCredits' );
		$showCreditsIfMax = $config->get( 'ShowCreditsIfMax' );
		$useCredits = $titleExists
			&& $out->isArticle()
			&& $out->isRevisionCurrent()
			&& $maxCredits !== 0;

		/** @var CreditsAction $action */
		if ( $useCredits ) {
			$article = Article::newFromWikiPage( $this->getWikiPage(), $this );
			$action = Action::factory( 'credits', $article, $this );
		}

		'@phan-var CreditsAction $action';
		$data = [
			'info' => [
				'lastmod' => !$useCredits ? $this->lastModified() : null,
				'numberofwatchingusers' => null,
				'credits' => $useCredits ?
					$action->getCredits( $maxCredits, $showCreditsIfMax ) : null,
				'copyright' => $titleExists &&
					$out->showsCopyright() ? $this->getCopyright() : null,
			],
			'places' => $this->getSiteFooterLinks(),
		];
		foreach ( $data as $key => $existingItems ) {
			$newItems = [];
			$this->getHookRunner()->onSkinAddFooterLinks( $this, $key, $newItems );
			$data[$key] = $existingItems + $newItems;
		}
		return $data;
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
		$rtn = '';

		switch ($name) {
			case 'info':
				foreach (${$name} as $key) {
					if (!empty($lnk[$name][$key])) {
						$rtn .= Html::rawElement(
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
					$rtn .= $this->makeListItem($k, $tmp[$id]);
				}
				break;
			case 'icon':
				if (count($ico) > 0) {
					foreach ($ico as $k => $v) {
						foreach ($v as $icon) {
							$icon['class'] = 'px-1';
							$rtn .= $this->makeFooterIcon($icon);
						}
					}
				}
				break;
		}
		if (!empty($rtn)) {
			return $rtn;
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
	protected function getLibraryPortletData(
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
				$portletData['html-items'] .= htmlspecialchars_decode($this->makeListItem($key, $this->decoratePortletClass($item, $type)));
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
	 * 									MENU_TYPE_DROPDOWN- a list items in dropdown
	 * @return 	array					modified version of portletData input
	 */
	private function decoratePortletClass(
		array 	$portletData,
		int 	$type = self::MENU_TYPE_DEFAULT
	) {
		if ($type == self::MENU_TYPE_DEFAULT) {
			$this->addClass($portletData, 'nav-item');
		}

		$label = isset($portletData['id']) ? $portletData['id'] : ( $portletData['text'] ?? null );
		if ( $label ) {
			$portletData['msg'] = Sanitizer::escapeIdForAttribute(
				Library\Constants::SKIN_NAME . '-' . $label
			);
		}

		if (isset($portletData['links'])) {
			foreach ($portletData['links'] as $key => $item) {
				if ($type == self::MENU_TYPE_DROPDOWN) {
					$this->addClass($portletData['links'][$key], 'dropdown-item');
				} else {
					$this->addClass($portletData['links'][$key], 'nav-link');
				}
				$portletData['links'][$key]['msg'] = Sanitizer::escapeIdForAttribute(Library\Constants::SKIN_NAME . '-' . $label);
				$portletData['links'][$key]['text'] = null;
			}
		} else {
			if ( isset( $portletData['link-class'] ) ) {
				$portletData['link-class'] = [];
			}
			$portletData['link-class'][] = 'nav-link';
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
	/**
	 * Adds a class to the passed element accounting for string
	 * and array definitions
	 *
	 * @param array &$obj to update
	 * @param string $class to add
	 * @param string $field to write to
	 * 
	 */
	private function addClass(&$obj, $class, $field = 'class')
	{
		$classList = $obj[$field] ?? [];
		if (is_array($classList)) {
			$classList[] = $class;
			$obj[$field] = $classList;
		} else {
			$obj[$field] = $classList . ' ' . $class;
		}
	}
}
