<?php
// if ( !defined( 'PATH_INSTALL') ) { define('PATH_INSTALL','./'); }
// include_once( PATH_INSTALL . 'pdf_dl_lib/publisher-handling/pdf_dl.publisher-traits.inc');

// UNFINISHED/TEST


class pdf_dl_sage
{
	private $htmlData = null;
	
	public function __construct( $htmlData = null )
	{
		if ( $htmlData != null ) { $this->htmlData = $htmlData; }
	}

	public function getPdfUrl()
	{
		if ( $this->htmlData ) {
			if ( gettype($this->htmlData) == 'string' ) { // code goes here
				return $this->parseCode();
			}
			elseif ( gettype($this->htmlData) == 'object' ) { // HTML as DOM object
				return $this->parseObj();
			}
		}
		return false;
	}

	private function parseObj()
	{
		return 'https://test.sage.com/object.pdf';
	}

	private function parseCode()
	{
		return 'https://test.sage.com/code.pdf';
	}
}

