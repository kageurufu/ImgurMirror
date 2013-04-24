<?php
$imgur = $_SERVER['QUERY_STRING'] ;
	//Grab the image url from the query string

if(!preg_match('/^([a-z0-9]{5,7})\.(png|jpg|gif)$/i', $imgur, $m)) {
		//Make sure only valid imgur filenames are allowed, 5-7 characters, and png, gif, or jpg extensions
    die('Not a valid imgur image');
    	//Exit if its not valid
}

$fname='./cache/'.$m[1];
	//Set the cached filename, without extension (cause fuck it)

if(file_exists($fname)){	
		//Check if the file is already cached
    header('Content-type: image');
		//Set the header so browsers will display it
    die(file_get_contents($fname));
    	//Output the cached file
}

$con = stream_context_create(array('http'=>array('timeout'=>15)));
	//Prevent long running timed out downloads, but long enough to get large files
$image = @file_get_contents('http://i.imgur.com/'.$imgur,0,$con);
	//download the image into a variable
if(!$image) die('Cannot retrieve imgur file');
	//If the download failed, exit
file_put_contents($fname, $image);
	//Save the image to cache
header('Content-type: image');
	//Set the header so browsers will display it
echo $image;
	//print the image
?>
