<?php

/*
	IWA PUBLISHING - example page:
	https://iwaponline.com/ebooks/book/111/Mathematical-Modeling-of-Biofilms
*/


class pdf_dl_iwaponline /* extends PdfDownloader */
{
	public $pageLink;
	public $htmlCode;
	public $htmlDom;
	
	public function __construct( $dataAry = [] )
	{
		/* parent::__construct();	*/
		if ( @!empty($dataAry['link']) ) {
			$this->pageLink = $dataAry['link'];
			if ( @empty($dataAry['html']) ) {
				if ( class_exists('PdfHamster') && method_exists('PdfHamster','fetchUrl') ) {
					$dataAry['html'] = PdfHamster::fetchUrl( $dataAry['link'] );
				} else { // try to avoid PHP's file... functions (they easily fail to mimic a browser)
					$dataAry['html'] = file_get_contents($dataAry['link']);
				}
			}
		}
		if ( @!empty($dataAry['html']) ) {
			$this->htmlCode = $dataAry['html'];
			$this->htmlDom = new DOMDocument('1.0', 'iso-8859-1');
			$this->htmlDom->loadHTML( $dataAry['html'] );
		}
	}

	public function getPdfUrl()
	{
		if ( @gettype($this->htmlDom) != 'object' ) { return false; }
		/*
			<div class="toolbar-wrap pg_article" vt-toolbar-wrap" role="navigation">
			<div class="toolbar-inner-wrap">
				<ul id="Toolbar" role="navigation">
							<li class="toolbar-item item-pdf">
									<a class="al-link pdf openInAnotherWindow stats-item-pdf-download js-download-file-gtm-datalayer-event  book-pdfLink"
			   data-resourceId="111"
			   data-resourceTypeId="Book"
			   data-doi=""
			   data-doctype="contentPdf"
			   data-book-Id="111" 
			   href="/ebooks/book-pdf/1191/wio9781780402482.pdf"
			   target="_blank"
			>
				<span class="screenreader-text">Open the </span>
				<i class="icon-menu_pdf-small">
				</i><span>PDF <span class="screenreader-text">for  in another window</span></span>
			</a>
		*/
		$tagAry = $this->htmlDom->getElementsByTagName('a');
		foreach( $tagAry as $tagObj) {
			if ( $tagObj->getAttribute('data-doctype') == 'contentPdf' ) {
				if ( !( $pdfLink = @rtrim($tagObj->getAttribute('href')) ) ) { continue; }
				if ( strtolower(substr($pdfLink,-4)) != '.pdf' )  { continue; }
				if ( !empty($this->pageLink) && substr($pdfLink,0,1) == '/' ) {
					$ary = explode('/',$this->pageLink,4);
					$ary[3] = substr($pdfLink,1);
					return implode('/',$ary);
				}
				return $pdfLink;
			}
		}
		return false;
	}
}
