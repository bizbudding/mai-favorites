# Changelog

## 2.4.3 (12/5/24)
* Changed: Updated the updater.

## 2.4.2 (11/27/23)
* Changed: Updated the updater.

## 2.4.1 (6/20/23)
* Fixed: Updater object not set.

## 2.4.0 (6/20/23)
* Changed: Updated the updater.

## 2.3.0 (4/5/22)
* Changed: Post type is now public so it works out of the box with SearchWP and FacetWP and similar plugins. Singular views are still not enabled because publicly_queryable is still false.

## 2.2.0 (7/19/21)
* Added: Now Favorite Categories can be registered to post types that use the block editor (via `register_taxonomy_for_object_type`).

## 2.1.0 (4/15/21)
* Added: New "nofollow" checkbox on Favorites to add "nofollow" rel attribute to the link.

## 2.0.4 (2/13/21)
* Added: Mai logo icon to updater.

## 2.0.3 (1/26/21)
* Fixed: Mai Post Grid custom more link text now works as expected.

## 2.0.2 (12/11/20)
* Changed: Plugin header consistency, again.

## 2.0.1 (12/1/20)
* Changed: Plugin header consistency.
* Changed: Updated the updater.

## 2.0.0 (8/6/20)
* Added: Support for Mai Engine (v2).
* Changed: Remove dependency on CMB2, and use custom meta box.

## 1.2.1 (1/24/18)
* Fixed: Undefined index error if grid is used without any content parameter.

## 1.2.0 (1/24/18)
* Added: Page attributes support for easier ordering of favorites.

## 1.1.0 (1/23/18)
* Added: Set default grid target to "_blank" and rel to "noopener".
* Changed: Only run updater in the admin.

## 1.0.2 (3/16/18)
* Changed: Point to correct GH repo.
* Changed: Updated the updater to 4.4.
* Fixed: More link text wasn't correctly falling back to more_link_text param in [grid]

## 1.0.1
* Changed: Better updater handling.

## 1.0.0
* Initial Release.
