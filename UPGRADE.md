Upgrading Instructions for Site Map Extension for Yii 2
=======================================================

!!!IMPORTANT!!!

The following upgrading instructions are cumulative. That is,
if you want to upgrade from version A to version C and there is
version B between A and C, you need to following the instructions
for both A and B.

Upgrade from 1.0.2
------------------

* PHP requirements were raised to 5.6. Make sure your code is updated accordingly.

* Constants `MAX_ENTRIES_COUNT` and `MAX_FILE_SIZE` has been removed from `BaseFile` class.
  Make sure you do not use these constants anywhere in your code.

* The signature of `\yii2tech\sitemap\File::writeUrl()` was changed. The method has got an extra optional parameter `$extraContent`.
  If you extend this method, make sure to adjust your code.
