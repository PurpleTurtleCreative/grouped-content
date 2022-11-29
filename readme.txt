=== Grouped Content ===
Contributors: michelleblanchette
Tags: page, management, hierarchy, organize, order, sort, view, content, group, related
Tested up to: 6.1.1
Requires at least: 4.7.1
Stable tag: 3.0.0
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.txt

Provides easy access to hierarchical posts' parent page, sibling pages, and child pages in wp-admin.

== Description ==

Grouped Content helps you manage and access your hierarchical pages (including hierarchical custom post types) naturally and automatically. **All it takes is the simple action of setting a parent page.** _(Which you are probably already doing!)_

= Enhanced Page Management =
With Grouped Content, you’ll be able to access pages faster, quickly generate new draft pages in bulk, and better visualize your site’s hierarchy. Additionally, you'll be able to see overview information of each page group. This can give you insight into which content groups are the largest or have the most drafts.

= Features =
Grouped Content is light and simple with a few powerful features. You can get started immediately all while keeping control of your site’s pages with these main features:

- **Groups Admin Page**. View hierarchical pages and group statistics in an easy-to-navigate, distraction-free area.
- **Page Relatives Metabox**. Access parent, sibling, and child pages immediately upon setting a parent page.
- **Content Generator**. Quickly add new draft pages and automate menu creation to efficiently create new groups of content and navigation for your site.

== Installation ==

Grouped Content does not require any configurations after being activated. If you have already been making use of page hierarchies, Grouped Content will automatically enhance your experience of maintaining page structures.

Here are the features you should notice upon proper installation and activation:

= The Groups Screen =
Tucked underneath each hierarchical post type (such as "Pages") in the WordPress admin menu, you should see a submenu item labeled "(Page) Groups". This is where you’ll be able to instantly see any page hierarchies that you’ve established. If you have not set any Parent Pages, then this will be empty until you do.

= The Page Relatives Metabox =
Another noticeable feature that’s been added is the Page Relatives metabox. It is available in the righthand sidebar when editing a Page (or any other hierarchical post). If the page currently being edited has been assigned a Parent Page or the page has been assigned as the Parent Page of other pages, the Page Relatives box will display links to the related content. This makes it easier to navigate around hierarchical content!

= The Content Generator =
In Tools > Create Draft Pages, you can quickly create empty draft pages under a specified parent page. You can also automatically create a menu of the generated page posts, too! This is helpful for quickly outlining new content groups on your WordPress website, such as resource directories or information modules.

*This plugin is multisite compatible and may be activated per site or network activated for all sites. There is no difference in functionality between the different activation methods.*

*If you have any questions during or after installation, please submit to [Grouped Content’s support forum](https://wordpress.org/support/plugin/grouped-content/).*

== Frequently Asked Questions ==

= Why don’t I see anything in (Page) Groups? =

Groups are formed when you set a Parent Page for a hierarchical post (eg. page). When editing a page, you may assign a Parent Page in the Page Attributes section on the righthand side of your edit screen.

If you can’t find this panel, it may be hidden. Be sure to check the Screen Options to enable the metabox!

= Why can’t I find the (Page) Relatives metabox? =

If the Page Relatives metabox is not listed on the righthand side when editing a page, it may be hidden. Be sure to check the Screen Options to enable the metabox!

== Screenshots ==

1. **Content Generator.** Automate the process of adding new pages to your site's hierarchy and creating site menus.
2. **View Page Groups.** Quickly see top-level details and effortlessly navigate through your site’s pages.
3. **Page Relatives Metabox.** Immediately have access to parent, sibling, and child pages with Gutenberg compatibility.
4. **Classic or Gutenberg.** The Page Relatives metabox is conveniently available for whichever editor you prefer.

== Changelog ==

= 3.0.0 – 2022-11-28 =
* New: Groups now support hierarchical custom post types! The "Groups" admin page for each post type is added to its submenu in wp-admin.
* New: The Page Relatives metabox now supports hierarchical custom post types! Find it in the post edit screen of any hierarchical post type.
* Fix: Asynchronous update of the Page Relatives metabox failing after upating the Page Parent in Gutenberg.
* Fix: Highlight the post parent node in the Page Relatives metabox when it is the current post.
* Fix: Silenced a repetitive console log when editing a page in the Gutenberg editor.
* Tweak: The content generator is now under Tools > Create Draft Pages in wp-admin.
* Removed: The main "Groups" admin menu page. "Groups" are now in each hierarchical post type's admin menu.
* Removed: "id" member variable from PTC_Content_Group class. Use $ptc_content_group->post->ID instead.

= 2.0.0 – 2022-05-04 =
* Fix: Author link ?post_type=page param was misspelled as ?post-type=page in the Groups details admin screen
* Tweak: Refactored code to improve source code organization and better follow WordPress Coding Standards

= 1.2.3 – 2020-05-18 =
* Fix: Child pages published before their parent would have malformed permalinks
* Fix: Parent page setting would be overwritten when updating a child page that has a draft parent
* Tweak: Changed Font Awesome 5 for anonymous loading of Font Awesome 4.7.0 as suggested by the plugins team

= 1.2.2 – 2020-05-12 =
* Fix: PHP 7.0 compatibility issue breaking the Groups details output screen
* Tweak: Removed admin notices from plugin pages
* Tweak: Removed FontAwesome5 local files to instead load remotely

= 1.2.1 – 2020-01-24 =
* Fix: Improved loading condition for Page Relatives metabox scripts

= 1.2.0 – 2020-01-24 =
* New: Groups toplevel directory details
* New: Added sequential content option to Content Generator for improved page sorting
* Tweak: Improved styling consistency for Groups admin screens
* Tweak: Page Relatives metabox now refreshes when saving a new Parent Page in the Gutenberg editor

= 1.1.0 – 2020-01-13 =
* New: Content Generator feature to add new draft pages in bulk with a corresponding menu

= 1.0.0 – 2020-01-03 =
* New: Groups admin menu page to navigate through hierarchical content in a natural, distraction-free area
* New: Page Relatives metabox to quickly access related pages and groups
* Initial Release
