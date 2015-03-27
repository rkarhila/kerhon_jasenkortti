<?php

// From: http://www.w3schools.com/php/php_file_upload.asp

$filebasename = basename($_FILES["fileToUpload"]["name"]);

print "$filebasename<br><br>";

$target_file = "Entities-032415.xls";

$uploadOk = 1;
$fileType = trim(pathinfo($filebasename,PATHINFO_EXTENSION));



// Check file extension:


if ($fileType != 'xls') {
  print "The file is not in the right format (is '$fileType'). Exiting.";
  exit(0);
}

print "File extension passed check.";



// Check file size:

if ($_FILES["fileToUpload"]["size"] > 2000000) {
  echo "Sorry, your file is too large (2MB limit).";

  $uploadOk = 0;

} 

$fileName=$_FILES['fileToUpload']['tmp_name'];

print "<br>Processing ".$fileName." ...";


/** Include PHPExcel_IOFactory */
require_once 'Classes/PHPExcel/IOFactory.php';

			       
// $objPHPExcel = PHPExcel_IOFactory::load($_FILES['fileToUpload']['tmp_name']);


/** automatically detect the correct reader to load for this file type */
$excelReader = PHPExcel_IOFactory::createReaderForFile($fileName);
 
/** Create a reader by explicitly setting the file type.
// $inputFileType = 'Excel5';
// $inputFileType = 'Excel2007';
// $inputFileType = 'Excel2003XML';
// $inputFileType = 'OOCalc';
// $inputFileType = 'SYLK';
// $inputFileType = 'Gnumeric';
// $inputFileType = 'CSV';
$excelReader = PHPExcel_IOFactory::createReader($inputFileType);
*/


//if we dont need any formatting on the data
$excelReader->setReadDataOnly();
 
//load only certain sheets from the file
$loadSheets = array('Sheet1');
$excelReader->setLoadSheetsOnly($loadSheets);
 
//the default behavior is to load all sheets
$excelReader->setLoadAllSheets();



$excelObj = $excelReader->load($fileName);


$rows = $excelObj->getActiveSheet()->getRowIterator((1));

print "foo2";

$cnt=1;
foreach($rows as $row){
  if ($cnt++==1) {print_r($row);};
  //$values[] = $row->getCellFromColumn('B')->getValue();
  //print_r ($values);
  //print "<br>";
  //in getCellFromColumn is the column index, lets say B
  //getCellFromColumn($par) is a fictional function, i can't find how to get cell from a 
  //certain column directly from the row object
  
}



$excelObj->getActiveSheet()->toArray(null, true,true,true);





print "<pre>";
print_r ($excelObj);
print "</pre>";



// A simple way to display everything:

// $objWriter = PHPExcel_IOFactory::createWriter($excelObj, 'HTML');
// $objWriter->save('php://output');




				       

?> 