<?php
if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }
include_once( PATH_INSTALL . 'pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');


/*
	WILEY - example page:
	https://bsssjournals.onlinelibrary.wiley.com/doi/10.1111/j.1365-2389.2006.00837.x
*/


class pdf_dl_wiley /* extends PdfDownloader */
{
	use publisherTraits;
	
	public $link;	// = link to (publisher's journal) webpage
	public $file;	// = local file to dump HTML code (used only if $optAry['html'] is empty).
	public $htmlCode;
	public $htmlDom;
	
	public function __construct( &$optAry = [] )	// handled keys: 'link', 'html', 'file'
	{
		/* parent::__construct();	*/
		$this->setup( $optAry );
	}

	public function getPdfUrl()
	{
		if ( @gettype($this->htmlDom) != 'object' ) { return false; }
		/*
			<div class="coolBar__section coolBar--download PdfLink cloned">
			<a href="/doi/epdf/10.1111/j.1365-2389.2006.00837.x" title="ePDF" class="coolBar__ctrl pdf-download">
				<img src="/specs/products/acropolis/pericles/releasedAssets/images/pdf-icon-169a2eb30e52100e76dfa5f4b66998e6.png" alt="PDF">PDF
			</a></div>
		*/
		$tagAry = $this->htmlDom->getElementsByTagName('a');
		foreach( $tagAry as $tagObj) {
			if ( $tagObj->hasAttribute('class') ) {
				$classList = '_ ' . $tagObj->getAttribute('class') . ' ';
				if ( strpos($classList,'pdf-download') ) {
					return $tagObj->getAttribute('href');
				}
			}
		}
		return false;
	}
}
