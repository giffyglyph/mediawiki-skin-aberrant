<?php
/**
 * BaseTemplate class for the Aberrant skin
 *
 * @ingroup Skins
 */
class AberrantTemplate extends BaseTemplate {

	/**
	 * Outputs the entire contents of the page
	 */
	public function execute() {
		$this->sidebar = $this->getSidebar();
		$this->pileOfTools = $this->getPageTools();
		$this->categories = $this->data['skin']->getTitle()->getParentCategories();
		$userLinks = $this->getUserLinks();
		$navigation = $this->getSearch() .
			Html::rawElement( 'div', [ 'class' => 'collapse bd-links', 'id' => 'bd-navigation-menu' ],
				$this->getMainNavigation() .
				$this->getPortlet('tb', $this->pileOfTools['general'], 'aberrant-sitetools')
			);
		$actions = $this->getPortlet('views', $this->pileOfTools['page-primary'], 'aberrant-pagetools') .
			$this->getPortlet('cactions', $this->pileOfTools['page-secondary'], 'aberrant-pageactions') .
			$this->getPortlet('namespaces', $this->pileOfTools['namespaces'], 'aberrant-namespaces') .
			$this->getPortlet('more', $this->pileOfTools['more'], 'aberrant-more') .
			$this->getPortlet('userpagetools', $this->pileOfTools['user'], 'aberrant-userpagetools') .
			$this->getPortlet('pagemisc', $this->pileOfTools['page-tertiary'], 'aberrant-pagemisc') .
			$this->getCategories();

		$html = $this->get( 'headelement' );
		$html .= Html::rawElement( 'div', [ 'id' => 'mw-wrapper', 'class' => $userLinks['class'] ],
			Html::rawElement( 'div', [ 'id' => 'mw-header-container' ],
				Html::rawElement( 'div', [ 'id' => 'mw-header' ],
					$this->getBrand() . $userLinks['html']
				)
			) .
			Html::rawElement( 'div', [ 'id' => 'mw-navigation-container' ],
				Html::rawElement( 'div', [ 'id' => 'mw-navigation', 'class' => 'bd-sidebar' ], $navigation )
			) .
			Html::rawElement( 'div', [ 'id' => 'mw-content-container' ],
				$this->getContentBlock($actions)
			) .
			Html::rawElement( 'div' , [ 'id' => 'mw-actions-container' ],
				Html::rawElement( 'div', [ 'id' => 'mw-actions', 'class' => 'page-actions' ], $actions )
			)
		);

		// BaseTemplate::printTrail() stuff (has no get version)
		// Required for RL to run
		$html .= MWDebug::getDebugHTML( $this->getSkin()->getContext() );
		$html .= $this->get( 'bottomscripts' );
		$html .= $this->get( 'reporttime' );

		$html .= Html::closeElement( 'body' );
		$html .= Html::closeElement( 'html' );

		// The unholy echo
		echo $html;
	}

	/**
	 * Generate the page content block
	 * Broken out here due to the excessive indenting, or stuff.
	 *
	 * @return string html
	 */
	protected function getContentBlock($actions) {
		return Html::rawElement( 'div', [ 'id' => 'mw-content', 'role' => 'main' ],
			$this->getSiteNotices() .
			$this->getIndicators() .
			Html::rawElement( 'div', [ 'class' => 'content-heading' ],
				Html::rawElement( 'h1', [ 'lang' => $this->get( 'pageLanguage' ) ],
					$this->get( 'title' )
				) .
				$this->getBootstrapDropdown( 'page-options', $actions, 'aberrant-options', [ 'menu-extra-classes' => 'dropdown-menu-right page-actions' ] )
			) .
			$this->getContentInfo() .
			Html::rawElement( 'div', [ 'class' => 'content-body' ],
				$this->get( 'bodytext' )
			) .
			$this->getAfterContent() .
			Html::rawElement( 'div', [ 'class' => 'content-footer' ],
				$this->getFooter()
			)
		);
	}

	/**
	 * Generates a block of navigation links with a header
	 * This is some random fork of some random fork of what was supposed to be in core. Latest
	 * version copied out of MonoBook, probably. (20190719)
	 *
	 * @param string $name
	 * @param array|string $content array of links for use with makeListItem, or a block of text
	 *        Expected array format:
	 * 	[
	 * 		$name => [
	 * 			'links' => [ '0' =>
	 * 				[
	 * 					'href' => ...,
	 * 					'single-id' => ...,
	 * 					'text' => ...
	 * 				]
	 * 			],
	 * 			'id' => ...,
	 * 			'active' => ...
	 * 		],
	 * 		...
	 * 	]
	 * @param null|string|array|bool $msg
	 * @param array $setOptions miscellaneous overrides, see below
	 *
	 * @return string html
	 */
	protected function getPortlet( $name, $content, $msg = null, $setOptions = [] ) {
		// random stuff to override with any provided options
		$options = array_merge( [
			'role' => 'navigation',
			// extra classes/ids
			'id' => 'p-' . $name,
			'class' => [ 'mw-portlet', 'emptyPortlet' => !$content ],
			'extra-classes' => '',
			'body-id' => null,
			'body-class' => 'mw-portlet-body',
			'body-extra-classes' => '',
			// wrapper for individual list items
			'text-wrapper' => [ 'tag' => 'span' ],
			// option to stick arbitrary stuff at the beginning of the ul
			'list-prepend' => ''
		], $setOptions );

		// Handle the different $msg possibilities
		if ( $msg === null ) {
			$msg = $name;
			$msgParams = [];
		} elseif ( is_array( $msg ) ) {
			$msgString = array_shift( $msg );
			$msgParams = $msg;
			$msg = $msgString;
		} else {
			$msgParams = [];
		}
		$msgObj = $this->getMsg( $msg, $msgParams );
		if ( $msgObj->exists() ) {
			$msgString = $msgObj->parse();
		} else {
			$msgString = htmlspecialchars( $msg );
		}

		$labelId = Sanitizer::escapeIdForAttribute( "p-$name-label" );

		if ( is_array( $content ) ) {
			$contentText = Html::openElement( 'ul',
				[ 'lang' => $this->get( 'userlang' ), 'dir' => $this->get( 'dir' ) ]
			);
			$contentText .= $options['list-prepend'];
			foreach ( $content as $key => $item ) {
				if ( is_array( $options['text-wrapper'] ) ) {
					$contentText .= $this->makeListItem(
						$key,
						$item,
						[ 'text-wrapper' => $options['text-wrapper'] ]
					);
				} else {
					$contentText .= $this->makeListItem(
						$key,
						$item
					);
				}
			}
			$contentText .= Html::closeElement( 'ul' );
		} else {
			$contentText = $content;
		}

		$divOptions = [
			'role' => $options['role'],
			'class' => $this->mergeClasses( $options['class'], $options['extra-classes'] ),
			'id' => Sanitizer::escapeIdForAttribute( $options['id'] ),
			'title' => Linker::titleAttrib( $options['id'] ),
			'aria-labelledby' => $labelId
		];
		$labelOptions = [
			'id' => $labelId,
			'lang' => $this->get( 'userlang' ),
			'dir' => $this->get( 'dir' )
		];

		$bodyDivOptions = [
			'class' => $this->mergeClasses( $options['body-class'], $options['body-extra-classes'] )
		];
		if ( is_string( $options['body-id'] ) ) {
			$bodyDivOptions['id'] = $options['body-id'];
		}

		$html = Html::rawElement( 'div', $divOptions,
			Html::rawElement( 'h3', $labelOptions, $msgString ) .
			Html::rawElement( 'div', $bodyDivOptions,
				$contentText .
				$this->getAfterPortlet( $name )
			)
		);

		return $html;
	}

	protected function getBootstrapDropdown( $name, $content, $msg = null, $setOptions = [] ) {
		// random stuff to override with any provided options
		$options = array_merge( [
			'id' => 'bd-' . $name,
			'class' => [ 'dropdown', 'empty' => !$content ],
			'menu-class' => 'dropdown-menu',
			'menu-extra-classes' => '',
		], $setOptions );

		// Handle the different $msg possibilities
		if ( $msg === null ) {
			$msg = $name;
			$msgParams = [];
		} elseif ( is_array( $msg ) ) {
			$msgString = array_shift( $msg );
			$msgParams = $msg;
			$msg = $msgString;
		} else {
			$msgParams = [];
		}
		$msgObj = $this->getMsg( $msg, $msgParams );
		if ( $msgObj->exists() ) {
			$msgString = $msgObj->parse();
		} else {
			$msgString = htmlspecialchars( $msg );
		}

		$labelId = Sanitizer::escapeIdForAttribute( "p-$name-label" );
		$contentText = Html::openElement( 'div', [ 'class' => $this->mergeClasses( $options['menu-class'], $options['menu-extra-classes'] ) ]);
		if ( is_array( $content ) ) {
			foreach ( $content as $key => $item ) {
				foreach ( $item["links"] as $link ) {
					$contentText .= Html::rawElement( 'a', [ 'class' => 'dropdown-item', 'href' => $link["href"], 'id' => $link["single-id"]], $link["text"] );
				}
			}
		} else {
			$contentText .= $content;
		}
		$contentText .= Html::closeElement( 'div' );

		$labelOptions = [
			'id' => $labelId,
			'lang' => $this->get( 'userlang' ),
			'dir' => $this->get( 'dir' ),
			'class' => 'btn btn-secondary dropdown-toggle',
			'type' => 'button',
			'data-toggle' => 'dropdown'
		];

		$html = Html::rawElement( 'div', [ 'class' => $options['class'] ],
			Html::rawElement( 'button', $labelOptions,
				Html::rawElement( 'span', [], $msgString )
			) .
			$contentText
		);

		return $html;
	}

	/**
	 * Helper function for getPortlet
	 *
	 * Merge all provided css classes into a single array
	 * Account for possible different input methods matching what Html::element stuff takes
	 *
	 * @param string|array $class base portlet/body class
	 * @param string|array $extraClasses any extra classes to also include
	 *
	 * @return array all classes to apply
	 */
	protected function mergeClasses( $class, $extraClasses ) {
		if ( !is_array( $class ) ) {
			$class = [ $class ];
		}
		if ( !is_array( $extraClasses ) ) {
			$extraClasses = [ $extraClasses ];
		}

		return array_merge( $class, $extraClasses );
	}

	/**
	 * The logo and (optionally) site title
	 *
	 * @param string $id
	 * @param string $part whether it's only image, only text, or both
	 *
	 * @return string html
	 */
	protected function getBrand( $id = 'p-logo', $part = 'both' ) {
		$html = '';
		$language = $this->getSkin()->getLanguage();
		$config = $this->getSkin()->getContext()->getConfig();

		$html .= Html::openElement(
			'div',
			[
				'id' => Sanitizer::escapeIdForAttribute( $id ),
				'class' => 'mw-portlet',
				'role' => 'banner'
			]
		);
		if ( $language->hasVariants() ) {
			$siteTitle = $language->convert( $this->getMsg( 'timeless-sitetitle' )->escaped() );
		} else {
			$siteTitle = $this->getMsg( 'timeless-sitetitle' )->escaped();
		}

		$html .= Html::rawElement( 'a', [
				'id' => 'p-banner',
				'href' => $this->data['nav_urls']['mainpage']['href']
			],
			$siteTitle
		);
		$html .= Html::closeElement( 'div' );

		return $html;
	}

	/**
	 * The search box at the top
	 *
	 * @return string html
	 */
	protected function getSearch() {
		return Html::rawElement( 'div', [ 'class' => 'mw-portlet', 'id' => 'p-search' ],
			Html::rawElement( 'form', [ 'action' => $this->get( 'wgScript' ) ],
				Html::rawElement( 'div', [ 'class' => 'input-group' ],
					$this->makeSearchInput( [
						'placeholder' => $this->getMsg( 'aberrant-search-placeholder' )->text(),
						'class' => 'form-control'
					] ) .
					Html::rawElement( 'div', [ 'class' => 'input-group-append' ],
						Html::rawElement( 'button', [ 'class' => 'btn btn-search', 'type' => 'submit' ], '<i class="fas fa-search"></i>')
					)
				) .
				Html::rawElement( 'button', [ 'class' => 'btn btn-menu', 'type' => 'button', 'data-toggle' => 'collapse', 'data-target' => '#bd-navigation-menu', 'aria-controls' => 'bd-navigation-menu', 'aria-expanded' => 'false', 'aria-label' => 'Toggle navigation'], '<i class="fas fa-bars"></i>')
			)
		);
	}

	/**
	 * Left sidebar navigation, usually
	 *
	 * @return string html
	 */
	protected function getMainNavigation() {
		$html = '';

		// Already hardcoded into header
		$this->sidebar['SEARCH'] = false;
		// Parsed as part of pageTools
		$this->sidebar['TOOLBOX'] = false;
		// Forcibly removed to separate chunk
		$this->sidebar['LANGUAGES'] = false;

		foreach ( $this->sidebar as $name => $content ) {
			if ( $content === false ) {
				continue;
			}
			// Numeric strings gets an integer when set as key, cast back - T73639
			$name = (string)$name;
			$html .= $this->getPortlet( $name, $content['content'] );
		}

		return $html;
	}

	/**
	 * Page tools in sidebar
	 *
	 * @return string html
	 */
	protected function getPageToolSidebar() {
		$pageTools = '';
		$pageTools .= $this->getPortlet(
			'cactions',
			$this->pileOfTools['page-secondary'],
			'aberrant-pageactions'
		);
		$pageTools .= $this->getPortlet(
			'userpagetools',
			$this->pileOfTools['user'],
			'aberrant-userpagetools'
		);
		$pageTools .= $this->getPortlet(
			'pagemisc',
			$this->pileOfTools['page-tertiary'],
			'aberrant-pagemisc'
		);
		if ( isset( $this->collectionPortlet ) ) {
			$pageTools .= $this->getPortlet(
				'coll-print_export',
				$this->collectionPortlet['content']
			);
		}

		return $pageTools;
	}

	/**
	 * Personal/user links portlet for header
	 *
	 * @return array [ html, class ], where class is an extra class to apply to surrounding objects
	 * (for width adjustments)
	 */
	protected function getUserLinks() {
		$user = $this->getSkin()->getUser();
		$personalTools = $this->getPersonalTools();
		// Preserve standard username label to allow customisation (T215822)
		$userName = $personalTools['userpage']['links'][0]['text'] ?? $user->getName();

		$html = '';
		$extraTools = [];

		// Remove Echo badges
		if ( isset( $personalTools['notifications-alert'] ) ) {
			$extraTools['notifications-alert'] = $personalTools['notifications-alert'];
			unset( $personalTools['notifications-alert'] );
		}
		if ( isset( $personalTools['notifications-notice'] ) ) {
			$extraTools['notifications-notice'] = $personalTools['notifications-notice'];
			unset( $personalTools['notifications-notice'] );
		}
		$class = empty( $extraTools ) ? '' : 'extension-icons';

		// Re-label some messages
		if ( isset( $personalTools['userpage'] ) ) {
			$personalTools['userpage']['links'][0]['text'] = $this->getMsg( 'aberrant-userpage' )->text();
		}
		if ( isset( $personalTools['mytalk'] ) ) {
			$personalTools['mytalk']['links'][0]['text'] = $this->getMsg( 'aberrant-talkpage' )->text();
		}

		// Labels
		if ( $user->isLoggedIn() ) {
			$dropdownHeader = $userName;
			$headerMsg = [ 'aberrant-loggedinas', $userName ];
		} else {
			$dropdownHeader = $this->getMsg( 'aberrant-anonymous' )->text();
			$headerMsg = 'aberrant-notloggedin';
		}
		$html .= Html::openElement( 'div', [ 'id' => 'user-tools' ] );

		$html .= Html::rawElement( 'div', [ 'id' => 'personal' ],
			Html::rawElement( 'h2', [],
				Html::element( 'span', [], $dropdownHeader )
			) .
			Html::rawElement( 'div', [ 'id' => 'personal-inner' ],
				$this->getBootstrapDropdown( 'personal', $personalTools, $headerMsg, [ 'menu-extra-classes' => 'dropdown-menu-right' ] )
			)
		);

		// Extra icon stuff (echo etc)
		if ( !empty( $extraTools ) ) {
			$iconList = '';
			foreach ( $extraTools as $key => $item ) {
				$iconList .= $this->makeListItem( $key, $item );
			}

			$html .= Html::rawElement(
				'div',
				[ 'id' => 'personal-extra', 'class' => 'p-body' ],
				Html::rawElement( 'ul', [], $iconList )
			);
		}

		$html .= Html::closeElement( 'div' );

		return [
			'html' => $html,
			'class' => $class
		];
	}

	/**
	 * Notices that may appear above the firstHeading
	 *
	 * @return string html
	 */
	protected function getSiteNotices() {
		$html = '';

		if ( $this->data['sitenotice'] ) {
			$html .= Html::rawElement( 'div', [ 'id' => 'siteNotice' ], $this->get( 'sitenotice' ) );
		}
		if ( $this->data['newtalk'] ) {
			$html .= Html::rawElement( 'div', [ 'class' => 'usermessage' ], $this->get( 'newtalk' ) );
		}

		return $html;
	}

	/**
	 * Links and information that may appear below the firstHeading
	 *
	 * @return string html
	 */
	protected function getContentInfo() {
		$html = '';

		if ( $this->data['subtitle'] ) {
			$html .= $this->get( 'subtitle' );
		}
		if ( $this->data['undelete'] ) {
			$html .= $this->get( 'undelete' );
		}

		return ($html == '') ? '' : Html::rawElement( 'div', [ 'class' => 'content-info' ], $html);
	}

	/**
	 * The data after content, catlinks, and potential other stuff that may appear within
	 * the content block but after the main content
	 *
	 * @return string html
	 */
	protected function getAfterContent() {
		$html = '';

		if ( $this->data['catlinks'] && sizeof($this->categories) > 0 ) {
			$html .= $this->get( 'catlinks' );
		}
		if ( $this->data['dataAfterContent'] ) {
			$html .= $this->get( 'dataAfterContent' );
		}

		return ($html == '') ? '' : Html::rawElement( 'div', [ 'class' => 'content-additional-container' ],
			Html::rawElement( 'div', [ 'class' => 'content-additional' ],
				$html
			)
		);
	}

	/**
	 * Generate pile of all the tools
	 *
	 * We can make a few assumptions based on where a tool started out:
	 *     If it's in the cactions region, it's a page tool, probably primary or secondary
	 *     ...that's all I can think of
	 *
	 * @return array of array of tools information (portlet formatting)
	 */
	protected function getPageTools() {
		$title = $this->getSkin()->getTitle();
		$namespace = $title->getNamespace();
		$sortedPileOfTools = [
			'namespaces' => [],
			'page-primary' => [],
			'page-secondary' => [],
			'user' => [],
			'page-tertiary' => [],
			'more' => [],
			'general' => []
		];

		// Tools specific to the page
		$pileOfEditTools = [];
		foreach ( $this->data['content_navigation'] as $navKey => $navBlock ) {
			// Put namespaces into page-tertiary
			if ( $navKey == 'namespaces' ) {
				if (array_key_exists('main', $navBlock)) {
					$msg = $this->getMsg( 'aberrant-view-view' );
					if ( $msg->exists() ) {
						$navBlock['main']['text'] = $msg->parse();
					} else {
						$navBlock['main']['text'] = 'Read';
					}
				}
				$sortedPileOfTools['page-primary'] = $navBlock;
			} elseif ( $navKey == 'variants' ) {
				// wat
				$sortedPileOfTools['variants'] = $navBlock;
			} else {
				$pileOfEditTools = array_merge( $pileOfEditTools, $navBlock );
			}
		}

		// Tools that may be general or page-related (typically the toolbox)
		$pileOfTools = $this->getToolbox();
		if ( $namespace >= 0 ) {
			$pileOfTools['pagelog'] = [
				'text' => $this->getMsg( 'aberrant-pagelog' )->text(),
				'href' => SpecialPage::getTitleFor( 'Log' )->getLocalURL(
					[ 'page' => $title->getPrefixedText() ]
				),
				'id' => 't-pagelog'
			];
		}

		// This is really dumb, and you're an idiot for doing it this way.
		// Obviously if you're not the idiot who did this, I don't mean you.
		foreach ( $pileOfEditTools as $navKey => $navBlock ) {
			$currentSet = null;
			if ( in_array( $navKey, [
				'edit',
				'view',
				'history',
				'addsection',
				'viewsource',
				'watch',
				'unwatch',
				'delete',
				'rename',
				'protect',
				'unprotect',
				'move'
			] ) ) {
				$currentSet = 'page-primary';
			} else {
				// Catch random extension ones?
				$currentSet = 'page-secondary';
			}
			$sortedPileOfTools[$currentSet][$navKey] = $navBlock;
		}
		foreach ( $pileOfTools as $navKey => $navBlock ) {
			$currentSet = null;

			if ( $navKey === 'contributions' ) {
				$currentSet = 'page-primary';
			} elseif ( in_array( $navKey, [
				'blockip',
				'userrights',
				'log',
				'emailuser'
			] ) ) {
				$currentSet = 'user';
			} elseif ( in_array( $navKey, [
				'whatlinkshere',
				'print',
				'info',
				'pagelog',
				'recentchangeslinked',
				'permalink',
				'wikibase',
				'cite'
			] ) ) {
				$currentSet = 'page-tertiary';
			} elseif ( in_array( $navKey, [
				'more',
				'languages'
			] ) ) {
				$currentSet = 'more';
			} else {
				$currentSet = 'general';
			}
			$sortedPileOfTools[$currentSet][$navKey] = $navBlock;
		}

		return $sortedPileOfTools;
	}

	/**
	 * Categories for the sidebar
	 *
	 * Assemble an array of categories. This doesn't show any categories for the
	 * action=history view, but that behaviour is consistent with other skins.
	 *
	 * @return string html
	 */
	protected function getCategories() {
		$skin = $this->getSkin();
		$html = '';

		$allCats = $skin->getOutput()->getCategoryLinks();
		if ( !empty( $allCats ) ) {
			if ( !empty( $allCats['normal'] ) ) {
				$catHeader = 'categories';
				$html .= $this->getCatList(
					$allCats['normal'],
					'normal-catlinks',
					'mw-normal-catlinks',
					'categories'
				);
			} else {
				$catHeader = 'hidden-categories';
			}

			if ( isset( $allCats['hidden'] ) ) {
				$hiddenCatClass = [ 'mw-hidden-catlinks' ];
				if ( $skin->getUser()->getBoolOption( 'showhiddencats' ) ) {
					$hiddenCatClass[] = 'mw-hidden-cats-user-shown';
				} elseif ( $skin->getTitle()->getNamespace() == NS_CATEGORY ) {
					$hiddenCatClass[] = 'mw-hidden-cats-ns-shown';
				} else {
					$hiddenCatClass[] = 'mw-hidden-cats-hidden';
				}
				$html .= $this->getCatList(
					$allCats['hidden'],
					'hidden-catlinks',
					$hiddenCatClass,
					[ 'hidden-categories', count( $allCats['hidden'] ) ]
				);
			}
		}

		return $html;
	}

	/**
	 * List of categories
	 *
	 * @param array $list
	 * @param string $id
	 * @param string|array $class
	 * @param string|array $message i18n message name or an array of [ message name, params ]
	 *
	 * @return string html
	 */
	protected function getCatList( $list, $id, $class, $message ) {
		$html = Html::openElement( 'div', [ 'id' => "sidebar-{$id}", 'class' => $class ] );

		$makeLinkItem = function ( $linkHtml ) {
			return Html::rawElement( 'li', [], $linkHtml );
		};

		$categoryItems = array_map( $makeLinkItem, $list );

		$categoriesHtml = Html::rawElement( 'ul',
			[],
			implode( '', $categoryItems )
		);

		$html .= $this->getPortlet( $id, $categoriesHtml, $message );

		$html .= Html::closeElement( 'div' );

		return $html;
	}
}

?>
