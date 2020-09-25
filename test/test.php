<?php
error_reporting(E_ERROR | E_PARSE);

include '../lib/pdf_downloader.php';

//$doi = '10.1039/c9lc01127g';
//$doi = '10.1016/j.combustflame.2020.08.040';
$doi = $argv[1];
$pdf_fname = $argv[2];

$down = new PdfDownloader();

// $down->setDoi($doi)->fetchHtml();

// $down->locatePdfUrl();




//$pdf = $down->returnPdf($doi);

$down->setDoi($doi)->fetchHtml()->locatePdfUrl();

//echo $down->getHtml();

// echo $down->getUrl();
// echo $down->getPdfUrl();

$down->fetchPdf();
file_put_contents($pdf_fname, $down->getPdf());