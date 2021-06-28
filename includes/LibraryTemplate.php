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

/**
 * QuickTemplate subclass for Library
 * @ingroup Skins
 */
class LibraryTemplate extends BaseTemplate {
	/** @var array of alternate message keys for menu labels */
	private const MENU_LABEL_KEYS = [
		'cactions' => 'library-more-actions',
		'tb' => 'toolbox',
		'personal' => 'personaltools',
		'lang' => 'otherlanguages',
	];
	/** @var int */
	private const MENU_TYPE_DEFAULT = 0;
	/** @var int */
	private const MENU_TYPE_TABS = 1;
	/** @var int */
	private const MENU_TYPE_DROPDOWN = 2;
	private const MENU_TYPE_PORTAL = 3;

	/**
	 * T243281: Code used to track clicks to opt-out link.
	 *
	 * The "vct" substring is used to describe the newest "Library" (non-legacy)
	 * feature. The "w" describes the web platform. The "1" describes the version
	 * of the feature.
	 *
	 * @see https://wikitech.wikimedia.org/wiki/Provenance
	 * @var string
	 */
	private const OPT_OUT_LINK_TRACKING_CODE = 'vctw1';

	/** @var TemplateParser */
	private $templateParser;
	/** @var string File name of the root (master) template without folder path and extension */
	private $templateRoot;

	/** @var bool */
	private $isLegacy;

	/**
	 * @param Config $config
	 * @param TemplateParser $templateParser
	 * @param bool $isLegacy
	 */
	public function __construct(
		Config $config,
		TemplateParser $templateParser,
		bool $isLegacy
	) {
		parent::__construct( $config );

		$this->templateParser = $templateParser;
		$this->isLegacy = $isLegacy;
		$this->templateRoot = $isLegacy ? 'skin-legacy' : 'skin';
	}

	/**
	 * Amends the default behavior of BaseTemplate to return rather
	 * than echo.
	 * @param string $key
	 * @return Message
	 */
	public function msg( $key ) {
		return $this->getMsg( 'library-' . $key );
	}

	/**
	 * @return Config
	 */
	private function getConfig() {
		return $this->config;
	}

	/**
	 * @return Main menu
	 */
	private function getMainMenu() : array {
		return [
			'MAINMENU' => [
				'0' => [
					'text' => 'Home',
					'href' => '/',
					'anonymous' => true,
				],
				'1' => [
					'text' => 'Login',
					'href' => '/wp-login.php',
					'anonymous' => true,
				],
				'2' => [
					'text' => 'Home',
					'href' => '/',
					'anonymous' => false,
				],
				'3' => [
					'text' => 'Family',
					'href' => '/category/family/',
				],
				'4' => [
					'text' => 'Life',
					'href' => '/category/life/',
				],
				'5' => [
					'text' => 'Blogging',
					'href' => '/category/life/blog/',
				],
				'6' => [
					'text' => 'Photography',
					'href' => '/category/life/photo/',
	
				],
				'7' => [
					'text' => 'Travel',
					'href' => '/category/travel/',
				],
				'8' => [
					'text' => 'Holiday',
					'href' => '/category/travel/holiday/',
				],
				'9' => [
					'text' => 'Library',
					'href' => '/wiki/Main_Page',
				],
				'10' => [
					'text' => 'Members',
					'href' => '/members/',
				],
				'11' => [
					'text' => 'Log out',
					'href' => '/logout/',
					'anonymous' => false,
				],

			],
		];
	}

	/**
	 * The template parser might be undefined. This function will check if it set first
	 *
	 * @return TemplateParser
	 */
	protected function getTemplateParser() {
		if ( $this->templateParser === null ) {
			throw new \LogicException(
				'TemplateParser has to be set first via setTemplateParser method'
			);
		}
		return $this->templateParser;
	}

	/**
	 * @return array Returns an array of data shared between Library and legacy
	 * Library.
	 */
	private function getSkinData() : array {
		$contentNavigation = $this->get( 'content_navigation', [] );
		$skin = $this->getSkin();
		$out = $skin->getOutput();
		$title = $out->getTitle();

		ob_start();
		Hooks::run( 'LibraryBeforeFooter', [], '1.35' );
		$htmlHookLibraryBeforeFooter = ob_get_contents();
		ob_end_clean();

		// Naming conventions for Mustache parameters.
		//
		// Value type (first segment):
		// - Prefix "is" or "has" for boolean values.
		// - Prefix "msg-" for interface message text.
		// - Prefix "html-" for raw HTML.
		// - Prefix "data-" for an array of template parameters that should be passed directly
		//   to a template partial.
		// - Prefix "array-" for lists of any values.
		//
		// Source of value (first or second segment)
		// - Segment "page-" for data relating to the current page (e.g. Title, WikiPage, or OutputPage).
		// - Segment "hook-" for any thing generated from a hook.
		//   It should be followed by the name of the hook in hyphenated lowercase.
		//
		// Conditionally used values must use null to indicate absence (not false or '').
		$mainPageHref = Skin::makeMainPageUrl();
		$commonSkinData = [
			'html-headelement' => $out->headElement( $skin ),
			'html-sitenotice' => $this->get( 'sitenotice', null ),
			'html-indicators' => $this->getIndicators(),
			'page-langcode' => $title->getPageViewLanguage()->getHtmlCode(),
			'page-isarticle' => (bool)$out->isArticle(),

			// Remember that the string '0' is a valid title.
			// From OutputPage::getPageTitle, via ::setPageTitle().
			'html-title' => strpos( $out->getPageTitle(), 'history' ) ? $out->getPageTitle() : basename( $out->getPageTitle() ), 

			'html-prebodyhtml' => $this->get( 'prebodyhtml', '' ),
			'msg-tagline' => $this->msg( 'tagline' )->text(),
			// TODO: Use Skin::prepareUserLanguageAttributes() when available.
			'html-userlangattributes' => $this->get( 'userlangattributes', '' ),
			// From OutputPage::getSubtitle()
			'html-subtitle' => $this->get( 'subtitle', '' ),

			// TODO: Use Skin::prepareUndeleteLink() when available
			// Always returns string, cast to null if empty.
			'html-undelete' => $this->get( 'undelete', null ) ?: null,

			// From Skin::getNewtalks(). Always returns string, cast to null if empty.
			'html-newtalk' => $skin->getNewtalks() ?: null,

			// 'msg-library-jumptonavigation' => $this->msg( 'library-jumptonavigation' )->text(),
			// 'msg-library-jumptosearch' => $this->msg( 'library-jumptosearch' )->text(),

			// Result of OutputPage::addHTML calls
			'html-bodycontent' => $this->get( 'bodycontent' ),

			'html-printfooter' => $skin->printSource(),
			'msg-lastedit' => preg_replace('/(.*)on(.*)\,(.*)/', '$2', $this->get( 'lastmod' ) ) ?: null,
			'msg-maincategory' => preg_replace('/(.*?)<li>(.*?)<\/li>(.*)/', '$2', $skin->getCategoryLinks() ) ?: null,
			'html-catlinks' => $skin->getCategories(),
			'html-dataAfterContent' => $this->get( 'dataAfterContent', '' ),
			// From MWDebug::getHTMLDebugLog (when $wgShowDebug is enabled)
			'html-debuglog' => $this->get( 'debughtml', '' ),
			// From BaseTemplate::getTrail (handles bottom JavaScript)
			'html-printtail' => $this->getTrail() . '</body></html>',
			'data-footer' => [
				'html-userlangattributes' => $this->get( 'userlangattributes', '' ),
				'html-hook-library-before-footer' => $htmlHookLibraryBeforeFooter,
				'array-footer-rows' => $this->getTemplateFooterRows(),
			],
			// 'html-navigation-heading' => $this->msg( 'navigation-heading' ),
			'data-search-box' => $this->buildSearchProps(),

			// Header
			// 'data-logos' => ResourceLoaderSkinModule::getAvailableLogos( $this->getConfig() ),
			'msg-sitetitle' => $this->msg( 'sitetitle' )->text(),
			'msg-sitesubtitle' => $this->msg( 'sitesubtitle' )->text(),
			'main-page-href' => $mainPageHref,

			'data-sidebar' => $this->buildSidebarProps(),
		] + $this->getMenuProps();

		// The following logic is unqiue to Library (not used by legacy Library) and
		// is planned to be moved in a follow-up patch.
		/*
		if ( !$this->isLegacy && $skin->getUser()->isLoggedIn() ) {
			$commonSkinData['data-sidebar']['data-emphasized-sidebar-action'] = [
				'href' => SpecialPage::getTitleFor(
					'Preferences',
					false,
					'mw-prefsection-rendering-skin-skin-prefs'
				)->getLinkURL( 'wprov=' . self::OPT_OUT_LINK_TRACKING_CODE ),
				'text' => $this->msg( 'library-opt-out' )->text(),
				'title' => $this->msg( 'library-opt-out-tooltip' )->text(),
			];
		}
		*/
		return $commonSkinData;
	}

	/**
	 * Renders the entire contents of the HTML page.
	 */
	public function execute() {
		$tp = $this->getTemplateParser();
		echo $tp->processTemplate( $this->templateRoot, $this->getSkinData() );
	}

	/**
	 * Get rows that make up the footer
	 * @return array for use in Mustache template describing the footer elements.
	 */
	private function getTemplateFooterRows() : array {
		$skin = $this->getSkin();
		$footerRows = [];
		foreach ( $this->getFooterLinks() as $category => $links ) {
			$items = [];
			$rowId = "footer-$category";

			foreach ( $links as $link ) {
				$items[] = [
					'id' => "$rowId-$link",
					'html' => $this->get( $link, '' ),
				];
			}

			$footerRows[] = [
				'id' => $rowId,
				'className' => null,
				'array-items' => $items
			];
		}

		return $footerRows;
	}

	/**
	 * Render a series of portals
	 *
	 * @return array
	 */
	private function buildSidebarProps() : array {
		$skin = $this->getSkin();
		$portals = $this->getMainMenu();
		$props = [];

		// for logged out users Library shows a limited menu
		if ( $skin->getUser()->isAnon() ) {
			// remove un-authorized items
			foreach ( $portals['MAINMENU'] as $name => $content ) {
				if ( isset( $content['anonymous'] ) && !$content['anonymous'] ) {
					unset( $portals['MAINMENU'][$name] );
				}
			}
		} else {
			// remove logged items
			foreach ( $portals['MAINMENU'] as $name => $content ) {
				if ( isset( $content['anonymous'] ) && $content['anonymous'] ) {
					unset( $portals['MAINMENU'][$name] );
				}
			}
			// Force the rendering of the following portals
			if ( !isset( $portals['TOOLBOX'] ) ) {
				$portals['TOOLBOX'] = true;
			}
			if ( !isset( $portals['LANGUAGES'] ) ) {
				$portals['LANGUAGES'] = true;
			}
			$portals = array_merge( $this->get( 'sidebar', [] ), $portals );
		}

		// Render portals
		foreach ( $portals as $name => $content ) {
			if ( $content === false ) {
				continue;
			}

			// Numeric strings gets an integer when set as key, cast back - T73639
			$name = (string)$name;

			switch ( $name ) {
				case 'SEARCH':
					break;
				case 'TOOLBOX':
					$portal = $this->getMenuData(
						'specialtools',
						$this->data['sidebar']['TOOLBOX'],
						self::MENU_TYPE_PORTAL, [
							'tag' => 'li',
						]
					);
					$props[] = $portal;
					break;
				case 'LANGUAGES':
					$languages = $skin->getLanguages();
					$portal = $this->getMenuData(
						'lang',
						$languages,
						self::MENU_TYPE_PORTAL, [
							'tag' => 'li',
						]
					);
					// The language portal will be added provided either
					// languages exist or there is a value in html-after-portal
					// for example to show the add language wikidata link (T252800)
					if ( count( $languages ) || $portal['html-after-portal'] ) {
						$props[] = $portal;
					}
					break;
				default:
					// Historically some portals have been defined using HTML rather than arrays.
					// Let's move away from that to a uniform definition.
					if ( !is_array( $content ) ) {
						$html = $content;
						$content = [];
						wfDeprecated(
							"`content` field in portal $name must be array."
								. "Previously it could be a string but this is no longer supported.",
							'1.35.0'
						);
					} else {
						$html = false;
					}
					$portal = $this->getMenuData(
						$name,
						$content,
						self::MENU_TYPE_PORTAL, [
							'tag' => 'li',
						]
					);
					if ( $html ) {
						$portal['html-items'] .= $html;
					}
					$props[] = $portal;
					break;
			}

		}

		$html = '';
		foreach ( $props as $name => $content ) {
			if ( trim($content['html-items']) === '' ) {
				unset( $props[$name] );
				continue;
			}
			if ( trim($html) === '' ) {
				$html = Html::rawElement(
					'a', [
						'class' => 'nav-link active',
						'href' => '#' . $content['id'],
					], $this->Msg( $content['id'] )
				);
			} else {
				$html .= Html::rawElement(
					'a', [
						'class' => 'nav-link',
						'href' => '#' . $content['id'],
					], $this->Msg( $content['id'] )
				);
			}
		};

		return [
			'html-sidebar-aside' => $html,
			'array-sidebar-body' => array_values( $props ),
		];
	}

	/**
	 * @param string $label to be used to derive the id and human readable label of the menu
	 *  If the key has an entry in the constant MENU_LABEL_KEYS then that message will be used for the
	 *  human readable text instead.
	 * @param array $urls to convert to list items stored as string in html-items key
	 * @param int $type of menu (optional) - a plain list (MENU_TYPE_DEFAULT),
	 *   a tab (MENU_TYPE_TABS) or a dropdown (MENU_TYPE_DROPDOWN)
	 * @param array $options (optional) to be passed to makeListItem
	 * @param bool $setLabelToSelected (optional) the menu label will take the value of the
	 *  selected item if found.
	 * @return array
	 */
	private function getMenuData(
		string $label,
		array $urls = [],
		int $type = self::MENU_TYPE_DEFAULT,
		array $options = [],
		bool $setLabelToSelected = false
	) : array {
		$extraClasses = [
			self::MENU_TYPE_DROPDOWN => 'dropdown mw-profile-menu',
			self::MENU_TYPE_TABS => 'nav',
			self::MENU_TYPE_PORTAL => 'mw-sidebar-pane',
			self::MENU_TYPE_DEFAULT => '',
		];
		// A list of classes to apply the list element and override the default behavior.
		$listClasses = [
			// `.menu` is on the portal for historic reasons.
			// It should not be applied elsewhere per T253329.
			self::MENU_TYPE_DROPDOWN => '',
		];
		$isPortal = self::MENU_TYPE_PORTAL === $type;

		// For some menu items, there is no language key corresponding with its menu key.
		// These inconsitencies are captured in MENU_LABEL_KEYS
		// $msgObj = $this->msg( self::MENU_LABEL_KEYS[ $label ] ?? $label );
		$msgObj = strtolower( preg_replace( '/[^a-zA-Z0-9]/s', '', $label ) );
		$props = [
			'id' => $msgObj,
			'label-id' => "{$msgObj}-label",
			'label' => $this->Msg( $msgObj . '-label' ),
			'label-msg' => $this->Msg(  $msgObj . '-msg' ),
			'html-userlangattributes' => $this->get( 'userlangattributes', '' ),
			'list-classes' => $listClasses[$type] ?? '',
			'html-items' => '',
			'is-dropdown' => self::MENU_TYPE_DROPDOWN === $type,
			'is-portal' => self::MENU_TYPE_PORTAL === $type,
			'is-tabs' => self::MENU_TYPE_TABS === $type,
			'html-tooltip' => Linker::tooltip( $label ),
			'html-menu-prebody' => $options['html-prebody'] ?? null,
		];

		foreach ( $urls as $key => $item ) {
			// Add CSS class 'collapsible' to all links EXCEPT watchstar.
			if (
				$key !== 'watch' && $key !== 'unwatch' ) {
				if ( !isset( $item['class'] ) ) {
					$item['class'] = '';
				}
				$item['class'] = rtrim( 'nav-item ' . $item['class'], ' ' );
			} else {
				$item['class'] = rtrim( 'nav-item ' . $item['class'], ' ' );
			}

			if ( !isset( $options['link-class'] ) ) {
				$options['link-class'] = 'nav-link';
			} else {
				$options['link-class'] = rtrim( 'nav-link ' . $options['link-class'], ' ' );
			}

			// send $label to $options for getMsg() identify
			$options['menu-id'] = strtolower( preg_replace( '/[^a-zA-Z0-9]/s', '', $label ) );
			$props['html-items'] .= $this->makeListItems( $key, $item, $options );

			// Check the class of the item for a `selected` class and if so, propagate the items
			// label to the main label.
			if ( $setLabelToSelected ) {
				if ( isset( $item['class'] ) && stripos( $item['class'], 'selected' ) !== false ) {
					$props['label'] = $item['text'];
				}
			}
		}

		$props['html-after-portal'] = $isPortal ? $this->getSkin()->getAfterPortlet( $label ) : '';

		// Mark the portal as empty if it has no content
		$class = ( count( $urls ) == 0 && !$props['html-after-portal'] )
			? 'library-menu-empty emptyPortlet' : '';
		$props['class'] = trim( "$class $extraClasses[$type]" );
		return $props;
	}

	/**
	 * @return array
	 */
	private function getMenuProps() : array {
		$skin = $this->getSkin();
		$portals = $this->getPersonalTools();
		$notification = [ 'notifications-alert', 'notifications-notice' ];
		$props = [];
		// $options = [ 'link-class' => 'dropdown-item' ];
		$contentNavigation = $this->get( 'content_navigation', [] );

		if ( $skin->getUser()->isAnon() ) {
			$props = $this->getMenuData(
				'usermenu',
				$portals,
				self::MENU_TYPE_TABS, [
					'tag' => 'li',
					'link-class' => 'btn btn-primary btn-with-icon btn-rounded',
				]
			);

			if ( array_key_exists( 'views', $contentNavigation ) ) {
				unset( $contentNavigation['views'] );
			};

			foreach ( $notification as $k ) {
				if ( array_key_exists( $k, $portals ) ) {
					unset( $portals[$k] );
				}
			}

		} else {
			if ( array_key_exists( 'userpage', $portals ) ) {
				$html = '<img src="https://ui-avatars.com/api/?length=2&size=80&rounded=true&name=' . str_replace( '.', '+', $portals['userpage']['links'][0]['text'] ) . '">';
				$html = Html::rawElement(
					'div', [
						'class' => '',
					], $html 
				);
				$html .= Html::rawElement(
					'h6', [], Html::rawElement(
						'a', [
							'href' => $portals['userpage']['links'][0]['href'],
							'class' => [ 'nav-link' ],
							'title' => Linker::titleAttrib( $portals['userpage']['links'][0]['single-id'] ),
						], $portals['userpage']['links'][0]['text']
					)
				);
				$html = Html::rawElement(
					'div', [
						'class' => 'mw-topbar-profile',
					], $html 
				);
				unset( $portals['userpage'] );
			}

			/* TODO: 
			 *  1. add notification links - output {{{html-personal-notification}}}
			 *  2. Watch / unWatch icon
			 *  3. and update Logout link
			if ( isset( $portals['logout'] ) ) {
				$portals['logout']['links'][0]['href'] = '/logout/';
			}
			*/

			$props = $this->getMenuData(
				'usermenu',
				$portals,
				self::MENU_TYPE_DROPDOWN, [
					'link-class' => 'dropdown-item',
					'html-prebody' => $html,
				]
			);

			$contentNavigation['views']['more'] = [
				'text' => 'More',
				'href' => '#',
				'id' => 'ca-more',
			];
		}

		return [
			'data-personal-menu' => $props,
			'data-namespace-tabs' => $this->getMenuData(
				'namespaces',
				$contentNavigation[ 'namespaces' ] ?? [],
				self::MENU_TYPE_TABS, [
					'tag' => 'li',
				]
			),
			'data-variants' => $this->getMenuData(
				'variants',
				$contentNavigation[ 'variants' ] ?? [],
				self::MENU_TYPE_DROPDOWN,
				[], true
			),
			'data-page-actions' => $this->getMenuData(
				'views',
				$contentNavigation[ 'views' ] ?? [],
				self::MENU_TYPE_TABS, [
					'tag' => 'li',
				]
			),
			'data-page-actions-more' => $this->getMenuData(
				'cactions',
				$contentNavigation[ 'actions' ] ?? [],
				self::MENU_TYPE_DROPDOWN
			),
		];
	}

	/**
	 * @return array
	 */
	private function buildSearchProps() : array {
		$config = $this->getConfig();
		$props = [
			'form-action' => $config->get( 'Script' ),
			'form-id' => $config->get( 'LibraryUseSimpleSearch' ) ? 'simpleSearch' : '',
			'html-button-search-fallback' => $this->makeSearchButton(
				'fulltext',
				[ 'id' => 'mw-searchButton', 'class' => 'searchButton mw-fallbackSearchButton d-none' ]
			),
			'html-button-search' => $this->makeSearchButton(
				'go',
				[ 'id' => 'btn', 'class' => 'searchButton' ]
			),
			'html-input' => $this->makeSearchInput( [
				'id' => 'searchInput',
				'class' => 'form-control',
				'placeholder' => 'Search for anything ...'
				] ),
			'msg-search' => $this->msg( 'search' ),
			'page-title' => SpecialPage::getTitleFor( 'Search' )->getPrefixedDBkey(),
		];
		return $props;
	}

	/**
	 * Generates a list link for a navigation, portal, sidebar ...
	 * @param string $key - Usually a key from the list that generating this link from.
	 * @param array $item - Contains some of a specific set of keys
	 * @param array $options (optional) -Can be use to affect the output of a link.
	 * 
	 * @return string
	 */
	private function makeLinks( $key, $item, $options = [] ) {
		$msgObj = $options['menu-id'] . '-' . strtolower( preg_replace( '/[^a-zA-Z0-9]/s', '', $item['msg'] ?? $item['text'] ?? $item['single-id'] ) );
		$html =  $this->msg( $msgObj )->text() ?? $item['text'];

		if ( isset( $options['text-wrapper'] ) ) {
			$wrapper = $options['text-wrapper'];
			if ( isset( $wrapper['tag'] ) ) {
				$wrapper = [ $wrapper ];
			}
			while ( count( $wrapper ) > 0 ) {
				$element = array_pop( $wrapper );
				$html = Html::rawElement( $element['tag'], $element['attributes'] ?? null, $html );
			}
		}
 
		if ( isset( $item['href'] ) || isset( $options['link-fallback'] ) ) {
			$attrs = $item;
			foreach ( [ 'single-id', 'text', 'msg', 'tooltiponly', 'context', 'primary',
				'tooltip-params', 'exists' ] as $k ) {
				unset( $attrs[$k] );
			}
 
			if ( isset( $attrs['data'] ) ) {
				foreach ( $attrs['data'] as $key => $value ) {
					$attrs[ 'data-' . $key ] = $value;
				}
				unset( $attrs[ 'data' ] );
			}
 
			if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
				$item['single-id'] = $item['id'];
			}
 
			$tooltipParams = [];
			if ( isset( $item['tooltip-params'] ) ) {
				$tooltipParams = $item['tooltip-params'];
			}
 
			if ( isset( $item['single-id'] ) ) {
				$tooltipOption = isset( $item['exists'] ) && $item['exists'] === false ? 'nonexisting' : null;
 
				if ( isset( $item['tooltiponly'] ) && $item['tooltiponly'] ) {
					$title = Linker::titleAttrib( $item['single-id'], $tooltipOption, $tooltipParams );
					if ( $title !== false ) {
						$attrs['title'] = $title;
					}
				} else {
					$tip = Linker::tooltipAndAccesskeyAttribs(
						$item['single-id'],
						$tooltipParams,
						$tooltipOption
					);
					if ( isset( $tip['title'] ) && $tip['title'] !== false ) {
						$attrs['title'] = $tip['title'];
					}
					if ( isset( $tip['accesskey'] ) && $tip['accesskey'] !== false ) {
						$attrs['accesskey'] = $tip['accesskey'];
					}
				}
			}
			if ( isset( $options['link-class'] ) ) {
				if ( isset( $attrs['class'] ) && isset( $item['text'] ) ) {
					$className = " {$options['link-class']} {$key} {$item['text']}";
					if ( is_string( $attrs['class'] ) ) {
						$attrs['class'] .= $className;
					} else {
						$attrs['class'][] = $className;
					}
				} else {
					$attrs['class'] = $options['link-class'];
				}
			}
			$html = Html::rawElement( isset( $attrs['href'] )
				? 'a'
				: $options['link-fallback'], $attrs, $html );
		}
 
		return $html;
	}

	/**
	 * Generate a list item for a navigation, portal, sidebar ...
	 * @param string $key - usually a key from the list that generating this link from 
	 * @param array $item - list item data containing some of a specific set of keys. 
	 *  The 'id', 'class' and 'itemtitle' keys will be used as attributes for the list item,
	 *   if 'active' contains a value of true a "active" class will also be appened to class.
	 * @param array $options (optional) - will be pass to makeLinks function
	 * 
	 * @return string
	 */
	private function makeListItems( $key, $item, $options = [] ) {
		// In case this is still set from SkinTemplate, we don't want it to appear in
        // the HTML output (normally removed in SkinTemplate::buildContentActionUrls())
         unset( $item['redundant'] );
  
         if ( isset( $item['links'] ) ) {
             $links = [];
             foreach ( $item['links'] as $linkKey => $link ) {
                 $links[] = $this->makeLinks( $linkKey, $link, $options );
             }
             $html = implode( ' ', $links );
         } else {
             $link = $item;
             // These keys are used by makeListItem and shouldn't be passed on to the link
             foreach ( [ 'id', 'class', 'active', 'tag', 'itemtitle' ] as $k ) {
                 unset( $link[$k] );
             }
             if ( isset( $item['id'] ) && !isset( $item['single-id'] ) ) {
                 // The id goes on the <li> not on the <a> for single links
                 // but makeSidebarLink still needs to know what id to use when
                 // generating tooltips and accesskeys.
                 $link['single-id'] = $item['id'];
             }
             if ( isset( $link['link-class'] ) ) {
                 // link-class should be set on the <a> itself,
                 // so pass it in as 'class'
                 $link['class'] = $link['link-class'];
                 unset( $link['link-class'] );
			 }

             $html = $this->makeLinks( $key, $link, $options );
         }
  
         $attrs = [];
         foreach ( [ 'id', 'class' ] as $attr ) {
             if ( isset( $item[$attr] ) ) {
                 $attrs[$attr] = $item[$attr];
             }
         }
         if ( isset( $item['active'] ) && $item['active'] ) {
             if ( !isset( $attrs['class'] ) ) {
                 $attrs['class'] = '';
             }
             $attrs['class'] .= ' active';
             $attrs['class'] = trim( $attrs['class'] );
         }
         if ( isset( $item['itemtitle'] ) ) {
             $attrs['title'] = $item['itemtitle'];
		 }

		 if ( !isset( $options['tag'] ) ) {
			 return $html;
		 } else {
			 return Html::rawElement( $options['tag'] ?? 'li', $attrs, $html );
		 }
	}
}