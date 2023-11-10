<?php
if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }
include_once( PATH_INSTALL . 'pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');


/*
	Tayler & Francis - example page:
	https://www.tandfonline.com/doi/full/10.1080/09603123.2010.550036
*/


class pdf_dl_tandfonline /* extends PdfDownloader */
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

	public function getPdfUrl()		// identical with locateMetaCitationPdfUrl()
	{
		return $this->getCitPdfUrl();
	}
	
	// NOT WORKING - 'PDF inside JavaScript'!
}
