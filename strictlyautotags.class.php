<?php

/**
 * Plugin Name: Strictly Auto Tags
 * Version: Version 1.4
 * Plugin URI: http://www.strictly-software.com/plugins/strictly-auto-tags/
 * Description: This plugin automatically detects tags to place against posts using existing tags as well as a simple formula that detects common tag formats such as Acronyms, names and countries. Whereas other smart tag plugins only detect a single occurance of a tag within a post this plugin will search for the most used tags within the content so that only the most relevant tags get added.
 * Author: Rob Reid
 * Author URI: http://www.strictly-software.com 
 * =======================================================================
 */

/*
error_reporting(E_ALL);
ini_set('display_errors', 1);
*/

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
	* The percentage of content that is allowed to be capitalised when auto discovering new tags
	*
	* @access protected
	* @var integer
	*/
	protected $ignorepercentage;

	/**
	* The list of noise words to use
	*
	* @access protected
	* @var string
	*/
	protected $noisewords;


	/**
	* This setting determines how nested tags are handled e.g New York, New York City, New York City Fire Department all contain "New York"
	* AUTOTAG_BOTH = all 3 terms will be tagged 
	* AUTOTAG_SHORT= the shortest version "New York" will be tagged and the others dicarded
	* AUTOTAG_LONG = the longest version "New York City Fire Department" will be tagged and the others dicarded
	*/
	protected $nestedtags;


	/**
	* The default list of noise words to use
	*
	* @access protected
	* @var string
	*/
	protected $defaultnoisewords = "about|after|a|all|also|an|and|another|any|are|as|at|be|because|been|before|being|between|both|but|by|came|can|come|could|did|do|each|even|for|from|further|furthermore|get|got|had|has|have|he|her|here|hi|him|himself|how|however|i|if|in|indeed|into|is|it|its|just|like|made|many|may|me|might|more|moreover|most|much|must|my|never|not|now|of|on|only|or|other|our|out|over|put|said|same|see|she|should|since|some|still|such|take|than|that|the|their|them|then|there|therefore|these|they|this|those|through|thus|to|too|under|up|very|was|way|we|well|were|what|when|where|which|while|who|will|why|with|would|you|your"; 

	/**
	* Holds a regular expression for checking whether a word is a noise word
	*
	* @access protected
	* @var string
	*/
	protected $isnoisewordregex;

	/**
	* Holds a regular expression for removing noise words from a string of words
	*
	* @access protected
	* @var string
	*/
	protected $removenoisewordsregex;


	public function __construct(){

		// set up values for config options e.g autodiscover, ranktitle, maxtags
		$this->GetOptions();

		// create some regular expressions required by the parser
		
		// create regex to identify a noise word
		$this->isnoisewordregex		= "/^(?:" . $this->noisewords . ")$/i";

		// create regex to replace all noise words in a string
		$this->removenoisewordsregex= "/\b(" . $this->noisewords . ")\b/i";

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
				// change ". The world" to ". the" but not ". United States"
				$content = preg_replace("/(\.[”’\"]?\s*[A-Z][a-z]+\s[a-z])/ue","strtolower('$1')",$content);
			}

			// remove plurals
			$content = preg_replace("/(\w)([‘'’]s )/i","$1 ",$content);

			// now remove anything not a letter or number
			$content = preg_replace("/[^\w\d\s\.,]/"," ",$content);
			
			// remove excess space
			$content = preg_replace("/\s{2,}/"," ",$content);			

		}

		return $content;

	}
	
	/**
	 * Checks a word to see if its a known noise word
	 * 
	 * @param string $word
	 * @return boolean
	 */
	protected function IsNoiseWord($word){
		
		$count = preg_match($this->isnoisewordregex,$word,$match);

		if(count($match)>0){
			return true;
		}else{			
			return false;
		}
	}

	/*
	 * removes noise words from a given string
	 *
	 * @param string
	 * @return string
	 */
	function RemoveNoiseWords($content){		

		$content = preg_replace($this->removenoisewordsregex," ",$content);

		return $content;
	}

	/*
	 * counts the number of words that capitalised in a string
	 *
	 * @param string
	 * @return integer
	 */
	function CountCapitals($words){
		
		$no_caps =	preg_match_all("/\b[A-Z][A-Za-z]*\b/",$words,$matches);			

		return $no_caps;
	}
	
	/*
	 * strips all non words from a string
	 *
	 * @param string
	 * @return string
	 */
	function StripNonWords($words){

		// strip everything not space or uppercase/lowercase
		$words = preg_replace("/[^A-Za-z\s]/u","",$words);
	
		return $words;
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
		preg_match_all("/\b([A-Z]{2,})\b/u",$content,$matches,PREG_SET_ORDER);
	
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

		unset($match,$matches);

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

		unset($match,$matches);

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

		// look for names of people or important strings of 2+ words that start with capitals e.g Federal Reserve Bank or Barack Hussein Obama
		// this is not perfect and will not handle Irish type surnames O'Hara etc
		preg_match_all("/((\b[A-Z][^A-Z\s\.,;:]+)(\s+[A-Z][^A-Z\s\.,;:]+)+\b)/u",$content,$matches,PREG_SET_ORDER);

		// found some results
		if($matches){
		
			foreach($matches as $match){
				
				$pat = $match[1];

				$searchtags[$pat] = trim($pat);
			}
		}
		
		unset($match,$matches);
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
	 * check the content to see if the amount of content that is parsable is above the allowed threshold
	 *
	 * @param string
	 * @return boolean
	 */
	function ValidContent($content){

		// strip everything not space or uppercase/lowercase letters
		$content	= $this->StripNonWords($content);

		// count the total number of words
		$word_count = str_word_count($content);

		// no words? nothing to analyse
		if($word_count == 0){
			return false;
		}

		// count the number of capitalised words
		$capital_count = $this->CountCapitals($content);

		if($capital_count > 0){
			// check percentage - if its set to 0 then we can only skip the content if its all capitals
			if($this->ignorepercentage > 0){
				$per = round(($capital_count / $word_count) * 100);

				if($per > $this->ignorepercentage){
					return false;	
				}
			}else{
				if($word_count == $capital_count){
					return false;
				}
			}
		}

		return true;
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

		// if we are looking for new tags then check the major sections to see what percentage of words are capitalised
		// as that makes it hard to look for important names and strings
		if($this->autodiscover){
			
			$discovercontent = "";

			// ensure title is not full of capitals
			if($this->ValidContent($title)){
				$discovercontent .= " " . $title . " ";				
			}

			// ensure article is not full of capitals
			if($this->ValidContent($article)){
				$discovercontent .= " " . $article . " ";					
			}

			// ensure excerpt  is not full of capitals
			if($this->ValidContent($excerpt)){
				$discovercontent .= " " . $excerpt . " ";					
			}
			
		}else{			
			$discovercontent	= "";
		}

		// if we are doing a special parse of the title we don't need to add it to our content as well
		if($this->ranktitle){
			$content			= " " . $article . " " . $excerpt . " ";
		}else{
			$content			= " " . $article . " " . $excerpt . " " . $title . " ";
		}

		// set working variable which will be decreased when tags have been found
		$maxtags			= $this->maxtags;


		// reformat content to remove plurals and punctuation
		$content			= $this->FormatContent($content);
		$discovercontent	= $this->FormatContent($discovercontent);

		// now if we are looking for new tags and we actually have some valid content to check
		if($this->autodiscover && !empty($discovercontent)){
			
			// look for Acronyms in content
			// the searchtag array is passed by reference to prevent copies of arrays and merges later on
			$this->MatchAcronyms($discovercontent,$searchtags);		
			
			// look for countries as these are used as tags quite a lot
			$this->MatchCountries($discovercontent,$searchtags);

			// look for names and important sentences 2-4 words all capitalised
			$this->MatchNames($discovercontent,$searchtags);
		}
		
		
		// get existing tags from the DB as we can use these as well as any new ones we just discovered
		global $wpdb;

		// just get all the terms from the DB in array format
	
		$dbterms = $wpdb->get_col("
				SELECT	DISTINCT name
				FROM	{$wpdb->terms} AS t
				JOIN	{$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE	tt.taxonomy = 'post_tag'
			");
		
		// if we have got some names and Acronyms then add them to our DB terms
		// as well as the search terms we found
		$c = count($searchtags);
		$d = count($dbterms);
		
		if($c > 0 && $d > 0){

			// join the db terms to those we found earlier
			$terms = array_merge($dbterms,$searchtags);
		
			// remove duplicates which come from discovering new tags that already match existing stored tags
			$terms = array_unique($terms);
			
		}elseif($c > 0){

			// just set terms to those we found through autodiscovery
			$terms = $searchtags;

		}elseif($d > 0){

			// just set terms to db results
			$terms = $dbterms;
		}

		// clean up		
		unset($searchtags,$dbterms);
		
		// if we have no terms to search with then quit now
		if(!isset($terms) || !is_array($terms)){
			// return empty array
			return $addtags;
		}

		
		// do we rank terms in the title higher?
		if($this->ranktitle){

			// parse the title with our terms adding tags by reference into the tagstack
			// as we want to ensure tags in the title are always tagged we tweak the hitcount by adding 1000
			// in future expand this so we can add other content to search e.g anchors, headers each with their own ranking
			$this->SearchContent($title,$terms,$tagstack,1000);
		}

		// now parse the main piece of content
		$this->SearchContent($content,$terms,$tagstack,0);
		
		// cleanup
		unset($terms,$term);
	
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
		

		// we don't need to worry about dupes e.g tags added when the rank title check ran and then also added later
		// as Wordpress ensures duplicate taxonomies are not added to the DB
		
		// return array of post tags
		return $addtags;

	}

	/**
	 * parses content with a supplied array of terms looking for matches
	 *
	 * @param string content
	 * @param array $terms
	 * @param array $tagstack	
	 * @param integer $tweak	 
	 */
	function SearchContent($content,$terms,&$tagstack,$tweak){

		if(empty($content) || !is_array($terms) || !is_array($tagstack)){
			return;
		}

		// now loop through our content looking for the highest number of matching tags as we don't want to add a tag
		// just because it appears once as that single word would possibly be irrelevant to the posts context.
		foreach($terms as $term){

			// safety check in case some BS gets into the DB!
			if(strlen($term) > 1){

				// for an accurate search use preg_match_all with word boundaries
				// as substr_count doesn't always return the correct number from tests I did
				
				$regex = "/\b" . $this->FormatRegEx( $term ) . "\b/";

				$i = preg_match_all($regex,$content,$matches);

				// if found then store it with the no of occurances it appeared e.g its hit count
				if($i > 0){

					// if we are tweaking the hitcount e.g for ranking title tags higher
					if($tweak > 0){
						$i = $i + $tweak;
					}

					// do we add all tags whether or not they appear nested inside other matches
					if($this->nestedtags == AUTOTAG_BOTH){
	
						// add term and hit count to our array
						$tagstack[] = array("term"=>$term,"count"=>$i);
					
					// must be AUTOTAG_SHORT
					}else{

						$ignore = false;
						
						// loop through existing tags checking for nested matches e.g New York appears in New York City 						
						foreach($tagstack as $key=>$value){

							$oldterm = $value['term'];
							$oldcount= $value['count'];
			
							// check whether our new term is already in one of our old terms
							if(stripos($oldterm,$term)!==false){
								
								// we found our term inside a longer one and as we are keeping the shortest version we need to add
								// the other tags hit count before deletng it as if it was a ranked title we want this version to show
								$i = $i + (int)$oldcount;

								// remove our previously stored tag as we only want the smallest version						
								unset($tagstack[$key]);
							
							// check whether our old term is in our new one
							}elseif(stripos($term,$oldterm)!==false){
								
								// yes it is so keep our short version in the stack and ignore our new term								
								$ignore = true;
								break;
							}
						}
					
						// do we add our new term
						if(!$ignore){							
							// add term and hit count to our array
							$tagstack[] = array("term"=>$term,"count"=>$i);
						}
					}
				}
			}
		}

		// the $tagstack was passed by reference so no need to return it
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
			$options = array('autodiscover'=>true, 'ranktitle'=>true, 'maxtags'=>4, 'ignorepercentage'=>50, 'noisewords'=>$this->defaultnoisewords, 'nestedtags'=>AUTOTAG_BOTH);
		}else{

			// check defaults in case of new functionality added to plugin after installation
			if(IsNothing($options['nestedtags'])){
				$options['nestedtags'] = AUTOTAG_BOTH;
			}

			if(IsNothing($options['noisewords'])){
				$options['noisewords'] = $this->defaultnoisewords;
			}

			if(IsNothing($options['ignorepercentage'])){
				$options['ignorepercentage'] = 50;
			}
		}

		// set internal members		
		$this->SetValues($options);

		// return options
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
		$this->SetValues($options);
	}
	
	/**
	 * sets internal member properties with the values from the options array
	 *
	 * @param object $object
	 */
	function SetValues($options){
		
		$this->autodiscover		= $options['autodiscover'];

		$this->ranktitle		= $options['ranktitle'];

		$this->maxtags			= $options['maxtags'];

		$this->ignorepercentage	= $options['ignorepercentage'];

		$this->noisewords		= $options['noisewords'];

		$this->nestedtags		= $options['nestedtags'];

	}

	
	/**
	 * Admin page for backend management of plugin
	 *
	 */
	function AdminOptions(){

		// get saved options
		$options	 = $this->GetOptions();

		// message to show to admin if input is invalid
		$msg		= "";

		// if form has been submitted then save new values
		if ( $_POST['strictlyautotags-submit'] )
		{
			$options['autodiscover']= strip_tags(stripslashes($_POST['strictlyautotags-autodiscover']));
			$options['ranktitle']	= strip_tags(stripslashes($_POST['strictlyautotags-ranktitle']));			
			$options['nestedtags']	= strip_tags(stripslashes($_POST['strictlyautotags-nestedtags']));

			$ignorepercentage		= trim(strip_tags(stripslashes($_POST['strictlyautotags-ignorepercentage'])));			
			$noisewords				= trim(strip_tags(stripslashes($_POST['strictlyautotags-noisewords'])));			

			// check format is word|word|word
			if(empty($noisewords)){
				$noisewords = $this->defaultnoisewords;
			}else{
				$noisewords = strtolower($noisewords);

				if( preg_match("/^([a-z]+\|[a-z]*)+$/",$noisewords)){	
					$options['noisewords']	= $noisewords;
				}else{
					$msg .= __('The noise words you entered are in an invalid format.<br />','strictlyautotags');
				}
			}

			// only set if its numeric
			$maxtags = strip_tags(stripslashes($_POST['strictlyautotags-maxtags']));

			if(is_numeric($maxtags) && $maxtags <= 20){
				$options['maxtags']		= $maxtags;
			}else{
				$msg .= __('The value you entered for Max Tags was invalid.<br />','strictlyautotags');
			}

			if(is_numeric($ignorepercentage) && ($ignorepercentage >= 0 || $ignorepercentage <= 100)){
				$options['ignorepercentage']		= $ignorepercentage;
			}else{
				$msg .= __('The value your entered for the Ignore Capitals Percentage was invalid.<br />','strictlyautotags');
			}
			
			if(!empty($msg)){
				$msg = substr($msg,0,strlen($msg)-6);
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
				p.error{
					font-weight:bold;
					color:red;
				}
				#StrictlyAutoTagsAdmin ul{
					list-style-type:circle !important;
					padding-left:18px;
				}
				#StrictlyAutoTagsAdmin label{
					font-weight:bold;
				}
				#strictlyautotags-noisewords{
					width:600px;
					height:250px;
				}
				#lblnoisewords{
					vertical-align:top;
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
				<li>'.__('Handle badly formatted content by setting the Ignore Capitals Percentage to an appropiate amount.', 'strictlyautotags').'</li>
				<li>'.__('Only the most frequently occurring tags will be added against the post.', 'strictlyautotags').'</li></ul>';

		echo	'<h3>'.__('AutoTag Options', 'strictlyautotags').'</h3>';

		if(!empty($msg)){
			echo '<p class="error">' . $msg . '</p>';
		}

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

		echo	'<div class="tagopt">
				<input type="text" name="strictlyautotags-ignorepercentage" id="strictlyautotags-ignorepercentage" value="' . $options['ignorepercentage'] . '" />
				<label for="strictlyautotags-ignorepercentage">'.__('Ignore Capitals Percentage','strictlyautotags').'</label>
				<span class="notes">'.__('Badly formatted content that contains too many capitalised words can cause false positives when discovering new tags. This option allows you to tell the system to ignore auto discovery if the percentage of capitalised words is greater than the specified threshold)', 'strictlyautotags').'</span>
				</div>';

		echo	'<div class="tagopt">
				<input type="radio" name="strictlyautotags-nestedtags" id="strictlyautotags-nestedtagsboth" value="' . AUTOTAG_BOTH . '" ' . ((IsNothing($options['nestedtags']) || $options['nestedtags']==AUTOTAG_BOTH  ) ? 'checked="checked"' : '') . '/><label for="strictlyautotags-nestedtagsboth">'.__('Tag All Versions','strictlyautotags').'</label>
				<input type="radio" name="strictlyautotags-nestedtags" id="strictlyautotags-nestedtagsshort" value="' . AUTOTAG_SHORT . '" ' . (($options['nestedtags'] ) ? 'checked="checked"' : '') . '/><label for="strictlyautotags-nestedtagsshort">'.__('Tag Shortest Version','strictlyautotags').'</label>				
				<span class="notes">'.__('This option determines how nested tags are handled e.g <strong><em>New York, New York City, New York City Fire Department</em></strong> all contain the words <strong><em>New York</em></strong>. Setting this option to <strong>Tag All</strong> will mean all 3 get tagged. Setting it to <strong>Tag shortest</strong> will mean the shortest match e.g <strong><em>New York</em></strong> gets tagged.', 'strictlyautotags').'</span>
				</div>';

		echo	'<div class="tagopt">
				<textarea name="strictlyautotags-noisewords" id="strictlyautotags-noisewords">' . $options['noisewords'] . '</textarea>
				<label id="lblnoisewords" for="strictlyautotags-noisewords">'.__('Noise Words','strictlyautotags').'</label>
				<span class="notes">'.__('Noise words or stop words, are commonly used English words like <strong><em>any, or, and</em></strong> that are stripped from the content before analysis as you wouldn\'t want these words being used as tags. Please ensure all words are separated by a pipe | character e.g <strong>a|and|at|as</strong>.)', 'strictlyautotags').'</span>
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
