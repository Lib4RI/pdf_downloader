<?php
if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }
include_once( PATH_INSTALL . 'pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');


/*
	RSC / Royal Society of Chemistry - example page:
	https://pubs.rsc.org/en/content/articlelanding/2010/EM/c002020f
*/


class pdf_dl_rsc /* extends PdfDownloader */
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
