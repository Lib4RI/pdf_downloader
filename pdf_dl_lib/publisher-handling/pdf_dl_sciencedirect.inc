<?php
include_once('/home/hoeslifr/___BatchWork/__Lib4RI.PDF-ReDL/pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');

/*
	Elsevier/Sciencedirect - example page:
	https://www.sciencedirect.com/science/article/pii/S0304380005004965
*/


class pdf_dl_sciencedirect /* extends PdfDownloader */
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
		return ( $this->link . '/pdf' ); // e.g. https://www.sciencedirect.com/science/article/pii/S0304380005004965/pdf
	}
}
