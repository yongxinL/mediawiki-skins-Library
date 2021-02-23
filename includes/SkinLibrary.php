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
class SkinLibrary extends SkinTemplate {

	/**
	 * @var templateParser|null
	 */
	private $templateParser = null;

	/**
	 * Get the template parser, it will be lazily created if not already set.
	 *
	 * @return templateParser
	 */
	protected function getTemplateParser() {

		if ( $this->templateParser === null ) {
			$this->templateParser = new TemplateParser( __DIR__ . '/templates' );
		}
		return $this->templateParser;
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
	 * @return array data for Mustache template.
	 */
	public function getTemplateData() {
		$out = $this->getOutput();
		$printSource = Html::rawElement( 'div', [ 'class' => 'printfooter' ], $this->printSource() );
		$bodyContent = $out->getHTML() . "\n" . $printSource;

		$commonSkinData = [
			'page-isarticle' => (bool)$out->isArticle(),
			// array objects
			'array-indicators' => $this->getIndicatorsData( $out->getIndicators() ),

			// data objects
			'data-search-box' => '',

			// HTML strings
			'html-site-notice' => $this->getSiteNotice() ?: null,
			'html-title' => strpos( $out->getPageTitle(), 'history' ) ? $out->getPageTitle() : basename( $out->getPageTitle() ), 
			'html-subtitle' => $this->prepareSubtitle(),
			'html-body-content' => $this->wrapHTML( $out->getTitle(), $bodyContent ),
			'html-categories' => $this->getCategories(),
			'html-after-content' => $this->afterContentHook(),
			'html-undelete-link' => $this->prepareUndeleteLink(),
			'html-user-language-attributes' => $this->prepareUserLanguageAttributes(),

			// links
			'link-mainpage' => Title::newMainPage()->getLocalUrl(),
		];

		foreach ( $this->options['messages'] ?? [] as $message ) {
			$data["msg-{$message}"] = $this->msg( $message )->text();
		}

		// print_r( $this->getPortletsTemplateData() );
		return $commonSkinData;
	}

	/**
	 * Render the associated template. The master template is assumed
	 * to be 'skin' unless 'template has been passed in the skin options
	 *
	 * @return void
	 */
	public function generateHTML() {
		$this->setupTemplateContext();
		$out = $this->getOutput();
		$tp = $this->getTemplateParser();
		$template = $this->options['template'] ?? 'skin';
		$data = $this->getTemplateData();

		// T259955: OutputPage::headElement must be called last (after getTemplateData)
		$html = $out->headElement( $this );
		$html .= $tp->processTemplate( $template, $data );
		$html .= $this->tailElement( $out );
		return $html;
	}

	/**
	 * The final bits that go to the bottom of a page.
	 * HTML document including the closing tags.
	 *
	 * @param OutputPage $out
	 * @return string
	 */
	private function tailElement( $out ) {
		$tail = [
			MWDebug::getDebugHTML( $this ),
			$this->bottomScripts(),
			wfReportTime( $out->getCSP()->getNonce() ),
			MWDebug::getHTMLDebugLog()
			. Html::closeElement( 'body' )
			. Html::closeElement( 'html' )
		];
		return WrappedStringList::join( "\n", $tail );
	}
}
