<? 

//Generate all types of errors in the screen.
error_reporting(E_ALL);
ini_set("display_errors", "1"); // shows all errors
ini_set("log_errors", 1);

//This will create the LOGS in a file, for debug purpose.
$ll = null;
$ll = function ( $filelog, $ptxt ) {
  $fp = fopen( 'img/'.$filelog, 'a');
  if (!$fp) echo "Error! Couldn't open the file ".$filelog;
  fwrite($fp, '<br>'.date("y.m.d H:i:s").' :: ' .$ptxt);
  fclose($fp);
};

//Get the 5 parameters:
// * s - SESSION (just a number to be used as driving name for files)
// * u - USER (the Instagram username or the Instagram TAG, without #))
// * t - TYPE (this can be "u" for user, or "t" for tag.)
// * n - NUMBER of the post to retrieve.
// * v - VERSION (Being 1 or 2). This is optional.
$ses= htmlspecialchars($_GET["s"])   ;
$t  = htmlspecialchars($_GET["t"])   ;
$us = htmlspecialchars($_GET["u"])   ;
$num= htmlspecialchars($_GET["n"])   ;
$ver= htmlspecialchars($_GET["v"])   ;

$filelog='log'.$ses.'.log';

$ll( $filelog, '<pre>Parameters: ');
$ll( $filelog, 'ses = '.$ses);
$ll( $filelog, 't   = '.$t);
$ll( $filelog, 'us  = '.$us);
$ll( $filelog, 'num = '.$num);
$ll( $filelog, 'ver = '.$ver);

//Verify the inputs parameters
if ($ses=='') 
  { echo "ERROR01-Session number is mandatory";
    exit;
  }
if (! is_numeric($ses) )
  { echo "ERROR03-Session should be a number";
    exit;
  }
if ($us =='')
  { echo "ERROR02-User is mandatory";
    exit;
  }

//Create default values
if ($t  =='') $t='u';
if ($num=='' ) $num=0;
else $num=$num-1;
if ($ver=='') $ver=1;

//======================
// Retrieve JSON file
//======================
$jsonfile = 'json'.$ses.'.txt'; 

//Open the correct URL depending of the type.
if     ($t=='u') $url = "https://www.instagram.com/$us/?__a=1";
elseif ($t=='t') $url = "https://www.instagram.com/explore/tags/$us/?__a=1";

else exit;

$oo = shell_exec("touch img/".$jsonfile );
$oo = shell_exec("chmod 666 img/".$jsonfile);
$cmd = 'curl "'.$url.'">img/'.$jsonfile;
$oo = shell_exec( $cmd );

//read File and save to variable
$json = file_get_contents( 'img/'.$jsonfile);

//Decode JSON response
$arr = json_decode($json, true);

//=====================
// Retrieve information from JSON
//=====================

//If type=U, saves some extra info from the USER.
if ($t=='u')
{
  //Create "Name of the User (username)"
  $userinfo = $arr["graphql"]["user"]["full_name"] . ' ('.$arr["graphql"]["user"]["username"].')';
  //echo $a.$arr["graphql"]["user"]["profile_pic_url"];
  //echo 'Followed by '. $arr["graphql"]["user"]["edge_followed_by"]["count"].$a;
  //echo 'Following '.   $arr["graphql"]["user"]["edge_follow"]["count"].$a;
} 

//If type=T, saves only the TAG.
if ($t=='t')
{
  $userinfo =  'Tag #'. $us.' '; 
}
$ll($filelog, 'Userinfo found: :: '.$userinfo); 


//SAVE the exact NODE (the number parameter) depending of the type
if ($t=='u') $nod = $arr["graphql"]["user"]["edge_owner_to_timeline_media"]["edges"][$num]["node"]; 
if ($t=='t') $nod = $arr["graphql"]["hashtag"]["edge_hashtag_to_top_posts"]["edges"][$num]["node"];

//Save the Description of the post.
$text =  substr( str_replace( chr(13), ' ', $userinfo.' '.$nod["edge_media_to_caption"]["edges"]["0"]["node"]["text"])     ,0,16*8-1) ;  


//Save the text content in a TXT file, to be used later by the IMAGE generator. 
$file = 'txt'.$ses.'.txt';
$fp = fopen( 'img/'.$file, 'w');
if (!$fp) echo "Error! Couldn't open the TXT file ".$file;
$text = preg_replace('/[^a-zA-Z0-9_ -]/s', '', $text) ;
$ll($filelog, 'Text of the image: ' .$text );
fwrite($fp,  $text ); 
fclose($fp);



//Captures the URL of the IMAGE to be converted
$cmd = 'bash /home/pi/www/instagr8/server/instaconv.sc '.$ses.' "'.$nod["thumbnail_src"].'" '.$ver;
$oo = shell_exec($cmd); 

$ll($filelog, 'Call shell conversion: '. $cmd);
$ll($filelog, 'OUTPUT of instaconv.sc:' );
$ll($filelog, $oo);

echo 'INSTA-OK';
?>
