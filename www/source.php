<?php
// ===========================================================================================
//
// Origin: http://github.com/mosbth/Utility
//
// Filename: source.php
//
// Description: Shows a directory listning and view content of files.
//
// Author: Mikael Roos, mos@bth.se
//
// Change history:
// 
// 2012-08-06: 
// Quick fix to display images in base directory. Worked only in subdirectories.
//
// 2012-05-30: 
// Added meta tags to remove this page from search engines and avoid ending up in search results.
//
// 2011-12-15: 
// Changed stylesheet to be compatible with blueprintcss style. Made all dirs clickable when 
// traversing down a dir-chain.
//
// 2011-05-31: 
// The update 2011-04-13 which supported follow symlinks has security issues. The follow of 
// symlinks, where destination path (realpath) is not below $BASEPATH, is disabled.
//
// 2011-04-13: 
// Improved support for including source.php in another context where header and footer is already
// set. Added $sourceSubDir, $sourceBaseUrl. Source.php can now display a subdirectory and will 
// work where the directory structure contains symbolic links. Changed all variable names to 
// isolate them. It's soon time to rewrite the whole code to version 2 of source.php...
//
// 2011-04-01: 
// Added detection of line-endings, Unix-style (LF) or Windows-style (CRLF).
//
// 2011-03-31: 
// Feature to try and detect chacter encoding of file by using mb_detect_encoding (if available)
// and by looking for UTF-8 BOM sequence in the start of the file. $encoding is set to contain the
// found encoding.
//
// 2011-02-21: 
// Can now have same link to subdirs, independently on host os. Links that contain / or \ is
// converted to DIRECTORY_SEPARATOR.
//
// 2011-02-04: 
// Can now link to #file to start from filename.
//
// 2011-01-26: 
// Added $sourceBasedir which makes it possible to set which basedir to use. This makes it
// possible to store source.php in another place. It does not need to be in the same directory 
// it displays. Use it like this (before including source.php):
// $sourceBasedir=dirname(__FILE__);
//
// 2011-01-20: 
// Can be included and integrated in an existing website where you already have a header 
// and footer. Do like this in another file:
// $sourceNoEcho=true;
// include("source.php");
// echo "<html><head><style type='text/css'>$sourceStyle</style></header>";
// echo "<body>$sourceBody</body></html>";
//
// 2010-09-14: 
// Thanks to Rocky. Corrected NOTICE when files had no extension.
//
// 2010-09-09: 
// Changed error_reporting to from E_ALL to -1.
// Display images of certain types, configurable option $IMAGES.
// Enabled display option of SVG-graphics.
//
// 2010-09-07: 
// Added replacement of \t with spaces as configurable option ($SPACES).
// Removed .htaccess-files. Do not show them.
//
// 2010-04-27: 
// Hide password even in config.php~.
// Added rownumbers and enabled linking to specific row-number.
//

// -------------------------------------------------------------------------------------------
//
// Settings for this pagecontroller. Review and change these settings to match your own
// environment.
//
error_reporting(-1);

// The link to this page. You may want to change it from relative link to absolute link.
if(isset($sourceBaseUrl)) {
  $HREF = $sourceBaseUrl;
} else {
  $HREF = '?';
}

// Should the result be printed or stored in variables?
// Default is to print out the result, with header and everything.
// If $sourceNoEcho is set, no printing of the result will be done. It will only be stored 
// in the variables $sourceBody and $sourceStyle
//
if(!isset($sourceNoEcho)) {
  $sourceNoEcho = null;
}
if(!isset($sourceSubDir)) {
  $sourceSubDir = null;
}
if(!isset($sourceNoIntro)) {
  $sourceNoIntro=null; // Set to true to avoid printing title and ingress
}
$sourceBody="";  // resulting html, can be echoed out to print the result
$sourceStyle=""; // css-style needed to print out the page

// Show the content of files named config.php, except the rows containing DB_USER, DB_PASSWORD
$HIDE_DB_USER_PASSWORD = TRUE; // TRUE or FALSE

// Separator between directories and files, change between Unix/Windows
$SEPARATOR = DIRECTORY_SEPARATOR;   // Using built-in PHP-constant for separator.
//$SEPARATOR = '/';   // Unix, Linux, MacOS, Solaris
//$SEPARATOR = '\\';   // Windows 

// Which directory to use as basedir for file listning, end with separator.
// Default is current directory
$BASEDIR = "." . $SEPARATOR;
if(isset($sourceBasedir)) {
  $BASEDIR = $sourceBasedir . $SEPARATOR;
}

// Display pictures instead of their source, if they have a certain extension (filetype).
$IMAGES = Array('png', 'gif', 'jpg', 'ico');

// Show syntax of the code, currently only supporting PHP or DEFAULT.
// PHP uses PHP built-in function highlight_string.
// DEFAULT performs <pre> and htmlspecialchars.
// HTML to be done.
// CSS to be done.
$SYNTAX = 'PHP';   // DEFAULT or PHP
$SPACES = '  ';   // Number of spaces to replace each \t


// -------------------------------------------------------------------------------------------
//
// Page specific code
//
if($sourceNoIntro) {
  $source_html = "";
} else {
  $source_html = <<<EOD
<header>
<h1>Show sourcecode</h1>
<p>
The following files exists in this folder. Click to view.
</p>
</header>
EOD;
}


// -------------------------------------------------------------------------------------------
//
// Verify the input variable _GET, no tampering with it
//
$source_currentdir  = isset($_GET['dir']) ? preg_replace('/[\/\\\]/', $SEPARATOR, strip_tags(trim($_GET['dir']))) : '';

$source_fullpath1   = realpath($BASEDIR);
$source_fullpath2   = realpath($BASEDIR . $source_currentdir);
$source_len = strlen($source_fullpath1);

if(!(is_dir($source_fullpath1) && is_dir($source_fullpath2))) {
  die('Not a directory.');
}

if(  strncmp($source_fullpath1, $source_fullpath2, $source_len) !== 0 ||
  strcmp($source_currentdir, substr($source_fullpath2, $source_len+1)) !== 0 ) {
  
  die('Tampering with directory?');
  //if(preg_match("/\.\./", $source_currentdir)) {}
}
$source_fullpath = $source_fullpath2;
$source_currpath = substr($source_fullpath2, $source_len+1);


// -------------------------------------------------------------------------------------------
//
// Show the name of the current directory
//
$source_dir        = basename($source_fullpath1);
$source_dirname   = basename($source_fullpath);
$source_dir_parts = !empty($source_currpath) ? explode($SEPARATOR, trim($source_currpath, $SEPARATOR)) : array();
$source_dir_path = "<a href='{$HREF}dir='>" . trim($source_dir, $SEPARATOR) . "</a>{$SEPARATOR}";
foreach($source_dir_parts as $val) {
  @$dir .= "{$val}{$SEPARATOR}";  
  $source_dir_path .= "<a href='{$HREF}dir=" . rtrim($dir, $SEPARATOR) . "'>{$val}</a>{$SEPARATOR}";
}
$source_html .= "<p><code>$source_dir_path</code></p>";


// -------------------------------------------------------------------------------------------
//
// Open and read a directory, show its content
//
$source_dir   = $source_fullpath;
$source_curdir1 = empty($source_currpath) ? "" : "{$source_currpath}{$SEPARATOR}";
$source_curdir2 = empty($source_currpath) ? "" : "{$source_currpath}";

$source_list = Array();
if(is_dir($source_dir)) {
    if ($source_dh = opendir($source_dir)) {
        while (($source_file = readdir($source_dh)) !== false) {
          if($source_file != '.' && $source_file != '..' && $source_file != '.svn' && $source_file != '.git' && $source_file != '.htaccess') {
            $source_curfile = $source_fullpath . $SEPARATOR . $source_file;
            if(is_dir($source_curfile)) {
                  $source_list[$source_file] = "<code><a href='{$HREF}dir={$source_curdir1}{$source_file}'>{$source_file}{$SEPARATOR}</a></code>";
                } else if(is_file($source_curfile)) {
                  $source_list[$source_file] = "<code><a href='{$HREF}dir={$source_curdir2}&amp;file={$source_file}'>{$source_file}</a></code>";
                }
             }
        }
        closedir($source_dh);
    }
}

ksort($source_list);

$source_html .= '<p>';
foreach($source_list as $source_val => $source_key) {
  $source_html .= "{$source_key}<br />\n";
}
$source_html .= '</p>';


// -------------------------------------------------------------------------------------------
//
// Show the content of a file, if a file is set
//
$source_dir   = $source_fullpath;
$source_file  = "";

if(isset($_GET['file'])) {
  $source_file = basename($_GET['file']);
  $source_filename = $source_dir . $SEPARATOR . $source_file;
  
  if(!is_file($source_filename)) {
    die("The file is not a regular file.");
  }
  
  // Get the content of the file
  $source_content = file_get_contents($source_dir . $SEPARATOR . $source_file);

  // Try to detect character encoding
  $source_encoding = null;

  // Detect character encoding
  if(function_exists('mb_detect_encoding')) {
    if($source_res = mb_detect_encoding($source_content, "auto, ISO-8859-1", true)) {
      $source_encoding = $source_res;
    }    
  }

  // Is it BOM?
  if(substr($source_content, 0, 3) == chr(0xEF) . chr(0xBB) . chr(0xBF)) {
    $source_encoding .= " BOM";
  }
  
  // Checking style of line-endings
  $source_lineendings = null;
  if(isset($source_encoding)) {
    $source_lines = explode("\n", $source_content);
    $source_l = strlen($source_lines[0]);
    if(substr($source_lines[0], $source_l-1, 1) == "\r") {
      $source_lineendings = " Windows (CRLF) ";
    }else {
      $source_lineendings = " Unix (LF) ";    
    }
  }
  
  // Remove password and user from config.php, if enabled
  if($HIDE_DB_USER_PASSWORD == TRUE && 
     ($source_file == 'config.php' || $source_file == 'config.php~')) {

    $source_pattern[0]   = '/(DB_PASSWORD|DB_USER)(.+)/';
    $source_replace[0]   = '/* <em>\1,  is removed and hidden for security reasons </em> */ );';
    
    $source_content = preg_replace($source_pattern, $source_replace, $source_content);
  }
  
  //
  // Display image if a valid image file
  //
  $source_pathParts = pathinfo($source_dir . $SEPARATOR . $source_file);
  $source_extension = isset($source_pathParts['extension']) ? strtolower($source_pathParts['extension']) : '';

  //
  // Display svg-image or enable link to display svg-image.
  //
  $source_linkToDisplaySvg = "";
  if($source_extension == 'svg') {
    if(isset($_GET['displaysvg'])) {
      header("Content-type: image/svg+xml");
      echo $source_content;
      exit;    
    } else {
      $source_linkToDisplaySvg = "<a href='{$_SERVER['REQUEST_URI']}&displaysvg'>Display as SVG</a>";
    }
  }
  
  //
  // Display image if a valid image file
  //
  if(in_array($source_extension, $IMAGES)) {
    if(empty($sourceSubDir) && empty($source_currentdir)) {
      $source_imgSrc = $source_file;
    } else {
      $source_imgSrc = "{$sourceSubDir}{$source_currentdir}/{$source_file}";
    }
    $source_content = "<div style='overflow:auto;'><img src='{$source_imgSrc}' alt='[image not found]'></div>";

  //
  // Show syntax if defined
  //
  } elseif($SYNTAX == 'PHP') {
    $source_content = str_replace("\t", $SPACES, $source_content);
    $source_content = highlight_string($source_content, TRUE);
    $source_sloc = 0;
    $source_i=0;
    $source_rownums = "";
    $source_text = "";
    $source_a = explode('<br />', $source_content);    
    foreach($source_a as $source_row) {
      $source_i++;
      $source_sloc += (empty($source_row)) ? 0 : 1;
      $source_rownums .= "<code><a id='L{$source_i}' href='#L{$source_i}'>{$source_i}</a></code><br />";
      $source_text .= $source_row . '<br />';
    }
    $source_content = <<< EOD
<div class='container'>
<div class='header'>
<!-- {$source_i} lines ({$source_sloc} sloc) -->
<code>{$source_i} lines  {$source_encoding} {$source_lineendings} {$source_linkToDisplaySvg}</code>
</div>
<div class='rows'>
{$source_rownums}
</div>
<div class='code'>
{$source_text}
</div>
</div>
EOD;
  } 
  
  //
  // DEFAULT formatting
  //
  else {
    $source_content = htmlspecialchars($source_content);
    $source_content = "<pre>{$source_content}</pre>";
  }
  
  $source_html .= <<<EOD
<h3 id="file"><code><a href="#file">{$source_file}</a></code></h3>
{$source_content}
EOD;
}



// -------------------------------------------------------------------------------------------
//
// Create and print out the html-page
//
$source_pageTitle = "Show sourcecode";
$source_pageCharset = "utf-8";
$source_pageLanguage = "en";
$sourceBody=$source_html;
$sourceStyle=<<<EOD
     div.container {
      min-width: 40em;
     }
     div.header {
      color: #000;
      border: solid 1px #999;
      border-bottom: 0px;
      background: #eee;
      padding: 0.5em 0.5em 0.5em 0.5em;
    }  
     div.rows {
       float: left;
       text-align: right;
      color: #999;
      border: solid 1px #999;
      background: #eee;
      padding: 0.5em 0.5em 0.5em 0.5em;
    }  
    div.rows a:link,
    div.rows a:visited,
    div.rows a:hover,
    div.rows a:active  { 
      text-decoration:none; 
      color: inherit;
    }
     div.code {
       white-space: nowrap;
      border: solid 1px #999;
      background: #f9f9f9;
      padding: 0.5em 0.5em 0.5em 0.5em;
      overflow:auto;
    }
EOD;


if(!isset($sourceNoEcho)) {
  // Print the header and page
  header("Content-Type: text/html; charset={$source_pageCharset}");
  echo <<<EOD
<!DOCTYPE html>
<html lang="{$source_pageLanguage}">
<head>
  <meta charset="{$source_pageCharset}" />
  <title>{$source_pageTitle}</title>
  <meta name="robots" content="noindex" />
  <meta name="robots" content="noarchive" />
   <style>{$sourceStyle}</style>
  <!--[if IE]> 
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>    
  <![endif]-->
</head>
<body>
  {$sourceBody}
</body>
</html>  
EOD;

  exit;
}