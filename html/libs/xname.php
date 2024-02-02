<?
/*
  This file is part of XName.org project
  See  http://www.xname.org/ for details

  License: GPLv2
  See LICENSE file, or http://www.gnu.org/copyleft/gpl.html

  Author(s): Yann Hirou <hirou@xname.org>

*/

include 'libs/html.php';
include 'libs/config.php';
include 'libs/db.php';
include 'libs/dbauth.php';
include 'libs/auth.php';
include 'libs/user.php';
include 'libs/zone.php';
include 'libs/primary.php';
include 'libs/secondary.php';
include 'libs/stats.php';


// **********************************************************
// Utilities


// function mailer($from, $to, $subject, $headers, $message)
//     send email
/**
 * Send an email
 *
 *@param string $from sender email address
 *@param string $to recipient email address
 *@param string $subject subject of email
 *@param string $headers additional headers
 *@param string $message body of email
 *@return int 1 if success, 0 if error
 */
function mailer($from, $to, $subject, $headers, $message){
  global $config;
  // TODO : verify mail by sending it directly, after
  // a connexion on MX
  // has to be done using vrfyEmail()

  // returns 0 if fails, 1 if succeed
  $mailcontent = "From: $from
To: $to
Subject: $subject
$headers

$message
";
  if($fd = popen($config->binsendmail, "w")){
    fwrite($fd, $mailcontent);
    if(pclose($fd)){
      return 0;
    }
    return 1;
  }else{
    return 0;
  }
}




// function randomID()
/**
 * Generate random ID to be used for sessionID & recovery ID
 *
 *@return int random number
 */
function randomID(){
        $datetime = md5(date("Y-m-d H:i:s"));
        $ip = md5(getenv("REMOTE_ADDR"));
        $session = md5($datetime . $ip);
  return $session;
}

// **********************************************************
// DB utilities

  /**
   * Return list of all server names
   *
   *@access public
   *@return array list of all server names or 0 if error
   */
  Function GetListOfServerNames($mandatory = 0) {
    global $db;

    $query = "SELECT servername FROM dns_server";
    if ($mandatory == 1)
      $query .= " WHERE mandatory=1";
    // $query .= " ORDER BY id DESC";
    $res = $db->query($query);
    if($db->error()){
      return 0;
    }
    $result = array();
    while($line = $db->fetch_row($res)){
      array_push($result,$line[0]);
    }
    return $result;
  }

  /**
   * Return list of all server IPs
   *
   *@access public
   *@return array list of all server IPs or 0 if error
   */
  Function GetListOfServerIPs() {
    global $db;

    $query = "SELECT serverip FROM dns_server";
    $res = $db->query($query);
    if($db->error()){
      return 0;
    }
    $result = array();
    while($line = $db->fetch_row($res)){
      array_push($result,$line[0]);
    }
    return $result;
  }

  /**
   * Return list of all server transfer IPs
   *
   *@access public
   *@return array list of all server IPs for transfer or 0 if error
   */
  Function GetListOfServerTransferIPs() {
    global $db;

    $query = "SELECT transferip FROM dns_server";
    $res = $db->query($query);
    if($db->error()){
      return 0;
    }
    $result = array();
    while($line = $db->fetch_row($res)){
      array_push($result,$line[0]);
    }
    return $result;
  }

// **********************************************************
// Availability of services
// ToBeDone

// function av_sslInterface()
function av_sslInterface(){
}

// function av_mailInterface()
function av_mailInterface(){
}

// function av_db(){
function av_db(){
}

// function av_primaryRegistration()
function av_primaryRegistration(){
}

// function av_secondaryRegistration()
function av_secondaryRegistration(){
}

// function av_primaryModification()
function av_primaryModification(){
}

// function av_secondaryModification()
function av_secondaryModification(){
}

// function av_logViewer()
function av_logViewer(){
}

// **********************************************************
// Checkers

// function checkIP($string)
/**
 * Check if IP sounds good - 4 positive numbers <= 255 separated by dots
 *
 *@param string $string IP address to check
 *@return int 0 or 1 if bad or valid IP
 */
function checkIP($string){
  if((strspn($string, "0123456789.") != strlen($string)) ||
  (count(explode('.' ,$string)) != 4)){
    $result = 0;
  }else{
    list($octet1,$octet2,$octet3,$octet4) = explode('.' ,$string);
    if(($octet1 > 255)||($octet2 > 255)||($octet3 > 255)||($octet4 > 255)){
      $result = 0;
    }else{
      $result = 1;
    }
  }

  return $result;
}


// function checkIPv6($string)
/**
 * Check if IPv6 sounds good - hexa numbers separated by dots or :
 *
 *@param string $string IP address to check
 *@return int 0 or 1 if bad or valid IPv6
 */
function checkIPv6($string){
  if(preg_match("/\./",$string)){
    if(preg_match("/[^a-f0-9\.]/i",$string)){
      $result = 0;
    }else{
      $result = 1;
    }
  }else{
    // 8 bytes
    if(preg_match("/([a-f0-9]+)((:[a-f0-9]+){7})/i",$string)){
      $result = 1;
    }else{
      // cf http://groups.google.com/groups?hl=en&lr=&ie=UTF-8&threadm=39889325.7F053765%40west.sun.com&rnum=1
      if(preg_match("/:::|[A-F0-9]{5}|[^A-F0-9:]|^:[^:]|[^:]:$|.*:.*:.*:.*:.*:.*:.*:.*:.*|^::[^:]+::[^:]+::$|^::[^:]+::[^:]{0,4}$|^[^:]+::[^:]+::[^:]{0,4}$/i",$string)){
        $result = 0;
      }else{
        $result = 1;
      }
    }
  }

  return $result;
}


// function checkDomain($string)
/**
 * Check if  name has only valid char, without dot as 1st char
 *
 *@param string $string zone name to be checked
 *@return int 1 if valid, 0 else
 */
function checkDomain($string){
  $string = strtolower($string);
  // only specified char AND only one . (no sub-zones)
  if((strspn($string, "0123456789abcdefghijklmnopqrstuvwxyz-.") !=
  strlen($string)) || (strpos('0'.$string,".") == FALSE)||
  (strpos('0'.$string,".") == 1)){
    $result = 0;
  }else{
    $result = 1;
  }
  return $result;
}

// function checkZone($string)
/**
 * Check if zone name has only valid char, without dot as 1st char
 * Zone name must have only [a-z] char at the end.
 *@param string $string zone name to be checked
 *@return int 1 if valid, 0 else
 */
function checkZone($string){
  $string = strtolower($string);
  // only specified chars
  if (preg_match("/[^a-z0-9\.\/_-]/i", $string))
    return 0;
  // at least one dot
  if (strpos($string, ".") === FALSE)
    return 0;
  // beginning dot or / or -
  if (preg_match("/^[\/\.-]/", $string))
    return 0;
  // ending char
  if (!preg_match("/[a-z]$/i", $string))
    return 0;
  // two consecutive dots
  if (strpos($string, "..") !== FALSE)
    return 0;

  return 1;
}


// function checkName($string)
/**
 * Check if name has only valid char
 *
 *@param string $string name to be checked
 *@return int 1 if valid, 0 else
 */
function checkName($string){
  $string = strtolower($string);
  // only specified char
  if(strspn($string, "0123456789abcdefghijklmnopqrstuvwxyz-") !=
  strlen($string)){
    $result = 0;
  }else{
    $result = 1;
  }
  return $result;
}



// function checkPrimary($string)
/**
 * Check if given string is a list of IP addresses or not, ';' separated
 *
 *@param string $string string to be checked
 *@return 1 if valid, 0 else
 */

function checkNet($string) {
  if (ereg("(.*)/([0-9][0-9]{0,2})", $string, $net)) {
    $string = $net[1];
    $net = $net[2];
  } else {
    $net = 0;
  }
  $ip = inet_pton($string);
  if ($ip === FALSE) {
    return 0;
  }
  if ($net) {
    if (strlen($ip) == 4 and $net > 32) { return 0; }
    if (strlen($ip) == 16 and $net > 128) { return 0; }
  }
  return 1;
}

function checkPrimary($string){
  $primarylist = explode(';', trim($string, ";"));
  while(list($key,$value) = each($primarylist)){
    if (!checkNet(trim($value, " "))) {
      return 0;
    }
  }
  return 1;
}

// function checkEmail($string)
/**
 * Check if email looks valid or not
 *
 *@param string $string email to check
 *@return int 1 if valid, 0 else
 */
function checkEmail($string) {
  return preg_match("/^[^@ ]+@[^@ ]+\\.[^@ ]+$/", $string);
}

// function vrfyEmail($string)
//     look for MX, check accept domain
/**
 * Verify email by looking for MX record for domain
 *
 *@param string $string email to check
 *@return string 1 if valid, "No valid MX record found" else
 */
function vrfyEmail($string){
  global $l;

  $host = substr(strstr($string,'@'),1);
  if (strtolower($host)=="nowmymail.com") return $l['str_no_valid_mx_record'];
  if (strtolower($host)=="meltmail.com") return $l['str_no_valid_mx_record'];
  if (strtolower($host)=="mailinator.com") return $l['str_no_valid_mx_record'];
  if(!getmxrr($host, $mxhosts) && !checkdnsrr($host, "A")){
    // no valid MX record
    return $l['str_no_valid_mx_record'];
  }else{
    return 1;
  }
}


// function checkDig($server,$zone)
//     try a zone transfer from $server for $zone
/**
 * Try a dig and return result
 *
 *@param string $server name of the DNS server to dig on
 *@param string $zone zone name to dig
 *@return string effective status or "connection timed out" or "unknown problem"
 */
function checkDig($server,$zone){
  global $config;
  $server = escapeshellarg($server);
  $zone = escapeshellarg($zone);
  $cmd = escapeshellcmd("$config->bindig soa $zone @$server -b '$config->nsaddress'");
  $result = shell_exec($cmd);

  // check if status:*
  // return *
  // if "connection timed out" return "connection timed out"

  if(ereg("status: ([[:alnum:]]+),",$result,$status)){
    return $status[1];
  }else{
    if(ereg("connection timed out",$result)){
      return "connection timed out";
    }else{
      return "unknown problem";
    }
  }
}

// **********************************************************


// function DigSerial($server,$zone)
// retrieve serial for zone on server
/**
 * Retrieve serial of a zone on specified server using 'dig'
 *
 *@param string $server server to query
 *@param string $zone zone name to query for
 *@return string serial number of zone or "not available"
 */
function DigSerial($server,$zone){
  global $config, $l;
  if (!checkIP($server) && !checkDomain($server))
    return $l['str_not_available'];
  $server = escapeshellarg($server);
  $zone = escapeshellarg($zone);
  $cmd = escapeshellcmd("$config->bindig @$server $zone soa -b '$config->nsaddress' +short");
  $result = shell_exec($cmd);
  if(ereg("try again",$result)){
    return $result;
  }else{
    preg_match("/^[^;\s\t]+ [^\s\t]+ ([0-9]+) .*/", $result, $serial);
    if(isset($serial[1])){
      return $serial[1];
    }else{
      return $l['str_not_available'];
    }
  }
}


// function zoneDig($server,$zone)
/**
 * Do an axfr dig of a zone
 *
 *@param string $server server to dig
 *@param string $zone zone to dig
 *@return string dig result
 */
function zoneDig($server,$zone){
  global $config;
  if (!checkIP($server) && !checkDomain($server))
    return "";
  $server = escapeshellarg($server);
  $zone = escapeshellarg($zone);
  $cmd = escapeshellcmd("$config->bindig @$server $zone axfr -b '$config->nsaddress'");
  $result = shell_exec($cmd);
  return $result;
}


// function retrieveArgs($name, $httpvars)
/**
 * Retrieve arguments from an associative array where
 * arguments names are incremental - for example foo1,foo2,foo3
 * and return an array with all values
 *
 *@param string $name non-incremental part of var name (example: foo in foo1)
 *@param array $httpvars associative array to be parsed - example $HTTP_GET_VARS
 *@return array array of values
 */
function retrieveArgs($name, $httpvars){
  $result = array();
  foreach($httpvars as $key => $value) {
     if (preg_match("/^".$name."[0-9]+$/", $key)) {
       array_push($result, $value);
     }
  }
  return $result;
}

// *******************************************************
// function diffDate($date)
  // returns time between now and date YYYY MM DD HH mm ss
  // in sec.
/**
 * Returns time between given date and now in seconds
 *
 *@param string $date date, format YYYYMMDDHHmm or YYYY-MM-DD HH:mm:ss
 *@return int number of seconds
 */
function diffDate($date){
  // $date : YYYYMMDDHHmmss (MySQL 3)
  // or $date : YYYY-MM-DD HH:mm:ss (MySQL 4)

  $nowts=mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("y"));

  // MySQL 3
  if(preg_match("/^[^-]*$/",$date)){
    $year=substr($date,0,4);
    $month=substr($date,4,2);
    $day=substr($date,6,2);
    $hour=substr($date,8,2);
    $min=substr($date,10,2);
    $sec=substr($date,12,2);
  }else{
    // MySQL 4
    if(preg_match("/^....-..-.. ..:..:..$/",$date)){
      $year=substr($date,0,4);
                        $month=substr($date,5,2);
                        $day=substr($date,8,2);
                        $hour=substr($date,11,2);
                        $min=substr($date,14,2);
                        $sec=substr($date,17,2);
                }
  }
  $datets=mktime($hour,$min,$sec,$month,$day,$year);

  return $nowts - $datets;
}

// function nowDate()
/**
 * returns current date formated YYYYMMDDHHmm
 *
 *@return int current date formated YYYYMMDDHHmm
 */
  function nowDate(){
  // $date : YYYY MM DD HH mm
  $now=strftime("%Y%m%d%H%M%S");
  return $now;

  }


// Function dateToTimestamp($date)
  /**
   * Returns date YYYYMMDDHHmmss formated into timestamp
   *
   *@return int date YYYYMMDDHHmmss formated into timestamp
   */
  Function dateToTimestamp($date){
          $year=substr($date,0,4);
    $month=substr($date,4,2);
    $day=substr($date,6,2);
    $hour=substr($date,8,2);
    $min=substr($date,10,2);
    $sec=substr($date,12,2);
    $datets=mktime($hour,$min,$sec,$month,$day,$year);
    return $datets;
  }


// Function timestampToDate($timestamp)
  /**
   * Returns epoch timestamp formated into YYYYMMDDHHmmss
   *
   *@return int timestamp formated into YYYYMMDDHHmmss
   */
  Function timestampToDate($timestamp){
    $datearray = getdate($timestamp);
    $year = $datearray['year'];
    $month = $datearray['mon'];
    $day = $datearray['mday'];
    $hour = $datearray['hours'];
    $min = $datearray['minutes'];
    $sec = $datearray['seconds'];
    if($day < 10){
      $day = "0" . $day;
    }
    if($month < 10){
      $month = "0" . $month;
    }
    if($hour < 10){
      $hour = "0" . $hour;
    }
    if($min < 10){
      $min = "0" . $min;
    }
    if($sec < 10){
      $sec = "0" . $sec;
    }

    $result = $year.$month.$day.$hour.$min.$sec;

    return $result;
  }


// *******************************************************
//  Function getSerial($previous)
/**
 * build zone serial number based on timestamp
 * fallback to previous plus one, if needed
 *
 *@param int $previous previous serial number, may be empty
 *@return generated serial number
 */
  Function getSerial($previous=0){
    $serial = time();
    if (!empty($previous) && $previous > $serial){
        $serial = $previous + 1;
    }
    return $serial;
  }



// **********************************************************


// function GetDirList($dir)
// list directory content
/**
 * retrieve content of given directory in a list
 *
 *@param string $dir directory to be listed
 *@return list $list list of items in directory
 */
function GetDirList($dir){
  global $config;
    $list = array();
    if ($handle = opendir($dir)){
    while (false !== ($file = readdir($handle))) {
      if (ereg("^[a-z][a-z]$", $file) && file_exists($dir."/".$file."/strings.php")){
        array_push($list, $file);
      }
    }
  }
  return $list;
}

// function ConvertIPv6toDotted($string,$bytes = 32)
/**
 * convert $string into IPv6 nibble format
 *
 *@param string $string IPv6 any format
 *@param int $bytes number of bytes in address
 *@return string $ipv6 ipv6 address in nibble format
 */
function ConvertIPv6toDotted($string, $bytes = 32){
  if(ereg(":",$string)){
    $ipsplit = split(":",$string);
    $newiparray = array();
    if(count($ipsplit) < $bytes / 4){
      // total: 8 fields separated by ":"
      reset($ipsplit);
      while(list($null,$ipitem) = each($ipsplit)){
        if($ipitem == ''){
          for($count=0;$count < ($bytes / 4) +1 - count($ipsplit);$count++){
            array_push($newiparray,'0000');
          }
        }else{
          array_push($newiparray,$ipitem);
        }
      }
    }else{
      $newiparray = $ipsplit;
    }
    $ipsplit = array();
    reset($newiparray);
    while(list($null,$ipitem) = each($newiparray)){
      while(strlen($ipitem) < 4){
        $ipitem = '0' . $ipitem;
      }
      array_push($ipsplit,$ipitem);
    }
    // final: 32 char separated by dots
    $iplist = join('',$ipsplit);
    $ipsplit = preg_split('//',$iplist);
    array_pop($ipsplit);
    array_shift($ipsplit);
    $ip = join('.',$ipsplit);
    return $ip;
  }else{
    return $string;
  }
}

function urlpop($url) {
  return $url . '" class="linkcolor" ' .
         'onclick="window.open(\'' . $url .
         '\',\'M\',\'toolbar=no,location=no,directories=no,status=no,' .
         'alwaysraised=yes,dependant=yes,resizable=yes,menubar=no,' .
         'scrollbars=yes,width=640,height=480\'); return false';
}

function xssafe($data) {
  return htmlspecialchars($data, ENT_QUOTES|ENT_HTML401);
}
