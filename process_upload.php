
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
);

CREATE TABLE loki
(
   l_id INTEGER PRIMARY KEY AUTOINCREMENT,
   email TEXT NOT NULL,
   syntymvuosi INTEGER NOT NULL,
   timestamp TEXT NOT NULL,
   remote_addr TEXT NOT NULL,
   http_x_forwarded TEXT NOT NULL,
   onnistui INTEGER NOT NULL
);
";

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
echo '<pre>'.PHP_EOL;

$sqlcommand="BEGIN TRANSACTION;";

foreach ($objWorksheet->getRowIterator() as $row) {
  

  $cellIterator = $row->getCellIterator();
  $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
                                                     //    even if a cell value is not set.
                                                     // By default, only cells that have a value 
                                                     //    set will be iterated.

  $memberobj=Array();
  foreach ($cellIterator as $cell) {
    $memberobj[$cell->getColumn()]=$cell->getValue();    
  }
  $memberobj['E']=substr($memberobj['E'],0,4);


  if ($memberobj['D'] == null) {
    print "Jasenen ".$memberobj['A']." (".$memberobj['B'].") sähköpostiosoitetta ei ole määritelty. Ei lisätä kantaan.\n".PHP_EOL;
  }

  elseif ($memberobj['F'] == null) {
    print "Jasenen ".$memberobj['A']." (".$memberobj['B'].") kortin voimassaoloaikaa ei ole määritelty. Lisätään silti.".PHP_EOL;
  }



  if ($memberobj['D'] != null && is_numeric($memberobj['E'])) {

    if ($db->querySingle("SELECT count(*) FROM jasenet WHERE j_id=".$memberobj['A'].";") > 0) {
      print "Jasen ".$memberobj['A']." (".$memberobj['B'].") on jo kannassa - Päivitetään tiedot: ".$memberobj['A'].",".  // id
	"'".$memberobj['B']."',".  // sukunimi
	"'".$memberobj['C']."',".  // etunimi
	"'".$memberobj['D']."',".  // email
	$memberobj['E'].",".  // syntymvuos
	"'".$memberobj['F']."',".  // voimassa
	"'".$memberobj['G']."\n".PHP_EOL;

      $sqlcommand.="UPDATE jasenet SET " .
	"sukunimi='".$memberobj['B']."', " .
	"etunimi='".$memberobj['C']."', " .
	"email='".$memberobj['D']."', " .
	"syntymvuosi=".$memberobj['E'].", " .
	"voimassa='".$memberobj['F']."', " .
	"rooli='".$memberobj['G']."' " .
	"WHERE j_id=".$memberobj['A'].";";	
    }
    else { 
      print "Lisätään tietokantaan ".$memberobj['A'].",".  // id
	"'".$memberobj['B']."',".  // sukunimi
	"'".$memberobj['C']."',".  // etunimi
	"'".$memberobj['D']."',".  // email
	$memberobj['E'].",".  // syntymvuos
	"'".$memberobj['F']."',".  // voimassa
	"'".$memberobj['G']."\n"; // rooli

      $sqlcommand.="INSERT INTO jasenet (j_id,sukunimi, etunimi, email, syntymvuosi,voimassa,rooli) VALUES ( ".
	$memberobj['A'].",".  // id
	"'".$memberobj['B']."',".  // sukunimi
	"'".$memberobj['C']."',".  // etunimi
	"'".$memberobj['D']."',".  // email
	$memberobj['E'].",".  // syntymvuos
	"'".$memberobj['F']."',".  // voimassa
	"'".$memberobj['G']."\"');\n"; // rooli
    }
  }


}
echo "</pre>".PHP_EOL;
$sqlcommand.="COMMIT;\n";
$dbadd=$db->exec($sqlcommand);

if (!$dbadd) {
  print "<font size='large' color='red'>Jokin meni pieleen kannan päivittämisessä!</font>\n".PHP_EOL;
}
else {
  print "Kanta päivitetty onnistuneesti!\n".PHP_EOL;
}







?> 


</body></html>