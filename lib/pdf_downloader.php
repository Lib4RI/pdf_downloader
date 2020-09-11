<?php


class PdfDownloader{
    private $dom;
    private $doi;
    private $html;
    private $pdf_url;
    private $doi_resolver = 'http://dx.doi.org/';
    
    public function __construct(){
        $dom = new DOMDocument();
    }
    
    public function setDoi($doi){
        $this->doi = $doi;
        return $this;
    }
    
    public function setDoiResolver($doi_resolver){
        $this->$doi_resolver = $doi_resolver;
        return $this;
    }

    public function getDoiResolver($doi_resolver){
        return $this->$doi_resolver;
    }
    
    public function fetchHtml(){
        $this->html = file_get_contents($this->doi_resolver.$this->doi);
        return $this;
    }
    
    public function getHtml(){
        return $this->html;
    }
    
    public function locatePdfUrl(){
        
        return $this;
    }
    
    public function getPdfUrl(){
        return $this->pdf_url;
    }
    
    public function setPdfName(){
        
    }
    
    private function getFromMetaCitationPdfUrl(){
        $tags = $dom->getElementsByTagName('meta');
        foreach( $tags as $tag ) {
            if ($tag->getAttribute('name') == 'citation_pdf_url'){
                $this->pdf_url = $tag->getAttribute('content');
                return true;
            }
        }
        return false;
    }
    
    private function getFromJsonElsevier(){
        $tags = $dom->getElementsByTagName('script');
        foreach( $tags as $tag ) {
            if (($tag->getAttribute('type') == 'application/json') && ($tag->getAttribute('data-iso-key') == '_0')){
                $data = json_decode($tag->nodeValue);
                return true;
            }
        }
        return false;
    }
}