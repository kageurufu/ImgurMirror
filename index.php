<?php
// imgur.php
// A super minimal caching imgur mirror script
// Add this to your crontab to prevent massive folder issues:
//   */60 * * * * find ~/path/to/imgur/cache -type f -mtime +3 -delete
// Or turn off caching here...

$CACHE_FOLDER = './cache/';

// Don't edit past here
$isbeta = basename(__FILE__) === 'beta.php';
$imgur = $_SERVER['QUERY_STRING'] ;
$con = stream_context_create(array('http'=>array('timeout'=>15)));

if (!$_SERVER['QUERY_STRING']) {
?><!doctype html>
<html>
  <body>
    <div>
      <h1>Imgur Proxy</h1>
<? if ($isbeta) { ?>
      <h2>BETA MODE</h2>
<? } ?>
      <dl>
        <dt>Image</dt>
        <dd><?=$_SERVER['HTTP_HOST']?>/imagehash</dd>
        <dt>Gallery</dt>
        <dd><?=$_SERVER['HTTP_HOST']?>/a/albumhash</dd>
        <dd><?=$_SERVER['HTTP_HOST']?>/gallery/albumhash</dd>
        <dt>GIFV</dt>
        <dd><?=$_SERVER['HTTP_HOST']?>/hash.gifv</dd>
      </dl>
    </div>
    <footer>
      Code on <a href="https://github.com/kageurufu/imgurmirror">github.com</a>
    </footer>
  </body>
</html>
<?
  return;
}

if (preg_match('/^(a|gallery)\/([a-zA-Z0-9]{5,})$/i', $imgur, $matches)) {
  $album_type = $matches[1];
  $album_hash = $matches[2];
  $album_url = 'https://imgur.com/' . $album_type . '/' . $album_hash;
  $cached_filename = $CACHE_FOLDER . $album_hash . '.json';

  if (file_exists($cached_filename)) {
    $fromcache = true;
    $album_data = json_decode(file_get_contents($cached_filename));
  } else { 
    $fromcache = false;
    $album_html = @file_get_contents($album_url, 0, $con) or die('Failed to get imgur album');
    
    if(!preg_match('/^ +image +: (.+), *$/m', $album_html, $album_matches)) 
      die('Failed to locate album data');

    $album_json = $album_matches[1];
    $album_data = json_decode($album_json);

    file_put_contents($cached_filename, $album_json);
  }
  $images = $album_data->album_images->images;
?><!doctype html>
<html>
  <head>
    <style>
      html, body {
        background-color: #111;
        margin: 0;
        padding: 0;
      }
      body a {
        margin: 5px;
        margin-bottom: 10px;
        max-width: 100%;
        display: block;
        text-align: center;
      }
      body a img {
        margin: 0 auto;
        display: block;
        max-width: 100%;
      }
      body a span {
        color: #ccc;
      }
    </style>
  </head>
  <body>
<?php foreach($images as $image) { ?>
    <a href="/<?=$image->hash.$image->ext?>">
      <img src="/<?=$image->hash.'h'.$image->ext?>" >
      <span><?=$image->description?></span>
      <!--<?=json_encode($image)?>-->
    </a>
<?php } ?>
  </body>
</html>
<?php
  return;
} else if (!preg_match('/^([a-zA-Z0-9]{5,})(?:\.(png|jpe?g|gifv?|webm|mp4))?$/i', $imgur, $matches)) {
  die('Not a valid imgur image/gifv video');
}

// gifv/image proxying
if (strtolower($matches[2]) === 'gifv') {
  ?><!doctype html>
<html>
  <body>
    <video preload="auto" autoplay="autoplay" muted="muted" loop="loop" webkit-playsinline controls="controls">
      <source src="/<?=$matches[1]?>.webm" type="video/webm">
      <source src="/<?=$matches[1]?>.mp4" type="video/mp4">
    </video>
    <script>
      var gif = document.location.pathname + "?<?=$matches[1]?>.gif";

      if( document.createElement("video").tagName.toLowerCase() !== 'video' ) {
        var i = document.createElement("img");
        img.src = gif;
        document.body.appendChild(i);
      }
    </script>
    <p>
      If the gifv isn't playing here, try the direct 
      <a href="/<?=$matches[1]?>.mp4">mp4</a>, 
      <a href="/<?=$matches[1]?>.webm">webm</a>, or 
      <a href="/<?=$matches[1]?>.gif">gif</a> links
    </p>
  </body>
</html><?php
} else {
  $cached_filename=$CACHE_FOLDER.$matches[1];

  switch($matches[2]) {
    case 'mp4': 
    case 'webm': 
      $content_type = 'video/'.$matches[2]; 
      $cached_filename=$CACHE_FOLDER.$matches[1].'_'.$matches[2];
      break;
    default: 
      // $content_type = 'image/'.$matches[2]; 
      break;
  }

  if(file_exists($cached_filename)) {
    $image = file_get_contents($cached_filename);
  } else {
    $image = @file_get_contents('http://i.imgur.com/'.$imgur, 0, $con);
    if(!$image) die('Cannot retrieve imgur file');
    file_put_contents($cached_filename, $image);
  }

  $finfo = new finfo(FILEINFO_MIME);
  header('Content-type: ' . $finfo->buffer($image));
  header('Cache-Control: public, max-age=31556926');
  die($image);
}
?>
