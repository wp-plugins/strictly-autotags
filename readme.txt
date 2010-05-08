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

e.g.

1. Download the plugin
2. Unzip the StrictlyAutoTags compressed file
3. Upload the directory strictlyautotags to the /wp-content/plugins directory on your WordPress blog
4. Activate the plugin through the 'Plugins' menu in WordPress

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
* strip all HTML tags from article content
