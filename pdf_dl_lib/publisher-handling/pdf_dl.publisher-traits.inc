<?php

trait publisherTraits
{
	public $link;	// = link to (publisher's journal) webpage
	public $file;	// Only used if $optAry['html'] is empty: local file to dump HTML code, directory must exist!
	public $htmlCode;
	public $htmlDom;

	public function setup( &$optAry = [] )		// handled keys: 'link', 'html', 'file'
	{
		$try2dump = ( @!empty($optAry['file']) && ( @!empty($optAry['link']) || @empty($optAry['html']) ) );

		if ( @!empty($optAry['link']) ) {
			$this->link = $optAry['link'];
			if ( @empty($optAry['html']) ) {
				if ( class_exists('PdfHamster') && method_exists('PdfHamster','fetchUrl') ) {
					$optAry['html'] = PdfHamster::fetchUrl( $optAry['link'], $optAry );
				} else { // try to avoid PHP's file... functions (they easily fail to mimic a browser)
					$optAry['html'] = file_get_contents($optAry['link']);
				}
			}
		}
		if ( @!empty($optAry['html']) ) {
			if ( is_string($optAry['html']) ) {
				$this->htmlCode = $optAry['html'];
				$this->htmlDom = new DOMDocument('1.0', 'iso-8859-1');
				@$this->htmlDom->loadHTML( $optAry['html'] );
			}
			elseif ( is_object($optAry['html']) ) {
				$this->htmlDom = $optAry['html'];
				@$this->htmlCode->saveHTML( $optAry['html'] );
			}
		}

		if ( $try2dump /* && !empty($this->htmlCode) */ ) {
			file_put_contents( $optAry['file'], strval($this->htmlCode) );
		}
	}
	
	public function getCitPdfUrl()		// identical with PdfDownloader::locateMetaCitationPdfUrl()
	{
		if ( @is_object($this->htmlDom) ) {
			$tagAry = $this->htmlDom->getElementsByTagName('meta');
			foreach( $tagAry as $tagObj) {
				if ($tagObj->getAttribute('name') == 'citation_pdf_url' ) {
					return $tagObj->getAttribute('content');
				}
			}
		}
        return false;
	}
}
