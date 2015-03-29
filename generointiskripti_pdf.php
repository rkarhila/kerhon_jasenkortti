<html>
<head>
  <?php



$birthyear=trim($_POST['birthyear']);
$email=trim($_POST['email']);


$dbfile="members.sqlite";

if (!file_exists($dbfile)) {
  echo "Jotain vikaa tietokannassa. Ilmoitathan j&auml;senvastaaville (jasen@alppikerho.fi) jos vika jatkuu.<br>";
  exit(0);
}
else {
  $db = new SQLite3($dbfile);
  $db->exec("pragma synchronous = off;");
}

// Get the member data from the database:

if ($db->querySingle("SELECT count(*) FROM jasenet WHERE email='$email' AND syntymvuosi=$birthyear;") > 0) {

  $sqlcommand = "SELECT * FROM jasenet WHERE email='$email' AND syntymvuosi=$birthyear;";
  // print $sqlcommand;
  $queryres = $db->query($sqlcommand);
  $arr = $queryres->fetcharray();


  // print_r($arr);

  $lastname=$arr['sukunimi'];
  $firstname=$arr['etunimi'];
  $membernumber=$arr['j_id'];
  $valid=$arr['voimassa'];
  $memberrole=$arr['rooli'];



  // Imagemagick script for constructing the cards:

  /* Read the tempate png */
  /* 150dpi should be enough for printing full-color images */
  /* But we'll still use 300 because of fancy smart phone displays */
  $im = new Imagick();
  $im->setResolution( 300, 300 );
  $im->readImage( "sivu1.pdf" );


  /* Create some drawing object 
     (taken from example, don't really know the mechanism here...)  */
  $draw = new ImagickDraw();


  /* Set font properties for the stamp */
  #$draw->setFillColor('#bbbbbb43'); /* Semi-transparent grey */
  $draw->setFillColor('#ffffffff'); /* Full white */
  #$draw->setFont('Calibri'); /* Not too many fonts available normally */
  $draw->setFont('Carlito-Regular.ttf'); /* Not too many fonts available normally */

  $draw->setFontSize( 38 );

  $rowheight=67;

  /* Dump the font metrics */
  $fonts=38;
  $draw->setFontSize( 38 );
  $textd = $im->queryFontMetrics($draw, $firstname);
  while($textd['textWidth']>340) {
    $fonts-=2;
    $draw->setFontSize( $fonts );
    $textd=$im->queryFontMetrics($draw, $firstname);
  }


  $im->annotateImage($draw, 240, 522, 0, $firstname);

  /* Dump the font metrics */
  $fonts=38;
  $draw->setFontSize( 38 );
  $textd = $im->queryFontMetrics($draw, $lastname);
  while($textd['textWidth']>340) {
    $fonts-=2;
    $draw->setFontSize( $fonts );
    $textd=$im->queryFontMetrics($draw, $lastname);
  }

  $im->annotateImage($draw, 240, 522+1*$rowheight, 0, $lastname);

  $fonts=38;
  $draw->setFontSize( $fonts );
  $im->annotateImage($draw, 240, 522+2*$rowheight, 0, $birthyear);
  $im->annotateImage($draw, 240, 522+3*$rowheight, 0, $membernumber);
  $im->annotateImage($draw, 240, 522+4*$rowheight, 0, $valid);


  /* Load the member image 
  //  We're not doing it now! 
  $faceimage= new Imagick();
  $faceimage->setResolution( 300, 300 );
  $faceimage->readImage( $faceimagefile );

  $faceimage->cropThumbnailImage(236,307);
  $faceimage->quantizeImage(256,Imagick::COLORSPACE_GRAY,0,0,0);

  // Combine images and flatten 

  $im->compositeImage($faceimage, Imagick::COMPOSITE_DEFAULT, 33, 33); 
  $im->flattenImages();*/



  $im->setImageCompression(Imagick::COMPRESSION_JPEG);
  $im->setImageCompressionQuality(80); 
  $im->stripImage();



  if (strpos($memberrole, "ilman") === false) {
    // print "Luoadaan kaksisivuinen j&auml;senkortti kategorialle $memberrole.<br>"; 
    // Add another page to the pdf somehow;
    /*
      $page2 = new Imagick();
      $page2->setResolution( 300, 300 );
      $page2-> readImage("sivu2.pdf");

      $page2->setImageCompression(Imagick::COMPRESSION_JPEG);
      $page2->setImageCompressionQuality(80);
      $page2->stripImage();
    */
      


      $page1imagefile=tempnam(sys_get_temp_dir(),"php_kortti_");
    // //  $page1imagefile="tmp_kortti1.pdf";
      $im->writeImage($page1imagefile);
 
    $finalimagefile=tempnam(sys_get_temp_dir(),"php_kortti_");
    // // $finalimagefile="tmp_kortti.pdf";

    // $command="pdfunite $page1imagefile sivu2.pdf $finalimagefile";

    // $command="convert  -density 300x300 -quality 80  -compress lzw  $page1imagefile sivu2.pdf $finalimagefile";

    // $command="pdfjoin --outfile $finalimagefile $page1imagefile sivu2.pdf";

    $command="gs -dNOPAUSE -sDEVICE=pdfwrite -sOUTPUTFILE=$finalimagefile -dBATCH $page1imagefile sivu2.pdf";

    // print $command."<br>";
  print "Generoidaan korttia ...<br>";
  print (exec($command))."<br>";

  }
  else {			    
    // print "Luodaan j&auml;senkortti kategorialle $memberrole.<br>"; 
    $finalimagefile=tempnam(sys_get_temp_dir(),"php_kortti_");
    $im->writeImage($finalimagefile, TRUE);
  }



  // print "Sahkopostia tassa lahetellaan osoitteeseen $email ... ";


  /* Email: */

  $subject="Suomen Alppikerhon jäsenkortti / Finnish Alpine club membership card";


  $message="Jäsenkorttisi on liitteenä. Näytä se puhelimesi ruudulta tai tulosta paperille ja nauti jäseneduista!\r\n\r\n";
  $message.="Your membership card is attached. Show it on your phone or print on paper, and enjoy the benefits";

  $attachment = chunk_split(base64_encode(file_get_contents($finalimagefile))); // Encode file contents for plain text sending
  $attachment_filename="Alppikerho_jasenkortti_".$firstname."_".$lastname.".pdf";


  // Define the boundray we're going to use to separate our data with.
  $mime_boundary = '==MIME_BOUNDARY_' . md5(time());

  // Define attachment-specific headers
  $headers['From'] = "Alppikerho <jasen@alppikerho.fi>";
  $headers['MIME-Version'] = '1.0';
  $headers['Content-Type'] = 'multipart/mixed; boundary="' . $mime_boundary . '"';
  $headers['X-Mailer'] = 'PHP/' . phpversion();  

 
  // Convert the array of header data into a single string.
  $headers_string = '';
  foreach($headers as $header_name => $header_value) {
    if(!empty($headers_string)) {
      $headers_string .= "\r\n";
    }
    $headers_string .= $header_name . ': ' . $header_value;
  }

  // Message Body
  $message_string  = '--' . $mime_boundary;
  $message_string .= "\r\n";
  $message_string .= 'Content-Type: text/plain; charset="iso-8859-1"';
  $message_string .= "\r\n";
  $message_string .= 'Content-Transfer-Encoding: 7bit';
  $message_string .= "\r\n";
  $message_string .= "\r\n";
  $message_string .= $message;
  $message_string .= "\r\n";
  $message_string .= "\r\n";
 
 
  // Add attachments to message body
 
  $message_string .= '--' . $mime_boundary;
  $message_string .= "\r\n";
  $message_string .= 'Content-Type: application/octet-stream; name="' . $attachment_filename . '"';
  $message_string .= "\r\n";
  $message_string .= 'Content-Description: ' . $attachment_filename;
  $message_string .= "\r\n";
 
  $message_string .= 'Content-Disposition: attachment; filename="' . $attachment_filename . '";';;
  $message_string .= "\r\n";
  $message_string .= 'Content-Transfer-Encoding: base64';
  $message_string .= "\r\n\r\n";
  $message_string .= $attachment;
  $message_string .= "\r\n\r\n";
 
  // Signal end of message
  $message_string .= '--' . $mime_boundary . '--';
 
 
  mail( $email, $subject, $message_string, $headers_string );


  // print "<br>Noin, sinne l&auml;hti.";
  
  $timestamp = date('Y-m-d h:i:s', time());
  $ip1=$_SERVER['REMOTE_ADDR'];
  $ip2=$_SERVER['HTTP_X_FORWARDED_FOR'];

  $sqlcommand = "INSERT INTO loki (email, syntymvuosi, timestamp, remote_addr,http_x_forwarded, onnistui) ";
  $sqlcommand .= "VALUES ('$email', '$birthyear', '$timestamp', '$ip1', '$ip2', 1); ";

  $db->exec($sqlcommand);


  print '<meta http-equiv="refresh" content="0; url=http://alppikerho.fi/ole-hyva/">';
  

}
  else {

  print '<meta http-equiv="refresh" content="0; url=http://alppikerho.fi/no-johan-nyt/">';
  // print "Antamasi osoite ($email) ja syntymävuosi ($birthyear) eivät ole rekisterissä. Tarkista tiedot ja yritä uuudelleen. Jos ei edelleenkään toimi, ota yhteyttä jäsenvastaaviin jasen@alppikerho.fi.";

  $timestamp = date('Y-m-d h:i:s', time());
  $ip1=$_SERVER['REMOTE_ADDR'];
  $ip2=$_SERVER['HTTP_X_FORWARDED_FOR'];

  $sqlcommand = "INSERT INTO loki (email, syntymvuosi, timestamp, remote_addr,http_x_forwarded, onnistui) ";
  $sqlcommand .= "VALUES ('$email', '$birthyear', '$timestamp', '$ip1', '$ip2', 0); ";

  $db->exec($sqlcommand);


}

    
    ?>
  </head>
</html>
