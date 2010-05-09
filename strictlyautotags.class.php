<?php

/**
 * Plugin Name: Strictly Auto Tags
 * Version: Version 1.3
 * Plugin URI: http://www.strictly-software.com/plugins/strictly-auto-tags/
 * Description: This plugin automatically detects tags to place against posts using existing tags as well as a simple formula that detects common tag formats such as Acronyms, names and countries. Whereas other smart tag plugins only detect a single occurance of a tag within a post this plugin will search for the most used tags within the content so that only the most relevant tags get added.
 * Author: Rob Reid
 * Author URI: http://www.strictly-software.com 
 * =======================================================================
 */

define('DEBUG',true);

require_once(dirname(__FILE__) . "/strictlyautotagfuncs.php");

class StrictlyAutoTags{

   /**
	* look for new tags by searching for Acronyms and names 
	*
	* @access protected
	* @var bool
	*/
	protected $autodiscover; 

   /**
	* treat tags found in the post title as important and automatically add them to the post
	*
	* @access protected
	* @var bool
	*/
	protected $ranktitle; 

   /**
	* The maxiumum number of tags to add to a post
	*
	* @access protected
	* @var integer
	*/
	protected $maxtags; 


	 /**
	* The list of noise words to use=
	*
	* @access protected
	* @var string
	*/
	protected $noisewords = "about|after|a|all|also|an|and|another|any|are|as|at|be|because|been|before|being|between|both|but|by|came|can|come|could|did|do|each|even|for|from|further|furthermore|get|got|had|has|have|he|her|here|hi|him|himself|how|however|i|if|in|indeed|into|is|it|its|just|like|made|many|may|me|might|more|moreover|most|much|must|my|never|not|now|of|on|only|or|other|our|out|over|put|said|same|see|she|should|since|some|still|such|take|than|that|the|their|them|then|there|therefore|these|they|this|those|through|thus|to|too|under|up|very|was|way|we|well|were|what|when|where|which|while|who|will|why|with|would|you|your"; 


	public function __construct(){

		// set up values for config options e.g autodiscover, ranktitle, maxtags
		$this->GetOptions();

		// load any language specific text
		load_textdomain('strictlyautotags', dirname(__FILE__).'/language/'.get_locale().'.mo');

		// add options to admin menu
		add_action('admin_menu', array(&$this, 'RegisterAdminPage'));
		
		// set a function to run whenever posts are saved that will call our AutoTag function
		add_actions( array('save_post', 'publish_post', 'post_syndicated_item'), array(&$this, 'SaveAutoTags') );

	}

	/**
	 * Check post content for auto tags
	 *
	 * @param integer $post_id
	 * @param array $post_data
	 * @return boolean
	 */
	public function SaveAutoTags( $post_id = null, $post_data = null ) {
		$object = get_post($post_id);
		if ( $object == false || $object == null ) {
			return false;
		}
		
		$posttags = $this->AutoTag( $object );

		// add tags to post
		// Append tags if tags to add
		if ( count($posttags) > 0) {
			
			// Add tags to posts
			wp_set_object_terms( $object->ID, $posttags, 'post_tag', true );
			
			// Clean cache
			if ( 'page' == $object->post_type ) {
				clean_page_cache($object->ID);
			} else {
				clean_post_cache($object->ID);
			}			
		}

		return true;
	}
	
	/**
	 * Format content to make searching for new tags easier
	 *
	 * @param string $content
	 * @return string
	 */
	protected function FormatContent($content=""){
		
		if(!empty($content)){

			// if we are auto discovering tags then we need to reformat words next to full stops so that we don't get false positives
			if($this->autodiscover){
				// ensure capitals next to full stops are decapitalised but only if the word is single e.g
				// change . The world to . the but not . United States
				$content = preg_replace("/(\.[”’\"]?\s*[A-Z][a-z]+\s[a-z])/e","strtolower('$1')",$content);
			}

			// remove plurals
			$content = preg_replace("/(\w)([‘'’]s )/i","$1 ",$content);

			// now remove anything not a letter or number
			$content = preg_replace("/[^\w\d\s]/"," ",$content);
			
			// remove excess space
			$content = preg_replace("/\s{2,}/"," ",$content);			

		}

		return $content;

	}

	/**
	 * Checks the title string to see if autodiscovery of new terms is possible
	 * A title that is all capitalised cannot be searched for Acronyms or new sentences
	 *
	 * @param string $title
	 * @return boolean
	 */
	protected function TrustTitle($title){

		// do we have any lowercase letters in the string?
		$l = strlen(preg_replace("/[^a-z]/","",$title));

		// if all the words are uppercase then we cannot distinguish between Acronyms and proper cased strings
		if($l==0){
			return false;
		}

		// strip everything not space or uppercase/lowercase
		$title = preg_replace("/[^A-Za-z\s]/","",$title);

		// count words
		$c = str_word_count($title);

		// if all words have had first letters capitalised then we cannot trust string either
		$cc = preg_match_all("/\b[A-Z][A-Za-z]*\b/",$title,$matches);

		// if the number of words equals the number that are capitalised then we cannot trust the title
		if($cc == $c){
			// cannot trust the title
			return false;
		}
		
		// further tests would be nice, maybe check for capitalised noise words?
		return true;
		
	}

	/**
	 * Checks a word to see if its a known noise word
	 * 
	 * @param string $word
	 * @return boolean
	 */
	protected function IsNoiseWord($word){

		// create noise word regex
		$regex = "/^(?:" . $this->noisewords . ")$/i";
		
		$count = preg_match($regex,$word,$match);

		if(count($match)>0){
			return true;
		}else{			
			return false;
		}
	}

	/**
	 * Searches the passed in content looking for Acronyms to add to the search tags array
	 * 
	 * @param string $content
	 * @param array $searchtags
	 */
	protected function MatchAcronyms($content,&$searchtags){
		
		// easiest way to look for keywords without some sort of list is to look for Acronyms like CIA, AIG, JAVA etc.
		// so use a regex to match all words that are pure capitals 2 chars or more to skip over I A etc
		preg_match_all("/\b([A-Z]{2,})\b/",$content,$matches,PREG_SET_ORDER);
	
		if($matches){
		
			foreach($matches as $match){
				
				$pat = $match[1];

				// ignore noise words who someone has capitalised!
				if(!$this->IsNoiseWord($pat)){
					// add in the format key=value to make removing items easy and quick plus we don't waste overhead running
					// array_unique to remove duplicates!					
					$searchtags[$pat] = trim($pat);
				}
			}
		}
	}

	/**
	 * Searches the passed in content looking for Countries to add to the search tags array
	 * 
	 * @param string $content
	 * @param array $searchtags
	 */
	protected function MatchCountries($content,&$searchtags){
		preg_match_all("/\s(Afghanistan|Albania|Algeria|American\sSamoa|Andorra|Angola|Anguilla|Antarctica|Antigua\sand\sBarbuda|Arctic\sOcean|Argentina|Armenia|Aruba|Ashmore\sand\sCartier\sIslands|Australia|Austria|Azerbaijan|Bahrain|Baker\sIsland|Bangladesh|Barbados|Bassas\sda\sIndia|Belarus|Belgium|Belize|Benin|Bermuda|Bhutan|Bolivia|Bosnia\sand\sHerzegovina|Botswana|Bouvet\sIsland|Brazil|British\sVirgin\sIslands|Brunei|Bulgaria|Burkina\sFaso|Burma|Burundi|Cambodia|Cameroon|Canada|Cape\sVerde|Cayman\sIslands|Central\sAfrican\sRepublic|Chad|Chile|China|Christmas\sIsland|Clipperton\sIsland|Cocos\s(Keeling)\sIslands|Colombia|Comoros|Congo|Cook\sIslands|Coral\sSea\sIslands|Costa\sRica|Croatia|Cuba|Cyprus|Czech\sRepublic|Denmark|Djibouti|Dominica|Dominican\sRepublic|Ecuador|Eire|Egypt|El\sSalvador|Equatorial\sGuinea|England|Eritrea|Estonia|Ethiopia|Europa\sIsland|Falkland\sIslands\s|Islas\sMalvinas|Faroe\sIslands|Fiji|Finland|France|French\sGuiana|French\sPolynesia|French\sSouthern\sand\sAntarctic\sLands|Gabon|Gaza\sStrip|Georgia|Germany|Ghana|Gibraltar|Glorioso\sIslands|Greece|Greenland|Grenada|Guadeloupe|Guam|Guatemala|Guernsey|Guinea|Guinea-Bissau|Guyana|Haiti|Heard\sIsland\sand\sMcDonald\sIslands|Holy\sSee\s(Vatican\sCity)|Honduras|Hong\sKong|Howland\sIsland|Hungary|Iceland|India|Indonesia|Iran|Iraq|Ireland|Israel|Italy|Ivory\sCoast|Jamaica|Jan\sMayen|Japan|Jarvis\sIsland|Jersey|Johnston\sAtoll|Jordan|Juan\sde\sNova\sIsland|Kazakstan|Kenya|Kingman\sReef|Kiribati|Korea|Korea|Kuwait|Kyrgyzstan|Laos|Latvia|Lebanon|Lesotho|Liberia|Libya|Liechtenstein|Lithuania|Luxembourg|Macau|Macedonia\sThe\sFormer\sYugoslav\sRepublic\sof|Madagascar|Malawi|Malaysia|Maldives|Mali|Malta|Man\sIsle\sof|Marshall\sIslands|Martinique|Mauritania|Mauritius|Mayotte|Mexico|Micronesia\sFederated\sStates\sof|Midway\sIslands|Moldova|Monaco|Mongolia|Montenegro|Montserrat|Morocco|Mozambique|Namibia|Nauru|Navassa\sIsland|Nepal|Netherlands|Netherlands\sAntilles|New\sCaledonia|New\sZealand|Nicaragua|Nigeria|Niue|Norfolk\sIsland|Northern\sIreland|Northern\sMariana\sIslands|Norway|Oman|Pakistan|Palau|Palmyra\sAtoll|Panama|Papua\sNew\sGuinea|Paracel\sIslands|Paraguay|Peru|Philippines|Pitcairn\sIslands|Poland|Portugal|Puerto\sRico|Qatar|Reunion|Romania|Russia|Rwanda|Saint\sHelena|Saint\sKitts\sand\sNevis|Saint\sLucia|Saint\sPierre\sand\sMiquelon|Saint\sVincent\sand\sthe\sGrenadines|San\sMarino|Sao\sTome\sand\sPrincipe|Saudi\sArabia|Scotland|Senegal|Serbia|Seychelles|Sierra\sLeone|Singapore|Slovakia|Slovenia|Solomon\sIslands|Somalia|South\sAfrica|South\sGeorgia\sand\sthe\sSouth\sSandwich\sIslands|Spain|Spratly\sIslands|Sri\sLanka|Sudan|Suriname|Svalbard|Swaziland|Sweden|Switzerland|Syria|Taiwan|Tajikistan|Tanzania|Thailand|The\sBahamas|The\sGambia|Togo|Tokelau|Tonga|Trinidad\sand\sTobago|Tromelin\sIsland|Tunisia|Turkey|Turkmenistan|Turks\sand\sCaicos\sIslands|Tuvalu|Uganda|Ukraine|United\sArab\sEmirates|UAE|United\sKingdom|UK|United\sStates\sof\sAmerica|USA|Uruguay|Uzbekistan|Vanuatu|Venezuela|Vietnam|Virgin\sIslands|Wake\sIsland|Wales|Wallis\sand\sFutuna|West\sBank|Western\sSahara|Western\sSamoa|Yemen|Zaire|Zambia|Zimbabwe|Europe|Western\sEurope|North\sAmerica|South\sAmerica|Asia|South\sEast\sAsia|Central\sAsia|The\sCaucasus|Middle\sEast|Far\sEast|Scandinavia|Africa|North\sAfrica|North\sPole|South\sPole|Central\sAmerica|Caribbean)\s/i",$content,$matches, PREG_SET_ORDER);


		if($matches){
		
			foreach($matches as $match){
				
				$pat = $match[1];

				$searchtags[$pat] = trim($pat);
			}
		}
	}

	/**
	 * Searches the passed in content looking for Countries to add to the search tags array
	 * 
	 * @param string $content
	 * @param array $searchtags
	 */
	protected function MatchNames($content,&$searchtags){

		// create noise word regex
		$regex = "/\b(" . $this->noisewords . ")\b/i";

		// remove noise words from content first
		$content = preg_replace($regex," ",$content);

		// look for names of people or important strings of 2-4 words that start with capitals e.g Federal Reserve Bank or Barack Obama
		preg_match_all("/(\s[A-Z][^\s]{2,}\s[A-Z][^\s]+\s(?:[A-Z][^\s]+\s)?(?:[A-Z][^\s]+\s)?)/",$content,$matches,PREG_SET_ORDER);

		// found some results
		if($matches){
		
			foreach($matches as $match){
				
				$pat = $match[1];

				$searchtags[$pat] = trim($pat);
			}
		}
			
	}

	/**
	 * formats strings so they can be used in regular expressions easily by escaping special chars used in pattern matching
	 *
	 * @param string $input
	 * @return string
	 */
	function FormatRegEx($input){

		$input = preg_replace("@([$^|()*+?.\[\]{}])@","\\\\$1",$input);

		return $input;
	}

	/**
	 * Parse post content to discover new tags and then rank matching tags so that only the most appropriate are added to a post
	 *
	 * @param object $object
	 * @return array
	 */
	function AutoTag($object){

		// skip posts with tags already added
		if ( get_the_tags($object->ID) != false) {
			return false;
		}

		// tags to add to post
		$addtags = array();

		// stack used for working out which tags to add
		$tagstack = array();

		// potential tags to add
		$searchtags = array();

		// ensure all html entities have been decoded
		$article	= html_entity_decode(strip_tags($object->post_content));
		$excerpt	= html_entity_decode($object->post_excerpt);
		$title		= html_entity_decode($object->post_title);

		// no need to trim as empty checks for space
		if(empty($article) && empty($excerpt) && empty($title)){		
			return $addtags;	
		}


		// if we are looking for new tags then can we trust the title? Some authors like to put each word in capitals
		// or capitalise the first letter of each word which would mean our autodisovery techniques would throw false positives
		// so lets check our title first
		
		if($autodiscover && $this->TrustTitle($title)){

			// add space to the end and beginning to make matching easier
			$content			= " " . $article . " " . $excerpt . " " . $title . " ";
			$discovercontent	= $content;

		}else{
			// cannot trust title so only look in the article and excerpt for new tags
			$discovercontent	= " " . $article . " " . $excerpt. " ";
			$content			= $discovercontent . $title . " ";

		}

		// set working variable which will be decreased when tags have been found
		$maxtags = $this->maxtags;


		// reformat content to remove plurals and punctuation
		$content			= $this->FormatContent($content);
		$discovercontent	= $this->FormatContent($discovercontent);

		// now if we are looking for new tags
		if($this->autodiscover){
			
			// look for Acronyms in content
			// the searchtag array is passed by reference to prevent copies of arrays and merges later on
			$this->MatchAcronyms($discovercontent,$searchtags);		
			
			// look for countries as these are used as tags quite a lot
			$this->MatchCountries($discovercontent,$searchtags);

			// look for names and important sentences 2-4 words all capitalised
			$this->MatchNames($discovercontent,$searchtags);
		}

		
		// if we have a searchtag array and are looking in the title (if ranktitle is set) then we need to join any terms in the DB
		// with our existing tags for a pattern match. Therefore to prevent an array merge THEN an implode 
		// I return the DB data as a string first to cut down on memory swaps during array merges

		
		// get existing tags from the DB as we can use these as well as any new ones we just discovered
		global $wpdb;

		if($this->ranktitle){

			$dbtermresults = $wpdb->get_col("
					SELECT	GROUP_CONCAT( DISTINCT name ORDER By Name SEPARATOR '|') as terms
					FROM	$wpdb->terms AS t
					JOIN	$wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
					WHERE	tt.taxonomy = 'post_tag'
				");

			$vals = trim($dbtermresults[0]);
					
			// cleanup ASAP
			unset($dbtermresults);

			// add the search terms we found
			if(count($searchtags) > 0){
				// search tags shouldn't contain special regex chars as we replaced all non A-Za-z1-9 earlier
				$vals = str_replace(" ","\s",$this->FormatRegEx($vals) . "|" . implode($searchtags,"|"));
			}

			// add space to either side of title to make matching easier
			$title = " " . $title . " ";
			
			// any keywords found in the title matching our combined list of tags automatically get added
			$pattern = "/\b(" . $vals . ")\b/i";

			if(preg_match_all($pattern,$title,$matches,PREG_SET_ORDER)){
			

				if($matches){
					
					$count	= 0;
					$rem	= ""; // holds items to remove 

					foreach($matches as $match){
						
						$pat = trim($match[1]);

						if(!empty($pat)){
							
							// don't add any more than the user specified

							if($maxtags != -1 || $count < $maxtags){
								
								// add this tag to our return list
								$addtags[] = $pat;

								// also add tag to a remove list
								$rem .= $pat . "|";

								$count++;

								// reached limit so exit
								if($maxtags != -1 && $count == $maxtags) break;

							}
						}
					}

					// remove all tags we found in the title from our combo list as we don't want to be searching for them in the content
					// as well. Use a simple regex to update our combo string. Saves on a array_unique call.
					if(!empty($rem)){

						$regex = "(" . str_replace(" ","\s",substr($rem,0,-1)) . ")";

						$vals = preg_replace($regex,"",$vals);
					}

					// explode our combo string into an array we can use for searching within our content
					$terms = explode("|",str_replace("\s"," ",$vals));

					// if we have a limit on the number of tags then ensure our max counter takes off the number of tags found in the title
					if($maxtags != -1 && $count > 0){

						$maxtags -= $count;

						// have we reached our limit
						if($maxtags == 0){

							// yes we have
							// no need to check the content as we found ALL our tags in the title
							return $addtags;
						}
					}
				}
			}else{				
				// no tags found in the title so reformat our regex back into an array to search the main content with
				$terms = explode("|",str_replace("\s"," ",$vals));
			}

		}else{
		
			// just get all the terms from the DB in array format
		
			$dbterms = $wpdb->get_col("
					SELECT	DISTINCT name
					FROM	{$wpdb->terms} AS t
					JOIN	{$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
					WHERE	tt.taxonomy = 'post_tag'
				");
			
			// if we have got some names and Acronyms then add them to our DB terms
			// add the search terms we found
			$c = count($searchtags);
			$d = count($dbterms);

			// if we have got some names and Acronyms then add them to our DB terms
			// add the search terms we found
			if($c > 0){
				
				if( $d > 0){

					$terms = array_merge($dbterms,$searchtags);
					
					// remove duplicates
					$terms = array_unique($terms);
					
				}else{
					$terms = $searchtags;					
				}
			
				unset($searchtags,$dbterms);
			
			}elseif($d > 0){

				// set terms to db results
				$terms = $dbterms;

				unset($searchtags,$dbterms);
			}else{
				// cleanup before exiting
				unset($searchtags,$dbterms);

				// return empty array
				return $addtags;
			}
		}

		
		// the $terms array we now have will just contain words NOT already added to the $addtags array and will contain all tags from
		// the DB as well as any we found earlier.

		if(count($terms)>0){

			// now loop through our content looking for the highest number of matching tags as we don't want to add a tag
			// just because it appears once as that single word would possibly be irrelevant to the posts context.
			foreach($terms as $term){

				// safety check
				if(strlen($terms) > 1){

					// for an accurate search use preg_match_all with word boundaries
					// as substr_count doesn't always return the correct number from tests I did
					
					$regex = "/\b" . $this->FormatRegEx( $term ) . "\b/";

					//echo "term = " . $term . " - regex = " . $regex . "<br>";

					$i = preg_match_all($regex,$content,$matches);

					// if found then store it with the no of occurances
					if($i > 0){

						// add term and hit count to our array
						$tagstack[] = array("term"=>$term,"count"=>$i);

					}
				}
			}

			// sort array in reverse order so we have our highest hits first
			rsort($tagstack);

			// take the top X items
			if($maxtags != -1 && count($tagstack) > $maxtags){

				// sort our results in decending order using our hitcount
				uasort($tagstack, array($this,'HitCount'));
				
				// return only the results we need
				$tagstack = array_slice($tagstack, 0, $maxtags);
			}

			// add our results to the array we return which will be added to the post
			foreach($tagstack as $item=>$tag){
				$addtags[] = $tag['term'];
			}
		}
		
		// return array of post tags
		return $addtags;

	}

	/**
	 * used when sorting tag hit count to compare two array items hitcount
	 *
	 * @param array $a
	 * @param array $b
	 * @return integer
	 */
	function HitCount($a, $b) {
		return $b['count'] - $a['count'];
	}

	/**
	 * Register AdminOptions with Wordpress
	 *
	 */
	function RegisterAdminPage() {
		add_options_page('Strictly Auto Tags', 'Strictly Auto Tags', 10, basename(__FILE__), array(&$this,'AdminOptions'));	
	}

	/**
	 * get saved options otherwise use defaults
	 *	 
	 * @return array
	 */
	function GetOptions(){

		// get saved options from wordpress DB
		$options = get_option('strictlyautotags');

		// if there are no saved options then use defaults
		if ( !is_array($options) )
		{
			// This array sets the default options for the plugin when it is first activated.
			$options = array('autodiscover'=>true, 'ranktitle'=>true, 'maxtags'=>4);
		}

		// set internal members
		$this->autodiscover = $options['autodiscover'];

		$this->ranktitle	= $options['ranktitle'];

		$this->maxtags		= $options['maxtags'];

		return $options;
	}

	/**
	 * save new options to the DB and reset internal members
	 *
	 * @param object $object
	 */
	function SaveOptions($options){

		update_option('strictlyautotags', $options);

		// set internal members
		$this->autodiscover = $options['autodiscover'];

		$this->ranktitle	= $options['ranktitle'];

		$this->maxtags		= $options['maxtags'];
	}

	/**
	 * Admin page for backend management of plugin
	 *
	 */
	function AdminOptions(){

		// get saved options
		$options = $this->GetOptions();

		// if form has been submitted then save new values
		if ( $_POST['strictlyautotags-submit'] )
		{
			$options['autodiscover']= strip_tags(stripslashes($_POST['strictlyautotags-autodiscover']));
			$options['ranktitle']	= strip_tags(stripslashes($_POST['strictlyautotags-ranktitle']));

			// only set if its numeric
			$maxtags = strip_tags(stripslashes($_POST['strictlyautotags-maxtags']));

			if(is_numeric($maxtags) && $maxtags <= 20){
				$options['maxtags']		= $maxtags;
			}

			// save new values to the DB
			update_option('strictlyautotags', $options);
		}

		echo	'<style type="text/css">
				.tagopt{
					margin-top:7px;
				}
				.donate{
					margin-top:30px;
				}
				.notes{
					display:block;					
				}	
				#StrictlyAutoTagsAdmin ul{
					list-style-type:circle !important;
					padding-left:18px;
				}
				#StrictlyAutoTagsAdmin label{
					font-weight:bold;
				}
				</style>';

		echo	'<div class="wrap" id="StrictlyAutoTagsAdmin">';

		echo	'<h3>'.__('Strictly AutoTags', 'strictlyautotags').'</h3>';

		echo	'<p>'.__('Strictly AutoTags is designed to do one thing and one thing only - automatically add relevant tags to your posts.', 'strictlyautotags').'</p>
				<ul><li>'.__('Enable Auto Discovery to find new tags.', 'strictlyautotags').'</li>
				<li>'.__('Suitable words such as Acronyms, Names, Countries and other important keywords will then be identified within the post.', 'strictlyautotags').'</li>
				<li>'.__('Existing tags will also be used to find relevant tags within the post.', 'strictlyautotags').'</li>
				<li>'.__('Set the maximum number of tags to append to a post to a suitable amount. Setting the number too high could mean that tags that only appear once might be added.', 'strictlyautotags').'</li>
				<li>'.__('Treat tags found in the post title as especially important by enabling the Rank Title option.', 'strictlyautotags').'</li>
				<li>'.__('Only the most frequently occurring tags will be added against the post.', 'strictlyautotags').'</li></ul>';

		echo	'<h3>'.__('AutoTag Options', 'strictlyautotags').'</h3>';

		echo	'<div><form method="post">';
	
		echo	'<div class="tagopt">
				<input type="checkbox" name="strictlyautotags-autodiscover" id="strictlyautotags-autodiscover" value="true" ' . (($options['autodiscover']) ? 'checked="checked"' : '') . '/>
				<label for="strictlyautotags-autodiscover">'.__('Auto Discovery','strictlyautotags').'</label>
				<span class="notes">'.__('Automatically discover new tags on each post', 'strictlyautotags').'</span>
				</div>';

		echo	'<div class="tagopt">
				<input type="checkbox" name="strictlyautotags-ranktitle" id="strictlyautotags-ranktitle" value="true" ' . (($options['ranktitle']) ? 'checked="checked"' : '') . '/>
				<label for="strictlyautotags-ranktitle">'.__('Rank Title','strictlyautotags').'</label>
				<span class="notes">'.__('Rank tags found in post titles over those in content', 'strictlyautotags').'</span>
				</div>';

		echo	'<div class="tagopt">
				<input type="text" name="strictlyautotags-maxtags" id="strictlyautotags-maxtags" value="' . $options['maxtags'] . '" />
				<label for="strictlyautotags-maxtags">'.__('Max Tags','strictlyautotags').'</label>
				<span class="notes">'.__('Maximum no of tags to save (20 max)', 'strictlyautotags').'</span>
				</div>';

	
		echo	'<input type="hidden" id="strictlyautotags-submit" name="strictlyautotags-submit" value="1" />';

		echo	'<p class="submit"><input value="'.__('Save Options', 'strictlyautotags').'" type="submit"></form></p></div>';

		echo	'<div class="donate"><h3>'.__('Donate to Stictly Software', 'strictlyautotags').'</h3>';

		echo	'<p>'.__('Your help ensures that my work continues to be free and any amount is appreciated.', 'strictlyautotags').'</p>';
		
		echo	'<div style="text-align:center;"><br />
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><br />
				<input type="hidden" name="cmd" value="_s-xclick"><br />
				<input type="hidden" name="hosted_button_id" value="6427652"><br />
				<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
				<br /></form></div></div>';

	}
}

// create auto tag object
$strictlyautotags = new StrictlyAutoTags();

?>
