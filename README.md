# Dravencms File Download module

This is a simple File Download module for dravencms

## Instalation

The best way to install dravencms/file-download is using  [Composer](http://getcomposer.org/):


```sh
$ composer require dravencms/file-download
```

Then you have to register extension in `config.neon`.

```yaml
extensions:
	file-download: Dravencms\FileDownload\DI\FileDownloadExtension
```

## Admin authorization and mutations

Administration uses User 3.1, Admin 3.2 and Application 0.3 guards. Screens and write callbacks enforce the existing provider-defined permissions server-side, including direct URLs and bulk callbacks. Mutating signals require POST and a session-bound action token; synchronize the matching admin assets when upgrading. See the User package's `user:security:migrate` instructions for the password-reset schema change.


## Contextual file delivery

This release requires Dravencms File 3 / Nette Files 3. Image URLs include a specific
file resolver and attachment reference; templates no longer pass bare files. Published
attachments are public-cacheable; admin previews of unpublished content require the
plugin's edit permission and are private. Original files and generated images stay
outside the document root. Install the private-storage configuration and run File's
checksum/storage migrations before serving this release. See the File package README.

Download attachments have no publication flag in this package and remain publicly
available through `download.attachment`; this resolver verifies the actual translated
attachment record. Applications requiring restricted downloads should supply a
separate resolver with their own access rules.
