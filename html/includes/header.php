<?

// if site has not been configured, print "friendly" message
if(!is_file("libs/config.php")){
  include('includes/strings/en/index_content_default.php');
  print $content;
  exit;
}
require "libs/xname.php";

$config = new Config();

if (strlen($_SERVER['SCRIPT_URI'])>0 && substr($_SERVER['SCRIPT_URI'], 0, 5) != "https") {
  Header("Location: " . $config->mainurl);
  exit;
}

// protect variables for db usage
if(isset($_REQUEST)){
  if(isset($_REQUEST['idsession'])){
    $idsession=$_REQUEST['idsession'];
  }else{
    $idsession='';
  }
  if(isset($_REQUEST['login'])){
    $login=addslashes($_REQUEST['login']);
  }else{
    $login='';
  }
  if(isset($_REQUEST['password'])){
    $password=addslashes($_REQUEST['password']);
  }else{
    $password='';
  }
  if(isset($_REQUEST['logout'])){
    $logout=$_REQUEST['logout'];
  }else{
    $logout=0;
  }
}else{
  if(isset($idsession)){
    $idsession=$idsession;
  }else{
    $idsession='';
  }
  if(isset($login)){
    $login=addslashes($login);
  }else{
    $login='';
  }
  if(isset($password)){
    $password=addslashes($password);
  }else{
    $password='';
  }
  if(!isset($logout)){
    $logout=0;
  }

}

$html = new Html();




$db = new Db();
if($config->userdbname){
  $dbauth = new DbAuth();
}else{
  $dbauth = $db;
}
$deflangused = 0;
if(isset($_REQUEST)){
  if(isset($_REQUEST['language']) && !empty($_REQUEST['language'])){
    $lang = $_REQUEST['language'];
  }else{
    if(isset($_REQUEST['newlang']) && !empty($_REQUEST['newlang'])){
      $lang = $_REQUEST['newlang'];
    }else{
      $lang = $config->defaultlanguage;
      $deflangused = 1;
    }
  }
}else{
  if(isset($language) && !empty($language)){
    $lang=$language;
  }else{
    if(isset($newlang) && !empty($newlang)){
      $lang = $newlang;
    }else{
      $lang = $config->defaultlanguage;
      $deflangused = 1;
    }
  }
}
$lang=substr($lang, 0, 2);
if (!is_file('includes/strings/' . $lang . '/strings.php'))
  $lang='en';

include 'includes/strings/' . $lang . '/strings.php';
$html->initialize();
$user = new User($login,$password,$idsession);

// use $idsession in all urls, including first page
if(empty($idsession) && $user->authenticated){
  header("Location: " . $config->mainurl
    . ereg_replace("^/","", $_SERVER['PHP_SELF'])
    . "?idsession=" . $user->idsession);
}
if(!$user->authenticated && $idsession){
  header("Location: " . $config->mainurl
    . ereg_replace("^/","", $_SERVER['PHP_SELF']));
}

// overwrite default strings
if ($deflangused){
if(isset($user->lang)){
  $lang = $user->lang;
}else{
  if(isset($_REQUEST) && isset($_REQUEST['language'])){
    $lang = $_REQUEST['language'];
  }else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
    $lang = ereg_replace(",.*", "", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
  }else{
    if(isset($language)){
      $lang=$language;
    }
  }
}
}
$lang=substr($lang, 0, 2);
// verify if language exists !
if(!is_file('includes/strings/' . $lang . '/strings.php')){
  $lang = $config->defaultlanguage;
}

include 'includes/strings/' . $lang . '/strings.php';

// reinitialize with definitive right language
$html->initialize();

if((isset($_REQUEST['logout']) && $_REQUEST['logout']) || (isset($logout) && $logout)){
  $user->logout($idsession);
  Header("Location: " . $_SERVER['SCRIPT_URI'] . "?language=" . $lang);
}


print $html->header($l[$page_title]);

if($config->usergroups){
  include 'libs/group.php';
  $group = new Group($user->userid);
  if($config->userlogs){
    include 'libs/userlogs.php';
    $userlogs=new UserLogs($group->groupid,$user->userid);
  }
}


if(empty($idsession)){
  $idsession=$user->idsession;
}

if(!empty($idsession)){
  $link="?idsession=" . xssafe($idsession);
  $hiddenfields = '<input type="hidden" name="idsession" value="' . xssafe($idsession) . '">';
  // add language only if different
  if(strcmp($lang,$user->lang)){
    $link .= "&amp;language=" . $lang;
    $hiddenfields .= '<input type="hidden" name="language" value="' . $lang . '">';

  }
}else{
  $link="?language=" .$lang;
}

print $html->subheader($link);


if($user->error){
  print $html->box('mainerror', $l['str_error'], sprintf($html->string_error,$user->error));
}


?>
