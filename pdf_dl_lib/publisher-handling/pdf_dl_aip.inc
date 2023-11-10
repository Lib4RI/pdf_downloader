<?php
if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }
include_once( PATH_INSTALL . 'pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');


/*
	American Institute of Physics - example page:
	https://pubs.aip.org/aip/pof/article/22/5/054104/257544/Identifying-unstable-modes-in-stratified-shear
*/


class pdf_dl_aip /* extends PdfDownloader */
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
}
