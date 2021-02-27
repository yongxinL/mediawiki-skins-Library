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
	 * Render the associated portlets (navigation) data
	 *
	 * @return array of portlet data for all portlets
	 */
	private function getPortletsTemplateData()
	{
		$contentNavigation = $this->buildContentNavigationUrls();
		$notification = ['notifications-alert', 'notifications-notice', 'userpage', 'login', 'login-private'];
		$portlets = [];
		$sidebarHeader = [];
		$sidebarData = [];
		$sidebar = array_merge(
			$this->buildSidebar(),
			Library\Constants::getCustomNav($this->getUser()->isRegistered())
		);

		$usermenu = $this->getStructuredPersonalTools();
		if ($usermenu['userpage']) {
			$usermenu['userpage']['links'][0]['avatar'] = $this->getPortletLabel('user-' . $usermenu['userpage']['links'][0]['text'] . '-avatar')
				?? '<img src="https://ui-avatars.com/api/?length=2&size=80&rounded=true&name=' . str_replace('.', '+', $usermenu['userpage']['links'][0]['text']) . '">';
			$usermenu['userpage']['links'][0]['member'] = $this->getPortletLabel('user-' . $usermenu['userpage']['links'][0]['text'] . '-member') ?? 'Member';
			$usermenu['userpage']['links'][0]['name'] = $this->getPortletLabel('user-' . $usermenu['userpage']['links'][0]['text'] . '-name') ?? $usermenu['userpage']['links'][0]['text'];
		} else {
			$usermenu['userpage']['links'][0]['avatar'] = $this->getPortletLabel('user-anonymous-avatar')
				?? '<img src="/w/skins/Library/resources/images/anonymous.png">';
			$usermenu['userpage']['links'][0]['member'] = $this->getPortletLabel('user-anonymous-member') ?? 'Unknown';
			$usermenu['userpage']['links'][0]['name'] = $this->getPortletLabel('user-anonymous-name') ?? 'anonymous';
		}

		if (($usermenu['anon_oauth_login']) && ($sidebar['nav']['Login'])) {
			$sidebar['nav']['Login']['href'] = $usermenu['anon_oauth_login']['links'][0]['href'];
		}

		foreach ($notification as $name) {
			if (array_key_exists($name, $usermenu)) {
				$portlets['data-' . $name] = $usermenu[$name]['links'];
				unset($usermenu[$name]);
			}
		}
		$portlets['data-personal'] = $this->getPortletData('personal', $usermenu);

		foreach ($sidebar as $name => $items) {
			if (is_array($items)) {
				// T73639: numeric strings gets an integer when set as key, cast back
				$name = (string)$name;
				switch ($name) {
					case 'SEARCH':
						// ignore search
						break;
					case 'TOOLBOX':
						// toolbox to `tb` id.
						$sidebarHeader[] = $this->getPortletData('tb', []);
						$sidebarData[] = $this->getPortletData('tb', $items, SELF::MENU_TYPE_SIDEBAR);
						break;
						// Languages is no longer be tied to sidebar
					case 'LANGUAGES':
						// T252800: Language portal will be aded provided either
						// language exists or there is a value in html-after-portal.
						$portal = $this->getPortletData('lang', $items);
						if (count($items) || $portal['html-after-portal']) {
							$portlets['data-languages'] = $portal;
						}
						break;
					default:
						$sidebarHeader[] = $this->getPortletData($name, []);
						$sidebarData[] = $this->getPortletData($name, $items, SELF::MENU_TYPE_SIDEBAR);
						break;
				}
			}
		}

		foreach ($contentNavigation as $name => $items) {
			$portlets['data-' . $name] = $this->getPortletData($name, $items);
		}

		return [
			'data-sidebar-iconbar' => $this->getPortletData('iconbar', $sidebarHeader),
			'data-sidebar-aside' => $sidebarData,
		] + $portlets;
	}

	/**
	 * rendering data that can be passed to a Mustache template that
	 * represents a single menu.
	 *
	 * @param [type] $name	of portal. e.g. p-personal the name is personal
	 * @param array $items	that are accepted input to Skin::makeListItem
	 * @return array
	 */
	private function getPortletData(string $name, array $items = [], int $type = -1)
	{
		$extraClasses = [
			SELF::MENU_TYPE_DEFAULT => 'nav-link',
			SELF::MENU_TYPE_SIDEBAR => 'nav-item',
			SELF::MENU_TYPE_DROPDOWN => 'dropdown-item'
		];

		switch ($name) {
			case 'actions':
			case 'personal':
				$type = SELF::MENU_TYPE_DROPDOWN;
			default:
				($type != -1) ? $type : $type = SELF::MENU_TYPE_DEFAULT;
				break;
		}

		$id = strtolower(preg_replace('/[^a-zA-Z0-9]/s', '', "$name"));
		$portletData = [
			'single-id' => 'p-' . $id,
			'href' => '#' . $id,
			'text' => $this->getPortletLabel($id),
			'class' => 'nav-link ' . Sanitizer::escapeClass("portlet-$name"),
			'html-tooltip' => Linker::tooltip($name),
			'is-navlink' => $type === SELF::MENU_TYPE_DEFAULT,
			'is-sidebar' => $type === SELF::MENU_TYPE_SIDEBAR,
			'is-dropdown' => $type === SELF::MENU_TYPE_DROPDOWN,
		];

		if ($type == SELF::MENU_TYPE_SIDEBAR) {
			$portletData['id'] = $id;
			$portletData['title'] = $this->getPortletLabel("$id-sidetitle");
			$portletData['info'] = $this->getPortletLabel("$id-sideinfo");
		}

		if (count($items) > 0) {
			foreach ($items as $key => $item) {
				$text = ((isset($item['text'])) && ($this->getPortletLabel($id . '-' . $item['text']))) ? $this->getPortletLabel($id . '-' . $item['text']) : null;
				$text = ((empty($text)) && (isset($item['id'])) && ($this->getPortletLabel($id . '-' . $item['id']))) ? $this->getPortletLabel($id . '-' . $item['id']) : null;
				$text = ((empty($text)) && ($this->getPortletLabel($id . '-' . $key))) ? $this->getPortletLabel($id . '-' . $key) : null;
				$item['text'] = $text ?? $item['text'];
				$class = $item['class'];
				$item['class'] = trim("$class $extraClasses[$type]");

				if ($type == SELF::MENU_TYPE_SIDEBAR) {
					$options['link-class'] = trim("nav-link " . $item['link-class']);
					$portletData['html-items'] .= htmlspecialchars_decode($this->makeListItem($key, $item, $options));
				} elseif (isset($item['links'])) {
					foreach ($item['links'] as $k => $link) {
						$text = ((isset($link['text'])) && ($this->getPortletLabel($id . '-' . $link['text']))) ? $this->getPortletLabel($id . '-' . $link['text']) : null;
						$link['text'] = $text ?? $link['text'];
						$link['class'] = trim("$class $extraClasses[$type]");
						$portletData['html-items'] .= htmlspecialchars_decode($this->makeLink($k, $link));
					}
				} else {
					$portletData['html-items'] .= htmlspecialchars_decode($this->makeLink($key, $item));
				}
			}
		}

		return $portletData;
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
