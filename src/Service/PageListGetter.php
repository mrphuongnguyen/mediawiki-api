<?php

namespace Mediawiki\Api\Service;

use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Mediawiki\DataModel\Page;
use Mediawiki\DataModel\PageIdentifier;
use Mediawiki\DataModel\Pages;
use Mediawiki\DataModel\Revisions;
use Mediawiki\DataModel\Title;

/**
 * @access private
 *
 * @author Addshore
 */
class PageListGetter {

	/**
	 * @var MediawikiApi
	 */
	private $api;

	/**
	 * @param MediawikiApi $api
	 */
	public function __construct( MediawikiApi $api ) {
		$this->api = $api;
	}

	/**
	 * @since 0.3
	 *
	 * @todo deal with continuing somehow?
	 *
	 * @param string $name
	 * @param array $extraParams
	 *
	 * @returns Pages
	 */
	public function getPageListFromCategoryName( $name, array $extraParams = array() ) {
		//TODO implement recursive behaviour

		$pages = new Pages();

		$params = array(
			'list' => 'categorymembers',
			'cmtitle' => $name,
		);

		$result =
			$this->api->getRequest(
				new SimpleRequest( 'query', array_merge( $extraParams, $params ) )
			);
		if ( !array_key_exists( 'query', $result ) ) {
			return $pages;
		}

		foreach ( $result['query']['categorymembers'] as $member ) {
			$pages->addPage(
				new Page(
					new PageIdentifier(
						new Title( $member['title'], $member['ns'] ),
						$member['pageid']
					),
					new Revisions()
				)
			);
		}

		return $pages;
	}

	/**
	 * @todo deal with continuing somehow?
	 *
	 * @param string $pageName
	 * @param array $extraParams
	 *
	 * @return Pages
	 */
	public function getPageListFromPageTransclusions( $pageName, array $extraParams = array() ) {
		$params = array(
			'list' => 'embeddedin',
			'eititle' => $pageName,
		);

		$result =
			$this->api->getRequest(
				new SimpleRequest( 'query', array_merge( $extraParams, $params ) )
			);

		$pages = new Pages();

		if ( !array_key_exists( 'query', $result ) ) {
			return $pages;
		}

		foreach ( $result['query']['embeddedin'] as $member ) {
			$pages->addPage(
				new Page(
					new PageIdentifier(
						new Title( $member['title'], $member['ns'] ),
						$member['pageid']
					),
					new Revisions()
				)
			);
		}

		return $pages;
	}

	/**
	 * @since 0.5
	 *
	 * @param string $pageName
	 *
	 * @returns Pages
	 */
	public function getFromWhatLinksHere( $pageName ) {
		$continue = array();
		$limit = 500;
		$pages = new Pages();

		while ( true ) {
			$params = array(
				'prop' => 'info',
				'generator' => 'linkshere',
				'titles' => $pageName,
			);
			if ( !empty( $continue ) ) {
				$params = array_merge( $params, $continue );
			}
			$params['glhlimit'] = $limit;
			$result = $this->api->getRequest( new SimpleRequest( 'query', $params ) );
			if ( !array_key_exists( 'query', $result ) ) {
				return $pages;
			}

			foreach ( $result['query']['pages'] as $member ) {
				$pages->addPage(
					new Page(
						new PageIdentifier(
							new Title( $member['title'], $member['ns'] ),
							$member['pageid']
						),
						new Revisions()
					)
				);
			}

			if ( empty( $result['continue'] ) ) {
				return $pages;
			} else {
				$continue = $result['continue'];
			}
		}
	}

	/**
	 * @param array $extraParams
	 *
	 * @todo deal with continuing
	 *
	 * @return Pages
	 */
	public function getRandom( array $extraParams = array() ) {
		$params = array(
			'list' => 'random',
		);
		$result =
			$this->api->getRequest(
				new SimpleRequest( 'query', array_merge( $extraParams, $params ) )
			);

		$pages = new Pages();

		foreach ( $result['query']['random'] as $member ) {
			$pages->addPage(
				new Page(
					new PageIdentifier(
						new Title( $member['title'], $member['ns'] ),
						$member['pageid']
					),
					new Revisions()
				)
			);
		}
	}

}
