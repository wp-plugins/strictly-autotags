=== Strictly Auto Tags ===
Contributors: Strictly Software
Donate link: http://www.strictly-software.com/donate
Plugin Home: http://www.strictly-software.com/plugins/strictly-auto-tags
Tags: tags, autotag, taxonomy, smarttag
Requires at least: 2.0.2
Tested up to: 2.9.2
Stable tag: 1.2

Strictly AutoTags is a plugin that automatically adds the most relevant tags to posts.


== Description ==

Strictly AutoTags is a plugin that scans a post for words that could be used as tags and then orders them so that the most relevant
words get added to the post. Just because a word appears in a post that is already in your list of tags does not mean that it should
automatically be added against the article. Therefore the plugin orders all matching tags in descending order and picks only those that occur the
most.

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

== Installation ==

This section describes how to install the plugin and get it working.

1. Download the plugin.
2. Unzip the strictly-autotags compressed file.
3. Upload the directory strictlyautotags to the /wp-content/plugins directory on your WordPress blog.
4. Activate the plugin through the 'Plugins' menu in WordPress.
5. Use the newly created Admin option within Wordpress titled Strictly Auto Tags to set the configuration for the plugin.
6. Tags will now automatically be added to all posts that are added or edited that don't currently have tags associated with it.

Help 

1. If you add a post but no tags are added then it does not mean the plugin is not working just that no tags could be found to associate with the post.

2. Test the plugin is working by creating a new post with the following content:

Title: CIA admits responsibility for torture at Guantanamo Bay

Content: Today the CIA admitted it was responsible for the recent accusations of torture at Guantanamo Bay.

Billy Bob Johnson, the chief station manager at the Guantanamo Bay prison said that the United States of America had to hold its hands up and admit that it had allowed its CIA operatives to feed the prisoners nothing but McDonalds and Kentucky Fried Chicken meals whilst forcing them to listen to Christian Rock Music for up to 20 hour periods at a time without any break.

The CIA apologised for the allegations and promised to review its policy of using fast food and Christian Rock Music as a method of torture.

3. Save the post and check the number of tags that get added. The plugin should have found a number of words to use even if you have no existing saved tags in your site.

== Changelog ==

= 1.1 =
* Changed internal members from private to protected
* Fixed bug in which an empty post returned an unitialised array
* Split up the main AutoTag method so that the 3 AutoDiscovery tests are in their own methods
* Put compatibility functions into their own include file
* Changed comments to phpdoc format

= 1.2 =
* Added Admin page description text into language specific text handler
* Added continents and major regions into the MatchCountries method
* Added noise word removal before name matching in the MatchNames method
* strip all HTML tags from article content before parsing
* updated regular expression that decapitalises words next to periods to only affect single capitalised words
