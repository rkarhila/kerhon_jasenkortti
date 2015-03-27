
<?php

// Implement supersimple username and password check!

require_once 'passwords.php';


if ($_POST['username'] != $username) {
  echo "Username and password do not match";
  exit(0);
}
else if ($_POST['password'] != $password) {
  echo "Username and password do not match";
  exit(0);
}



echo '
<html>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8"> 
<body>';
   
echo "Initialising database ..." .PHP_EOL;


$dbfile="members.sqlite";

if (!file_exists($dbfile)) {

  $db = new SQLite3($dbfile);
  $db->exec("pragma synchronous = off;");
  $sqlcommand="BEGIN TRANSACTION;

CREATE TABLE jasenet
(
   j_id INTEGER PRIMARY KEY AUTOINCREMENT,
   sukunimi TEXT NOT NULL,
   etunimi TEXT NOT NULL,
   email TEXT NOT NULL,
   syntymvuosi INTEGER NOT NULL,
   voimassa TEXT,
   rooli TEXT NOT NULL
);";

  $sqlcommand.="COMMIT;";
  print "<pre>$sqlcommand </pre>";
  $dbcreation = $db->exec($sqlcommand);
  if ($dbcreation) {
    print "<br>Database initialised.".PHP_EOL;
    //exit (0);
  } else
    {
      print "<br>Initialising DB did not go like in Stromsso";
    }
}
else {
  $db = new SQLite3($dbfile);
  $db->exec("pragma synchronous = off;");
  
}


// Upload handling:


// From: http://www.w3schools.com/php/php_file_upload.asp

$filebasename = basename($_FILES["fileToUpload"]["name"]);

print "<br><br>Let's work with $filebasename<br><br>";

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
// $loadSheets = array('Sheet1');
//$excelReader->setLoadSheetsOnly($loadSheets);
 
//the default behavior is to load all sheets
$excelReader->setLoadAllSheets();



$objPHPExcel = $excelReader->load($fileName);


//
// From https://github.com/PHPOffice/PHPExcel/blob/develop/Documentation/\
// markdown/Overview/07-Accessing-Cells.md
//
$objWorksheet = $objPHPExcel->getActiveSheet();

echo 'Adding new data to database'. PHP_EOL;
//echo '<table>' . PHP_EOL;

$sqlcommand="BEGIN TRANSACTION;";

foreach ($objWorksheet->getRowIterator() as $row) {
  
  //  echo '<tr>' . PHP_EOL;
  $cellIterator = $row->getCellIterator();
  $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
                                                     //    even if a cell value is not set.
                                                     // By default, only cells that have a value 
                                                     //    set will be iterated.

  $memberobj=Array();
  foreach ($cellIterator as $cell) {
    $memberobj[$cell->getColumn()]=$cell->getValue();    
    //echo '<td>' . 
    //  $cell->getValue() . 
    //  '</td>' . PHP_EOL;
  }
  $memberobj['E']=substr($memberobj['E'],0,4);

  if ($memberobj['F'] != null && $memberobj['C'] != null && is_numeric($memberobj['E'])) {
    $sqlcommand.="INSERT INTO jasenet (j_id,sukunimi, etunimi, email, syntymvuosi,voimassa,rooli) VALUES ( ".
      "'".$memberobj['A']."',".  // id
      "'".$memberobj['B']."',".  // sukunimi
      "'".$memberobj['C']."',".  // etunimi
      "'".$memberobj['D']."',".  // email
      $memberobj['E'].",".  // syntymvuos
      "'".$memberobj['F']."',".  // voimassa
      "'".$memberobj['G']."\"');\n"; // rooli

  }

  //  echo '</tr>' . PHP_EOL;

}
//echo '</table>' . PHP_EOL;

$sqlcommand.="COMMIT;\n";
print "<pre>$sqlcommand </pre>";
$dbadd=$db->exec($sqlcommand);

if (!$dbadd) {
  print "something went wrong in adding to database<br>".PHP_EOL;
}






/*


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


*/

				       

?> 


</body></html>