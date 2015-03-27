<?php


#$lastname=$memberinfo['lastname'];
#$firstname=$memberinfo['firstname'];
#$membernumber=$memberinfo['membernumber'];

$lastname="Wunderbaum-Möttönen-Mikkola";
$firstname="Hermanni";
$birthyear="3079";
$membernumber="100001";
$valid="31.3.2016";

$faceimagefile="hermanni_naama.jpg";

# Imagemagick script for constructing the cards:

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
$draw->setFont('Carlito'); /* Not too many fonts available normally */

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


/* Load the member image */
$faceimage= new Imagick();
$faceimage->setResolution( 300, 300 );
$faceimage->readImage( $faceimagefile );

$faceimage->cropThumbnailImage(236,307);
$faceimage->quantizeImage(256,Imagick::COLORSPACE_GRAY,0,0,0);

/* Combine images and flatten */

$im->compositeImage($faceimage, Imagick::COMPOSITE_DEFAULT, 33, 33); 
$im->flattenImages();



/* Load the member info layout and club logos etc */
/* Set font properties for membership info*/



/* Give image a format */
#$im->setImageFormat('png');



/* We'll want the right dimensions for the paper, and for that 
we'll use a pregenerated empty business card size pdf: */

#$pdf = new Imagick();
#$pdf->setResolution( 150, 150 );
#$pdf->readImage( "korttipohja.pdf" );



/* Important !! Took some googling to find this setting,
to get the right size for printing */
 
#$pdf->setOption('pdf:use-cropbox', 'true');


/* Combine the generated png file with the template pdf */
#$pdf->compositeImage($im, Imagick::COMPOSITE_DEFAULT, 0, 0); 
#$pdf->setImageFormat('pdf');

/* Output the image with headers */
#header('Content-type: application/pdf');
echo $im;
					 
?>
