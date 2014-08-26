couchbeard-plugin
=================
Wordpress plugin to access xbmc, couchpotato, sickbeard and sabnzbd functionality.
Widget added, so now it is easy to implement on your own theme.

Still in developer stage.

How to use
==========
Enter your informations in the app and use the widget to get an overall look.

Progress
========
 - Couchbeard abstract class (done)
 - Couchpotato class extending couchbeard (done)
 - Sickbeard class extending couchbeard (done)
 - Sabnzbd class extending couchbeard (done)
 - Xbmc class extending couchbeard (done)
 - Widget (done)
 - Couchpotato ajax calls (80% done)
 - Sickbeard ajax calls (50% done)
 - Sabnzbd ajax calls (10%)
 - Xbmc ajax calls (50%)
 - Couchpotato view (70% done)
 - Sickbeard view (50% done)
 - Sabnzbd view
 - Xbmc view
 - Update/refresh button
 - Themes (default and dark) for the plugin
 - Search bar with autocomplete


How to use
==========
Objects with login or api keys have to use the abstract class couchbeard. The class couchbeard are storing login and api key informations in the database. With the abstract class it is possible to retrieve keys.
Make sure to add the application to the array of api-keys or login in couchbeard-plugin, so it is added to the database.
