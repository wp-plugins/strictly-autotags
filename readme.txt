=== Strictly Auto Tags ===
Contributors: Strictly Software
Donate link: http://www.strictly-software.com/donate
Plugin Home: http://www.strictly-software.com/plugins/strictly-auto-tags
Tags: tags, autotag, taxonomy, smarttag
Requires at least: 2.0.2                    
Tested up to: 3.8.1
Stable tag: 2.9.0

Strictly AutoTags is a plugin that automatically adds the most relevant tags to posts.


== Description ==
FREE VERSION - Buy version 2.9.1 at http://www.strictly-software.com/plugins/strictly-auto-tags

Strictly AutoTags is a plugin that scans an English language post for words that could be used as tags and then orders them so that the most relevant
words get added against the post. Just because a word appears in a post that is already in your list of tags does not mean that it should
automatically be added against the article. Therefore the plugin orders all matching tags in descending order and picks only those that occur the most.

As well as using existing tags to work out which words to tag posts with this plugin automatically detects new words to use as tags 
by using a simple rule of thumb I have discovered during my time using Wordpress as a blogging tool. I have found that over 90% of all
tags I use fall into one of the following three categories: Acronyms e.g CIA, FBI, AIG, IT, SQL, ASP, names of people or places and countries.
Therefore using the power of regular expressions I scan posts for words or sentences that match these three groups and then store them
as potential tag candidates.

The more posts are added to a blog the more tags will get added but the good thing about this plugin is that having no existing tags 
stored in your Wordpress DB isn't a bar from using it as it will auto detect suitable tags whenever it comes across them.

Whereas other tag plugins only detect a single occurance of a tag this plugin will search for the most used tags within the content so that 
only relevant tags get added. If you set the MaxTags option to 5 then it will pick the top 5 occurring tags within the post and ignore all others.
The RankTitle option when set means that tags found in the post title are automatically added to the post even if they only occur once and only within
the title.

This plugin is not a replacement for other tag related plugims such as Smart Tags as it doesn't even try to manage the tags within your blog.
The plugin is designed to do one thing and one thing only which is to add the most relevant and appropriate tags to your posts as well as discovering new
tags on the way with as little effort as possible.

Please note this plugin is designed for the English language and will not work with UTF-8 characters.

== Installation ==

This section describes how to install the plugin and get it working.

1. Download the plugin.
2. Unzip the strictly-autotags compressed file.
3. Upload the directory strictlyautotags to the /wp-content/plugins directory on your WordPress blog.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Use the newly created Admin option within Wordpress titled Strictly AutoTags to set the configuration for the plugin.
6. Read carefully all the options and the possible settings that could effect the plugins behaviour and the notes under each option.
7. Tags will now automatically be added to all posts that are added or edited that don't currently have tags associated with it.

Help 

1. If you add a post but no tags are added then it does not mean the plugin is not working just that no tags could be found to associate with the post.

2. Test the plugin is working by creating a new post with the following content and saving it as a draft:

Article Title: The CIA now admits responsibility for torture at Guantanamo Bay

Content: Today the CIA admitted it was responsible for the recent accusations of torture at Guantanamo Bay.

Billy Bob Johnson, the chief station manager at the Guantanamo Bay prison said that the USA had to hold its hands up and admit that it had allowed its CIA operatives to feed the prisoners nothing but McDonalds and Kentucky Fried Chicken meals whilst forcing them to listen to Christian Rock Music for up to 20 hour periods at a time without any break.

The CIA apologised for the allegations and promised to review its policy of using fast food and Christian Rock Music as a method of torture.

3. Save the draft post and check the number of tags that get added. The plugin should have found a number of words to use even if you have no existing saved tags in your site. It is always best to save the article as a draft, check which tags have been used, remove or add any yourself before publishing an article.

4. Some people have complained that they have added words to the stop/noise word list which still get tagged and think the plugin is broken. This is not the case and the problem is usually that the user hasn't removed any new noise words from the system first BEFORE re-scanning. The noise words are only used in the auto discovery stage of the auto tagging and if tags have already been saved in the system then the site will use them in it's relevancy check whether or not they have been marked as noise words. Version 2.0 has a new option to aid the easy removal of noise words from the saved post tag list and this option should be run whenever new noise words are added.

5. If you have articles with lots of capital words in the title e.g THIS IS A TEST ARTICLE or body then set the Ignore Capital Percentage to a level appropriate for your site. Otherwise words will be treated as acronyms when they shouldn't.

6. If you don't want the plugin to hunt out possible new tags for your site then turn Auto Discovery OFF. Then only existing saved post tags will be used to find relevant tags in new articles.

7. Auto Discovery will find new possible words to use as tags using the settings you give it so set them carefully. However if these new words are not found to be relevant to your article they won't get saved against the post OR in the system. Use the Rank Title and Rank HTML options to ensure words in the post title or already in special formatting HTML such as headers or strong tags are considered more relevant than other words.

8. If you have any problems make sure it not related to Wordpress, your server/hosting or other plugins before requesting support. 

9. Please check existing support tickets before writing a new support ticket as the problem may have already been answered. 

10. Always disable all other plugins and leave just Strictly AutoTags on before considering it the likely cause of a problem.

== Changelog ==

= 1.1 =
* Changed internal members from private to protected.
* Fixed bug in which an empty post returned an unitialised array.
* Split up the main AutoTag method so that the 3 AutoDiscovery tests are in their own methods.
* Put compatibility functions into their own include file.
* Changed comments to phpdoc format.

= 1.2 =
* Added Admin page description text into language specific text handler.
* Added continents and major regions into the MatchCountries method.
* Added noise word removal before name matching in the MatchNames method.
* strip all HTML tags from article content before parsing.
* updated regular expression that decapitalises words next to periods to only affect single capitalised words.

= 1.3 =
* Added TrustTitle method to check whether it can be used to auto discover new tags or not.
* Removed all HTML entities before checking content.
* Added extra safety check to term parser to handle previosuly entered bad terms.
* Added IsNoiseWord function in to check for capitalised noise words.
* Changed regular expressions to remove all non word characters instead of some punctuation.

= 1.4 =
* Added new config option Ignore Percentage which sets the percentage of content when capitalised to ignore during auto discovery.
* Added new config option Noise Words which allows user to set the list of noise words to ignore during auto discovery.
* Added new config option Nested Tags which sets how multiple occurring nested tags such as New York, New York City, New York City Fire Department are handled.
* changed regular expression that matches names to match any number of words.
* Added new functions IsNoiseWord, CountCapitals, StripNonWords, ValidContent, SearchContent to strictlyautotags.class.php.
* Added new function IsNothing to strictlyautotagfuncs.php.
* Removed unneccessary rsort call.
* Changed the coding for merging the stored tags and newly discovered ones.

= 1.5 =
* Added IsRomanNumeral function to skip values identified as Aconyms that are Roman Numerals.
* Modifed a few regular expressions using /u which cause errors in cerain cases.
* Added some error handling on some preg_match statements to prevent errors where unknowable tags or patterns cause issues.
* Updated the ShowDebug statement in the function library to handle arrays.

= 1.6 =
* Modified MatchNames method so that noise words are not removed from the auto discover text before hand in one go but from each match instead. This prevent two seperate tags from being added together when they shouldn't have been due to a noise word separating the two.
* Updated the SearchContent method so that the noise words are removed before matching.
* Updated FormatContent so newlines are replaced with periods to reduce false combinations.
* Added a period between the title and content when creating initial search strings.

= 1.7 =
* Added option to re-tag all existing posts or just those currently without tags.
* Modified the MatchNames function so that noise words are not removed from potential matches as this can make too many tags nonsensical.

= 1.8 =
* Removed my own FormatRegEx method and replaced it's usage with preg_quote.
* Removed usage of the non standard add_actions and replaced it's usage with multiple add_action calls.
* Added some major cities to the MatchCountries method.
* Added nonces to admin page to improve security.
* Added esc_attr to HTML input values.

= 1.9 =
* Added new admin option which allows users to remove under used tags and keep their saved tag list to a manageable size.
* Ability to specify how many articles a tag has to belong to when being cleaned.
* Added extra help text for noise word list to remind people that when they add noise words they should remove them from the saved post tags as well.

= 2.0 =
* Added new checkbox option to admin config page called "Remove Saved Noise Tags" which on saving will remove any noise words in the list.
that are currently saved as post tags. This should help the problem where people have thought the system wasn't working due to noise words being matched.
* Changed the format of the admin save page to make it look nicer.

= 2.1 =
* Fixed issue with noise word validator in the admin area so that it handles apostrophes, numbers and dashes.
* Updated the text on the admin page to remind people that this plugin only works with English characters e.g A-Z.

= 2.2 =
* Added new "Rank Important Content" option which ranks matches inside certain HTML tags as more important.
* Added new AUTOTAG_LONG option to increase accuracy so that for content such as New York City Fire Department only an exact match is allowed rather than partials such as New York or New York City.
* Removed the RemoveNoiseWords function call from SearchContent and instead added a check for IsNoiseWord before saving tags.
* Added the RemoveNoiseWords function call to the main AutoTag method to remove noise words from the content used for new tag discovery.
* Updated the SearchContent method to increment hitcounts for previously matched tags.
* Fixed bug with noise word regular expression that was caused by using preg_quote which was adding a slash in front of the pipe delimiters.

= 2.3 =
* Added question mark to the regular expression that matches names and looks for full tag matches.
* Added uninstall option.
* Added counter to show how many tags the plugin has saved since upgrade / installation.
* Added support strictly links to help users support the plugin more easily.
* Added date of installation / upgrade.
* Added register_activation_hook and register_deactivation_hook and the StrictlyAutoTagControl static class.

= 2.4 =
* Added new "Max Tag Words" option which specifies the maximum number of words a tag can have to help prevent long capitalised sentences getting matched.
* Added new "Bold Tagged Words" option to wrap tagged words in strong tags to aid SEO.
* Modified code in the SearchContent method to handle the new Max Tag Words setting.
* Added a few more default locations to the the MatchCountries function including Palestine, Tel Aviv and Belfast.

= 2.5 =
* Added new "Case Sensitive" Noise Word list so that words or phrases that should only be ignored if the exact case matches are handled e.g it is a noise word IT is an acronym
* Fixed bug that prevented phrases from being added to the noise word list
* Updated Bold function so only exact matches of the tag are wrapped in bold tags. This is to prevent incorrect case matches of a tag from being bolded.

= 2.6 =
* Fixed a bug with the case-insensitive noise word list that was lower casing the words before storing them.
* Fixed a bug that tested the format of case-insensitive noise words were correct.
* Added my new Hash Tag Hunter Application to the Stictly Software Recommendations section.

= 2.7 =
* Removed the sponsorship message as per the issue outlined in http://gregsplugins.com/lib/2011/11/26/automattic-bullies/

= 2.8 =
* Added new SEO deeplinking feature to link tags to their relevant tag page.
* New options to only deeplink a tag with a certain number of related articles.
* Option to set how many tags are deeplinked within a post.
* Added new re-bold and re-deeplink posts function for SEO purposes.
* Added the ability to tag custom posts.

= 2.8.1 =
* Remove old call to debug statement in RemoveBoldAndLinks (I use it in other plugins which is why it didn't error for me)

= 2.8.2 =
* Fixed bug that sometimes caused nested deeplinking and bolding within HTML attributes by using placeholders to prevent reverse lookups

= 2.8.3 =
* Rewrote bug fix that was causing nested tags within certain html attributes like alt, title, src and href so that the wording is stored and replaced after bolding or deep linking.
* Removed placeholders for quotes
* Fixed bug that prevented noise words with the digit 0 from being saved.
* Removed some code

= 2.8.4 =
* Try to fix unknown issue that I cannot replicate which people are saying is changing their links/href/src tags. If there is a problem I can only imagine it to be in the storage array which holds these values to prevent tagging by mistake. I am unsetting the array before and after storing/replacing to see if that will help.
* Also wrapping the init of the class in a static class so that if the object is loaded multiple times there is only a singleton not multiple instances. This might help as well.

= 2.8.5 =
* Updated storage array to store content between important content, bold, strong, headers and links etc. So they don't get tagged inside e.g put bolded words inside an existing h4 etc.
* Changed storage array to run "RETURN" twice to handle nested code because of previous change e.g <a href="##STORED##"><img src="##STORED##"></a>.
* Fixed bug that wasn't showing the correct value for the minimum number of tags that a post must have before deeplinking to their tag page in admin.
* Fixed bug in admin to allow noise words to have dots in them e.g for links like youtube.com.
* Added more default noise words to the list.
* Cleaned code that wasn't needed anymore due to changes with the way I handle href/src/title/alt attributes to prevent nested tagging.
* Removed unneccessary regular expressions which are not needed now.
* Added details about all the sexy new features for version 2.8.5 which is donate and deliver only!

= 2.8.6 =
* DONATION ONLY VERSION - Contact me from my site www.strictly-software.com or donate from admin panel £40 to get your hands on this version.
* Updated storage to handle new data-blah and data-blah-blah attributes so they don't get tagged inside images by mistake.
* Added new tag equivalent functionality using a simple method so that you can match instances of certain words BUT use a different tag for example [Snowden, NSA, PRISM]=[Police State] would allow the system to match the words Snowden, Prism and NSA but add a tag "Police State" (if relevant of course). You can use as many replacements as you want using a simple markup format. Allows for you to add a tag that may never appear in the article but is related to other words that do.
* I've added an option to set the minimum number of letters a tag must have before it is used as a tag. This applies to stored tags or newly found ones.
* Added option to tell system whether or not to convert plain text links e.g www.msnbc.com into real links before tagging e.g <a href="http://www.strictly-software">strictly-software</a>
* Created a new name function to match names like al-Qaeda or al-Nusra Front or even words with commas in like 1,000 Guineas which would be tagged as 1000 Guineas.
* Allows for storing tags in shortcodes amd other special html tags such as headers, anchors, strong and bold tags so that words within those tags don't get messed up.

= 2.8.7 =
* Updated the free version storage function to store new data-blah and data-blah-blah attributes so they don't get tagged inside images by mistake.
* Updated the storage function to store [youtube blah] [somesite blah] tags so they don't get tagged by accident.

= 2.8.8 =  
* Added hook so other plugins can act once the tagging is finished.

= 2.8.9 =  
* includes new method to clean all existing post content by removing strong tags and anchors in one update statement.

= 2.9.0 =
* Latest Free Version
* Fixed option that may have caused issues with user capabilities on activation.
* Added hyphen to chars NOT stripped from StripNonWord function.
* Tightened up some regular expression for matching names and hyphenated strings.
* Added ability to update all posts to remove HTML added by the tagger.
* Updated the ReTagAndLink operation to store content, bold, deeplink and put the stored content (alt,title,data-blah,[youtube] etc) back in for each item in loop.
* Returned the "ReTagPosts" operation to just add new tags to a post. No linking or reformatting done on the article at all. The Re-Tag and Re-Format Posts option should be used for doing that.
* Corrected some admin panel spelling mistakes and titles.
* Imporoved auto bold regex so words near end tags get matched that were not before now get matched.
* Fix for anyone suffering old number style admin levels on activation.
* Improved regex that removes HTML added by SEO deeplinking/bold
* Fixed issue with trailing | on noise words causing some regex issues.
* Added new FormatTitle function to help with auto discovery and allow hyphens.
* A much better debug function if you need to turn it on.
* Added code to remove any [shortcode] before auto discovery to prevent attributes being found
* Ask you all to donate me at least a single pound e.g 1 GBP so that I can could stop splitting the plugins into two versions. 150,000 at 1 GBP would mean I could leave my job and work to make brilliant free plugins all the time!
* Fixed bug in above code due to parameters being round wrong way (Microsoft style) that may have stopped auto discovery from working and changed shortcode storage code to handle non space [shortcode] as well as those containing space [youtube url]

= 2.9.1 =
* Latest Paid For Version
* Improved regex for turning strong textual links e.g http://strictly-software.com into clickable links.
* Added option to turn weak textual links such as www.strictly-software.com into clickable links.
* Added option to turn plugin into "CLEAN" mode where you can edit an article one by one but on saving it removed any HTML my plugin may have put in previously. Tags remain. This is for big sites where the bulk clean update method may take too long to run.
* Added option to set minimum words a tag must have before being autotagged.
* Tightened up some regular expression for matching names and hyphenated strings.
* Updated the ReTagAndLink operation to store content, bold, deeplink, remove basic formatting, convert text to links and put the stored content (alt,title,data-blah,[youtube] etc) back in for each item in loop.
* Returned the "ReTagPosts" operation to just add new tags to a post. No linking or reformatting done on the article at all. The Re-Tag and Re-Format Posts option should be used for doing that.
* Improved new secondary name function which will match things like 1,000,000 Dollar Man or 5000 Pounds, al-Nusra Front, Pope John Paul VI etc.
* Imporoved auto bold regex so words near end tags get matched that were not before.
* Corrected some admin panel spelling mistakes and titles.
* Improved regex that removes HTML added by SEO deeplinking/bold.
* Fixed issue with trailing | on noise words causing some regex issues.
* Added new FormatTitle function to help with auto discovery and allow hyphens.
* A much better debug function if you need to turn it on.
* Fix for anyone suffering old number style admin levels on activation.
* Added code to remove any [shortcode] before auto discovery to prevent attributes being found
* Fixed bug in above code due to parameters being round wrong way (Microsoft style) that may have stopped auto discovery from working and changed shortcode storage code to handle non space [shortcode] as well as those containing space [youtube url]