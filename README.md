# Overview

This module adds some additional CLI commands in the `catalog:media` namespace. They are particularly useful for
cleaning up the media gallery database and filesystem as Magento tends to leave behind orphaned records, files, etc
over time.

## Terminology

Within this module and the commands herein, we use the following terminology to refer to data:
* **Cache** - a modified version of a media gallery image, stored in the pub/media/catalog/product/cache folder
* **Missing** - an image which is referenced in the database, but does not exist on the filesystem
* **Orphaned** - an image which is referenced in the database, but is not in use on any current products
* **Unused** - an image which is present on the filesystem, but not in the database, and as such is not currently in use
* **Temporary** - a temporary image which is present on the filesystem

## Installation

Run the following commands from the project root directory:

```
#Add the repo to your composer.json file
composer config repositories.magentocode-magento2-cli-media-tool vcs https://github.com/MagentoCode/magento2-cli-media-tool.git
#Require the latest stable build
composer require magentocode/magento2-cli-media-tool
#Enable the Magento module
bin/magento module:enable MagentoCode_CliMediaTool
#Run the Magento CLI upgrade tool
bin/magento setup:upgrade
```

## CLI Commands

### catalog:media:info

Use this command to retrieve a summary of information about the current state of your product media gallery. This can be
used as an easy way to run a checkup on your media gallery to see if you're wasting any disk space on old images. In
general, a "healthy" media gallery should have the same number of `Media Gallery entries` as it does
`Non-cache media files` with no `Unused`, `Missing`, `Orphaned` or `Temporary` files or media gallery entries. The
number of `Cache media files` should not be taken into consideration.

```
bin/magento catalog:media:info
===============================================
Media Gallery entries: 67444.
Orphaned media gallery entries: 0.
Non-cache media files on filesystem: 67444.
Cache media files on filesystem: 4813.
Unused files on filesystem: 0.
Missing files on filesystem: 0.
Orphaned files on filesystem: 0.
Temporary files on filesystem: 0.
===============================================
```

### catalog:media:cleanup

##### This is a destructive operation and will make changes to your database and filesystem when run.

Use this command to automatically clean up your media gallery database and filesystem. Running this command is the same
as running `catalog:media:orphaned:remove`, `catalog:media:missing:remove`, `catalog:media:unused:remove`
and `catalog:media:temporary:remove` except it removes **all** orphaned records from the database instead of just those
with files on the filesystem. It also does all of this for you in a single command, and is particularly useful for a
quick cleanup of your media gallery. **It is highly recommended that you make a backup of your site files and database
before running this command!**

```
bin/magento catalog:media:cleanup
===============================================
===     Beginning Media Gallery Cleanup     ===
===============================================
   Files: 67444
   Media Gallery Entries: 67444
===============================================
Removed 0 orphaned media gallery paths which are no longer in use on any products.
Removed 0 media gallery paths that no longer have images on the filesystem.
Removed 0 files which are no longer present in the media gallery database table.
Removed 0 temporary files older that 24 hour(s).
===============================================
===                  Done                   ===
===============================================
   Files: 67444
   Media Gallery Entries: 67444
===============================================
```

### catalog:media:cache

This command simply outputs the full absolute file path to each of the `Cache` file paths in the media gallery. It is
mostly intended for use with custom shell scripts or external programs which may want to grab this output for their own
use.

```
bin/magento catalog:media:cache
/path/to/pub/media/catalog/product/i/m/image1.jpg
/path/to/pub/media/catalog/product/i/m/image2.jpg
/path/to/pub/media/catalog/product/i/m/image3.jpg
/path/to/pub/media/catalog/product/i/m/image4.jpg
```

### catalog:media:cache:remove

This command will remove all current `Cache` files from the filesystem and output the number of items removed. If
you need to re-generate your cache images, use the `catalog:image:resize` command which is provided by Magento 2's
stock CLI.

```
bin/magento catalog:media:cache:remove
Removed 4813 cache files.
```

### catalog:media:missing

This command simply outputs the full absolute file path to each of the `Missing` file paths in the media gallery. It is
mostly intended for use with custom shell scripts or external programs which may want to grab this output for their own
use.

```
bin/magento catalog:media:missing
/path/to/pub/media/catalog/product/i/m/image1.jpg
/path/to/pub/media/catalog/product/i/m/image2.jpg
/path/to/pub/media/catalog/product/i/m/image3.jpg
/path/to/pub/media/catalog/product/i/m/image4.jpg
```

### catalog:media:missing:remove

This command will remove all current `Missing` image references from the database and output the number of items
removed.

```
bin/magento catalog:media:missing:remove
Removed 0 database references to missing files.
```

### catalog:media:orphaned

This command simply outputs the full absolute file path to each of the `Orphaned` file paths in the media gallery. It is
mostly intended for use with custom shell scripts or external programs which may want to grab this output for their own
use.

```
bin/magento catalog:media:orphaned
/path/to/pub/media/catalog/product/i/m/image1.jpg
/path/to/pub/media/catalog/product/i/m/image2.jpg
/path/to/pub/media/catalog/product/i/m/image3.jpg
/path/to/pub/media/catalog/product/i/m/image4.jpg
```

### catalog:media:orphaned:remove

This command will remove all current `Orphaned` files from the filesystem and output the number of items removed.

```
bin/magento catalog:media:orphaned:remove
Removed 0 orphaned files.
```

### catalog:media:unused

This command simply outputs the full absolute file path to each of the `Unused` file paths in the media gallery. It is
mostly intended for use with custom shell scripts or external programs which may want to grab this output for their own
use.

```
bin/magento catalog:media:unused
/path/to/pub/media/catalog/product/i/m/image1.jpg
/path/to/pub/media/catalog/product/i/m/image2.jpg
/path/to/pub/media/catalog/product/i/m/image3.jpg
/path/to/pub/media/catalog/product/i/m/image4.jpg
```

### catalog:media:unused:remove

This command will remove all current `Unused` files from the filesystem and output the number of items removed.

```
bin/magento catalog:media:unused:remove
Removed 0 unused files.
```

### catalog:media:temporary

This command simply outputs the full absolute file path to each of the `Temporary` file paths in the media gallery. It is
mostly intended for use with custom shell scripts or external programs which may want to grab this output for their own
use.

```
bin/magento catalog:media:temporary -m 24
/path/to/pub/media/tmp/catalog/product/i/m/image1.jpg
/path/to/pub/media/tmp/catalog/product/i/m/image2.jpg
/path/to/pub/media/tmp/catalog/product/i/m/image3.jpg
/path/to/pub/media/tmp/catalog/product/i/m/image4.jpg
```

### catalog:media:temporary:remove

This command will remove all current `Temporary` files from the filesystem and output the number of items removed.

```
bin/magento catalog:media:temporary:remove -m 24
Removed 0 temporary files older that 24 hour(s).
```
