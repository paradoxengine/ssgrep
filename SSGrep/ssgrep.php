#!/usr/bin/php
<?php

/*
   SSGrep - Smart Security Grep

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

/*****Global variables *****/
//Script version
$GLOBALS['version']="0.11";
//Knowledge Base
$GLOBALS['kb']="ALL";
//Default output
$GLOBALS['output']="STDOUT";
$GLOBALS['outputFile']="output.html";
//Be verbose!
$GLOBALS['verbose']=FALSE;
/*********************/
// Turn off all error reporting
error_reporting (0);
// Check the colors compability
if($_ENV["SHELL"]=="/bin/bash"){$GLOBALS['colors']=TRUE;}

if($GLOBALS['colors']){echo "\n\033[35;1m.:[ \033[34mSmart Security Grep\033[35;1m ]:.\033[0m\n";}
else{echo "\n.:[ Smart Security Grep ]:.\n";}

$start=time();
//Get the cli arguments
$args = arguments($argv);

//Manage the different options and check for invalid options
$valid=array ("kb" => "", "l" => "", "o" => "", "v" => "", "h" => "", "i" => "");
if($args[h] || count($args[i])<1|| count(array_diff_key($args,$valid))!=0){
	 showHelp();
	 exit(0);
}

if($args[v]){
	$GLOBALS['verbose']=TRUE;
}else{
	$GLOBALS['verbose']=FALSE;
}

if(!is_null($args[o])){
$extension = substr($args[o],strrpos($args[o], "."));
	switch ($extension) {
		case ".html":
		case ".HTML":
        		$GLOBALS['output']="HTML";
        		$GLOBALS['outputFile']=$args[o];
        		break;
    		default:
    			echo "\n--o is possible just for HTML files\n\n";
    			exit(0);
	}
}

if (!is_null($args[l]) ){
	switch ($args[l]) {
		case "eng":
		case "ENG":
        		$GLOBALS['language']="ENG";
        		break;
		case "ita":
 		case "ITA":
        		$GLOBALS['language']="ITA";
        		break;
		case "all":
 		case "ALL":
        		break;
    		default:
    			echo "\n--l possible values are \"eng\",\"ita\",\"all\".\n I will use the default keyword \"all\".\n";
	}
}

if (!is_null($args[kb]) ){
	switch ($args[kb]) {
		case "java":
		case "j":
		case "JAVA":
		case "J":
				$GLOBALS['kb']="JAVA";
				break;
		case "sensitive":
		case "s":
		case "SENSITIVE":
		case "S":
				$GLOBALS['kb']="SENSITIVE";
				break;
		case "lamer":
		case "l":
		case "LAMER":
		case "L":
				$GLOBALS['kb']="LAMER";
				break;
		case "misc":
		case "m":
		case "MISC":
		case "N":
				$GLOBALS['kb']="MISC";
				break;
		case "all":
		case "a":
		case "ALL":
		case "A":
				$GLOBALS['kb']="ALL";
				break;
		default:
				echo "\n--kb possible values are \"java\",\"sensitive\",\"lamer\",\"misc\",\"all\".\n I will use the default keyword \"all\".\n";
				$GLOBALS['kb']="ALL";
	}
}

//Core Function

/*
	* Recursively open all files
	* - create an array with all files
	* - open each file
	* - analyze each row
	*/
$files = array();
$folders = array();
foreach($args[i] as $sarg){
	if(is_file($sarg)){
		//User gives me a single file
		$files[] = $sarg;
	}elseif(is_dir($sarg)){
		//User gives me a dir
		if($sarg==".") $sarg="./";
		$folders = recursive_subfolders($folders,$sarg);
		$folders[] = $sarg;
		foreach($folders as $folder){
			if ($handle = opendir($folder)){ 
				while (false !== ($file = readdir($handle))) {
				if(substr($folder,$strleng-1,1)!="/") $folder=$folder."/";
				if ($file != "." && $file != ".." && is_file($folder.$file)) {
						$files[]=$folder.$file;
				}
				}
			closedir($handle);
			}
		}
	}else{
		if($GLOBALS['colors']){echo "\n\033[5;38mFatal Error! \033[0m The resource \"".$sarg."\" doesn't exist.\n\n";}	
		else{echo "\nFatal Error! The resource \"".$sarg."\" doesn't exist.\n\n";}
		exit(-1);
	}
}

if($GLOBALS['verbose']){ 
	if(count($files)>1) echo("\nParsing Done! SSGrep will grep ".count($files)." files.");
	else echo("\nParsing Done! SSGrep will grep ".count($files)." file.");
}

$kbhandle=array();
if($GLOBALS['kb']=="ALL"){
	if ($kbhandle = opendir(getcwd()."/".substr($argv[0],0,strlen($argv[0])-6)."data/")){ 
		while (false !== ($allkb = readdir($kbhandle))) {
			if ($allkb != "." && $allkb != ".." && substr($allkb,strpos($allkb,"."),3)==".kb") {
				$kbfiles[]=getcwd()."/".substr($argv[0],0,strlen($argv[0])-6)."data/".$allkb;
			}
		}
	closedir($kbhandle);
	}
}else{
	$kbfiles[]=getcwd()."/".substr($argv[0],0,strlen($argv[0])-6)."data/".strtolower($GLOBALS['kb']).".kb";
}

if($GLOBALS['verbose']){ 
	if(count($kbfiles)>1) echo("\n\nSSGrep will use ".count($kbfiles)." Knowledge Base files.\n");
	else echo("\n\nSSGrep will use ".count($kbfiles)." Knowledge Base file.\n");
}

//Open every KB selected, create an array for each category
$keywords=array();
foreach($kbfiles as $kbfile){
	$kbhandle = @fopen($kbfile,"r") or die("Opening KB file error");
	if($kbhandle){
		while (!feof($kbhandle)) {
			$kbbuffer = fgets($kbhandle, 4096); //single kb line
			if(strlen($kbbuffer)!=0){
				//Check the language selection
				if(substr($kbbuffer,1,3)=="***" || substr($kbbuffer,1,3)==$GLOBALS['language']){
					$keywords[substr($kbfile,strripos($kbfile,"/")+1,strlen(substr($kbfile,strripos($kbfile,"/")+1))-3)][]=ereg_replace("/\n\r|\r\n|\n|\r/", "",substr($kbbuffer,5));
				}
			}
		}
	}
	fclose($kbhandle);
}

if($GLOBALS['verbose']){echo("\nLoading KB Done!");}

echo("\n\nWorking...");

//String matching structure
$strm=array();
//Grep for the selected KB and language
foreach($files as $file){
	if($GLOBALS['verbose']) echo("\n$file");
	else echo("."); //time indicator
	$count=0;
	$handle = @fopen($file,"r") or die("Opening file error");
	if($handle){
		while (!feof($handle)) {
			$line = fgets($handle, 4096); //single file line
			$line = ereg_replace("/\n\r|\r\n|\n|\r/", "", $line); //remove carriage return
			$count++;
			if(strlen($line)!=0){
				//For each keywords, grep the line
				foreach($keywords as $k => $s){
					foreach($s as $ssingle){
						if(eregi($ssingle,$line,$regs)!=FALSE){
							//Array of Array[file, line, line number, match, kb, language]
							$strm[] =  array ($file,$line,$count,$regs[0],$k." KB",$GLOBALS['language']);
						}
					}
				}
			}
		}
	}
	fclose($handle);
}

$end=time()-$start;
if($end==0) $end=1;

//Show results
switch ($GLOBALS['output']) {
	case "STDOUT":
		outputSTDOUT($strm);
		break;
	case "HTML":
		outputHTML($strm,count($files),count($kbfiles),count($keywords),$end);
		break;
	default:
		if($GLOBALS['colors']){echo "\n\033[5;38mFatal Error! \033[0m\n\n";}
		else{echo "\nFatal Error!\n\n";}
		exit(-1);
}

if($end==0 || $end==1) echo("\n\nFinished! ".count($strm). " results in 1 second.\n");
else echo("\n\nFinished! ".count($strm). " results in ".$end." seconds.\n");

echo "\n";
exit(0); //end of program

function outputHTML($out,$nfiles,$nkbfiles,$nkeywords,$end){

$handle = @fopen($GLOBALS['outputFile'],"w+") or die("Opening file error");
if($handle){
	if(count($out)!=0){
		$strdata= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n".
				"<html>\n".
				"<head>\n".
				"<meta content=\"text/html; charset=ISO-8859-1\" http-equiv=\"content-type\">\n".
				"<title>SSGrep v".$GLOBALS['version']." - Results</title>\n".
				"</head>\n".
				"<body style=\"color: rgb(0, 0, 0); background-color: #336666;\" alink=\"#000099\" link=\"#000099\" vlink=\"#990099\">\n".
				"<span style=\"font-family: Arial, Helvetica, Geneva; font-size: 12px; color:#A6C6ED; line-height: 17px;\">\n".
				//HTML page header
				"<b>SSGrep Results</b> (".htmlentities(count($out))." items).<br>\n".
				"Grepping ".htmlentities($nfiles);
		if($nfiles==1) $strdata=$strdata." file using ".htmlentities($nkbfiles);
		else $strdata=$strdata." files using ".htmlentities($nkbfiles);
		if($nkbfiles==1)$strdata=$strdata." KB. It tooks ".htmlentities($end)." sec."; 
		else $strdata=$strdata." KBs. It tooks ".htmlentities($end)." sec.\n";
		$strdata=$strdata."<br><hr style=\"width: 100%; height: 1px; border-style: dotted\"><br><ul>\n";
		foreach($out as $match){
			$strdata=$strdata."<li><a href=\"file://".htmlentities($match[0])."\">".htmlentities($match[0])."</a>   Match:\"".htmlentities($match[3])."\"\n".
					"<br>See details <a href=\"#".htmlentities($match[0])."\">HERE</a></li>\n";
		}
		$strdata=$strdata."</ul><br>\n".
				"<hr style=\"width: 100%; height: 1px; border-style:dotted; border-width:1px 0 0 0; border-color:#A6C6ED;\"><br>\n";
				//HTML results
		foreach($out as $match){
			$strdata=$strdata."<a name=\"".htmlentities($match[0])."\"> In \"<a href=\"file://".htmlentities($match[0])."\">".htmlentities($match[0])."</a>\"\n".
					"<br>Match:\"".htmlentities($match[3])."\"\n".
					"<br>Found by ".strtoupper(htmlentities($match[4]))." using the language option ".htmlentities($match[5]).".\n".
					"<br><br>\n".
					str_replace("[br]","<br>",htmlentities(getLine($match[0],$match[2],5,5,"[br]"))).
					"<br><hr style=\"width: 100%; height: 1px; border-style:dotted; border-width:1px 0 0 0; border-color:#A6C6ED;\"><br>\n";
		}
		//HTML page footer
		$strdata=$strdata."<br>\n".
				"<hr style=\"width: 100%; height: 1px; border-style: dotted\">\n".
				"This file was generated by <a href=\"http://www.ikkisoft.com\">SSGrep</a>, the Smart Security Grep.\n".
				"</span>\n".
				"</body>\n".
				"</html>\n";
	}else{
		//"No Results" page
		$strdata= "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n".
				"<html>\n".
				"<head>\n".
				"<meta content=\"text/html; charset=ISO-8859-1\" http-equiv=\"content-type\">\n".
				"<title>SSGrep v".$GLOBALS['version']." - Results</title>\n".
				"</head>\n".
				"<body style=\"color: rgb(0, 0, 0); background-color: #336666;\" alink=\"#000099\" link=\"#000099\" vlink=\"#990099\">\n".
				"<span style=\"font-family: Arial, Helvetica, Geneva; font-size: 12px; color:#A6C6ED; line-height: 17px;\">\n".
				//HTML page header
				"<b>SSGrep Results</b> (".htmlentities(count($out))." items).<br>\n".
				"Grepping ".htmlentities($nfiles);
				if($nfiles==1) $strdata=$strdata." file using ".htmlentities($nkbfiles);
				else $strdata=$strdata." files using ".htmlentities($nkbfiles);
				if($nkbfiles==1)$strdata=$strdata." KB. It tooks ".htmlentities($end)." sec."; 
				else $strdata=$strdata." KBs. It tooks ".htmlentities($end)." sec.\n";
				$strdata=$strdata."<br><hr style=\"width: 100%; height: 1px; border-style: dotted\"><br>\n".
				"<br><br><b>No Results !!!</b><br><br>\n";
				//HTML page footer
				$strdata=$strdata."<br>\n".
				"<hr style=\"width: 100%; height: 1px; border-style: dotted\">\n".
				"This file was generated by <a href=\"http://www.ikkisoft.com\">SSGrep</a>, the Smart Security Grep.\n".
				"</span>\n".
				"</body>\n".
				"</html>\n";
	}
fwrite($handle, $strdata);	
}
fclose($handle);

//end of function outputHTML
}

function outputSTDOUT($out){

if(count($out)!=0){
	if($GLOBALS['colors']){echo "\n\n\033[34mResults: \033[0m\n\n";}
	else{echo "\n\nResults:\n\n";}
	foreach($out as $match){
		if($GLOBALS['colors']){
			echo "\033[35;1m*\033[0m";
                	echo " In \"\033[34m$match[0]\033[0m\": ";
                	echo "\033[35;1m$match[3]\033[0m\n";
                	echo getLine($match[0],$match[2],1,1,"\n");
		}
		else{
                	echo "*";
                	echo " In \"$match[0]\": ";
                	echo "$match[3]\n";
                	echo getLine($match[0],$match[2],1,1,"\n");
		}
	}
}else{
	if($GLOBALS['colors']){echo "\n\n\033[34mNo Results !!!\033[0m\n";}
	else{echo "\n\nNo Results !!!\n";}
}

//end of function outputSTDOUT
}

//Get the line number $num from the file $file, including $before/$after lines. Use $rc as carriage return.
function getLine($file, $num, $before, $after, $rc){

$content=file($file,FILE_IGNORE_NEW_LINES) or die("Opening file error");
$cont="1";
for ($i = $num-2; $i > $num-$before-2 && $i >= 0; $i--) {
    $lines="[-$cont]--- ".trim($content[$i]).$rc.$lines;
    $cont++;
}

$lines=$lines."[#$num]--- ".trim($content[$num-1]).$rc;

$cont="1";
for ($i = $num; $i < ($num+$after) && $i < count($content); $i++) {
    $lines=$lines."[+$cont]--- ".trim($content[$i]).$rc;
    $cont++;
}
return $lines;

//end of function getLine
}

function recursive_subfolders($folders,$path) {

if ($dir = opendir($path)) {
$j = 0;
while (($file = readdir($dir)) !== false) {
	if ($file != '.' && $file != '..' && is_dir($path.$file)) {
		$j++;
		$folders[$j] = $path . $file;
	}
}
}
closedir($dir);

$j = count($folders);
foreach ($folders as $folder) {
if ($dir = opendir($folder)) {
	while (($file = readdir($dir)) !== false) {
	$pathto = $folder. '/' . $file;
	if ($file != '.' && $file != '..' && is_dir($pathto) && !in_array($pathto, $folders)) {
		$j++;
		$folders[$j] = $pathto;
		$folders = recursive_subfolders($folders,".");
	}
	}
}
closedir($dir);
}
sort($folders);
return $folders;

//end of function recursive_subfolders
}

function arguments($argv) {

$_ARG = array();
foreach ($argv as $arg) {
	if (ereg('--[a-zA-Z0-9]*=.*',$arg)) {
	$str = split("=",$arg); $arg = '';
	$key = ereg_replace("-",'',$str[0]);
	for ( $i = 1; $i < count($str); $i++ ) {
		$arg .= $str[$i];
	}
			$_ARG[$key] = $arg;
	} elseif(ereg('--[a-zA-Z0-9]',$arg)) {
	$arg = ereg_replace("-",'',$arg);
	$_ARG[$arg] = 'true';
	} else{
	$_ARG[i] []= $arg;
	unset($_ARG[i][0]);
	}

}
return $_ARG;

//end of function arguments
}

function showHelp(){

echo "\nSSGrep v".$GLOBALS['version']." (c) 2007 by Ikki\n";
echo "\nUsage: ssgrep [options] <input resources>".
	"\n <input resource>. Required. Files, Directories, ecc.".
	"\n --kb=<knowledge base>. Optional. Available modes are:".
	"\n      j/java - Search for dangerous Java/JSP Methods".
	"\n      s/sensitive - Search for sensitive informations inside the source code".
	"\n      l/lamer - Search for lamer developers comments".
	"\n      m/misc - Search miscellaneous strings".
	"\n      a/all - Search all".
	"\n --l=<language>. Optional. Available modes are:".
	"\n     eng - Look for english keywords".
	"\n     ita - Look for italian keywords".
	"\n     all - Don't care about language".
	"\n --o=<output file>. Optional. Available output files are:". 
	"\n     .html - Show results in a comfortable HTML file".
	"\n --v. Optional. Show informations during the grep process".
	"\n --h. Optional. Display this help\n\n";
	
//end of function showHelp
}

?>
