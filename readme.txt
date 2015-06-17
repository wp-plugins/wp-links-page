=== WP-Links-Page-Free ===
Contributors: Allyson Rico, Robert Macchi
Tags: wp-links, links, links page, link screenshots, link directory, link gallery, link thumbnails
Requires at least: 
Tested up to: 4.2.2
Stable tag: 

This plugin allows you to create a dynamic link gallery with screenshots of each link.

== Description ==

This plugin allows you to create a dynamic link gallery with screenshots of each link. It will automatically create screenshots of each link and save you from creating a hardcoded links page or finding images for each site. It allows sites to create a links page with several different views. The screenshots for the links can be updated on a set schedule or with the click of a button. Links can be added, edited, and reordered on the setup page.

This free version only allows six links to be added. For the full version please visit: http://www.wplinkspage.com/

== Installation ==

Installation
Uploading via WordPress Dashboard

    Navigate to the ‘Add New’ in the plugins dashboard
    Navigate to the ‘Upload’ area
    Select wp-links-page.zip from your computer
    Click ‘Install Now’
    Activate the plugin in the Plugin dashboard

Using FTP

    Download wp-links-page.zip
    Extract the wp-links-page.zip directory to your computer
    Upload the wp-links-page.zip directory to the /wp-content/plugins/ directory
    Activate the plugin in the Plugin dashboard

== Usage ==

— Adding and Editing Links —

With this version you are limited to six links only. Purchase the pro version for unlimited links at http://wplinkspage.com/purchase/

Visit the WP Links Page section of the dashboard to add, reorder, and edit the links.

Add links by entering the link URL/description and hit the add link button.

You may edit the link or description with the edit button.

To reorder your links simply drag and drop them into place, then click ‘Save’ at the bottom of the screen.

When updating the links, press the edit button and make your changes. Click update to save your changes. Please do not try to update multiple links at the same time. The update button will only update its link, not any others.

Clicking the ‘Update Screenshots’ button on the this page can take several minutes depending on your connection. Please be patient while it retrieves new images. If for some reason it does not automatically refresh when completed, simply refresh the page to see the new images.

— Settings —

Visit the Settings subpage in the WP Links Page section to set the timeframe to retrieve new screenshots and edit the settings for the links page view.

The Grid layout does not show the description. Use the List layout to display the link descriptions.

Options:

    Screenshot refresh rate: Twice Daily, Daily, Every two days, Weekly, Every two Weeks, Monthly.
    Display: Grid or List.
    Columns for Grid: 1, 2, 3, 4 or 5

— Widget —

A new widget is also provided. This widget will display a one column grid of the links with descriptions only showing if the ‘List’ option is chosen.

If using a enhanced Text Widget you may enter the the Wp links Page shortcode to display links.

With this version the widget only gives the option to change the title. Purchase the pro version for the extra widget features at http://wplinkspage.com/purchase/

Options:

    Title
    Number of Links to display
    Display Descriptions toggle

— Shortcode -

Use this shortcode to add your links to any page or post:

[wp_links_page_free]

    Displays all links with the global settings.

With this version you are limited to the above shortcode only. Purchase the pro version take advantage of the fine tuned control available below at http://wplinkspage.com/purchase/

[wp_links_page num=”1″]

    Displays only the first link.
    Change the number to any other link number to display a different link.
    This shortcode always fills 100% of the width regardless of list or grid options.
    Allowed Options: Any Number

[wp_links_page type=”grid”]

    Overrides the global settings to either grid or list.
    This option is case sensitive. It will understand “grid” but not “Grid”.
    Allowed Options: grid or list

[wp_links_page cols=”2″]

    Overrides the current number of columns in the grid.
    If the list setting is enabled this option will be ignored.
    Allowed Options: 2, 3, 4, or 5

[wp_links_page description=”yes”]

    Sets the option to display descriptions on the grid.
    If the list setting is enabled descriptions are always displayed regardless of this option.
    Allowed Options: yes or no

[wp_links_page limit=”3″]

    Limits the amount of links shown to this number
    e.g. if 3 is chosen only 3 links will display.
    Allowed Options: any number

For more detailed documentation there is a help page included with this plugin found under the WP Links Page Free section in your dashboard.

You can also visit http://www.wplinkspage.com/ for more information.

== ChangeLog ==

= Version 0.1 =

* Initial version of this plugin.
