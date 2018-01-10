# Changelog

## 2.0.15 - 2018-01-10

- Add compatibility with symfony/process v4

## 2.0.14 - 2018-01-06

- Fixed PHP path detection

## 2.0.13 - 2017-12-08

- Changed default tld from `.dev` to `.test`
- Fixed `valet share` for linked sites
- Change FPM port to 9001 ([#51](https://github.com/cretueusebiu/valet-windows/pull/51))
- Other minor fixes ported from [laravel/valet](https://github.com/laravel/valet)

## 2.0.12 - 2017-09-27

- Fixed global composer files ([#49](https://github.com/cretueusebiu/valet-windows/issues/49)).

## 2.0.11 - 2017-08-04

- Pretty print sites
- Update some drivers
- Added wildcard certificates
- Fixed Nginx upload max size
- Added `--secure` option for `valet link`
- Added ability to pass directory to `valet open`
- Other minor fixes

## 2.0.10 - 2017-05-23

- Fixed SSL certificate ([#29](https://github.com/cretueusebiu/valet-windows/pull/30)).

## 2.0.9 - 2017-04-03

- Fixed issues ([#10](https://github.com/cretueusebiu/valet-windows/issues/10) and [#18](https://github.com/cretueusebiu/valet-windows/issues/18)) related to paths.

## 2.0.8 - 2017-01-25

- Fixed Nginx configuration for secured sites.

## 2.0.7 - 2017-01-21

- Fix Nginx when parking a directory from other drives than `C:`.
- Read the domain set in config when reinstalling.

## 2.0.5 - 2017-01-12

- Restart PHP-FPM service on failure.
- Upgrade [WinSW](https://github.com/kohsuke/winsw).
- Fix Nginx `server_names_hash_bucket_size` error.

## 2.0.4 - 2017-01-02

- Fix `valet link` command.
- Configure [Acrylic DNS Proxy](http://mayakron.altervista.org/wikibase/show.php?id=AcrylicWindows10Configuration) automatically.
