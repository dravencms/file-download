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
