================================================
              XPLAYHISTORY README
================================================

1 - Requirements
2 - Instalation
3 - Preferences
4 - Upgrades
5 - Client side help (player plugins)

------------------------------------------------

This is not a standalone application. XPlayHistory is a module for
the XOOPS content management system version 2.x.

1 - Requirements

Xoops 2.x - available at www.xoops.org
If you got Xoops 2.x up and running you got everything else that's needed.

2 - Instalation

The module is a simple xoops module and requires no further action
besides the regular instalation procedures for a xoops 2.0 module.

3 - Preferences

- Number of most recent songs to display.
  The main page shows a list of the most recent songs you listened to.
  This controls the length of that list

- Consider consecutive duplicates?
  If xplayhistory should consider duplicated consecutive songs or not.
  For example if you like to listen to the same song over and over again all
  day long but whant it to show up only once in the list.

- The Shame List
  Are you ashamed of certain artists you listen to and do not whant other
  people to know abbout them? This was made for you.
  Just add one shamefull artist per line and forget abbout it.	

- Minimum time spacing between two songs
  If a song is submited after another within this time frame the prefious
  one will be removed from the list. This is usefull when you are suposed
  to be just browsing/previewing your songs and dont want them to be logged.
  
4 - Upgrades
  Nothing yet !!!
  
5 - Client side help (player plugins)

	For now I have only tested solutions with Winamp (www.winamp.com) but
	plugins like AMIP are suposed to work with and iTunes as well.
	
	Besides AMIP I have tested winamp with the Now Playing plugin.
	
	-- HTTP parameters:
	
	Xplayhistory gets the parameters from the http request regardless of the
	method, in other words: either GET or POST will work fine.
	For now the parameters used by xplayhitory are:

	Artist - Mandatory
	Title - Mandatory
	Album - Optional
	Year - Optional
	Genre - Optional
	
	If by any chance "Artist" or "Title" are not informed the song will be simply
	ignored. All the other parameters are optional. Tip: Keep your ID3 tags neat
	and your play history will be more readable and accurate.

	-- Winamp plugins
	Now Playing is very straightforward and configuration is a brease. Let´s
	jump to my favorite: AMIP. Why is it my favorite? Because it does not freeze
	winamp for a few seconds every song, it´s really stable, more flexible, gives
	us the possibility to use other ID3 fields in xplayhistory in the future (see
	AMIP help for all tags supported) and is still being improved frequently. Anyway:
	
	See chapter Web Integration in AMIP help file for instructions to set everything up.
	When everything is installed and ready to go just add this to your selected preset:
	
	/exec: ([PATH TO CURL]\curl.exe) -G -d "Title=&func_ue(%2)&Album=&func_ue(%4)&Artist=&func_ue(%1)&Genre=&func_ue(%7)" http://[YOUR XOOPS ROOT URL]/modules/xplayhistory/add-song.php
		
	Links:
		Winamp: www.winamp.com
		AMIP: amip.tools-for.net
		Now Playing: http://www.cc.jyu.fi/~ltnevala/nowplaying/
		             or
		             http://www.winamp.com/plugins/details.php?id=138883
		             

	----------------------------
	Any feedback will be welcome
	Marco Garbelini
	marco@garbelini.net
