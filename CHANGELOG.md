# Changelog

## [1.3.0](https://github.com/roadrunner-php/psr-logger/compare/1.2.0...1.3.0) (2026-11-03)


### Features

* add `FlattenProcessor` to send nested context maps as dot-separated attributes ([#28](https://github.com/roadrunner-php/psr-logger/issues/28)) ([c0fa01f](https://github.com/roadrunner-php/psr-logger/commit/c0fa01fb20a437cc7c3ae21c45953a69369e3612))
* export `JsonSerializable` objects in the log context by their `jsonSerialize()` data ([#26](https://github.com/roadrunner-php/psr-logger/issues/26)) ([ec01ac6](https://github.com/roadrunner-php/psr-logger/commit/ec01ac6c8269e07c0ed710309805d37e5c9c9117))
* limit nesting depth and array size of the log context ([#25](https://github.com/roadrunner-php/psr-logger/issues/25)) ([751dadb](https://github.com/roadrunner-php/psr-logger/commit/751dadbf65cc30576cc3e06301017c2174c8375c))
* log previous exceptions and cut exception traces to 8 frames ([#27](https://github.com/roadrunner-php/psr-logger/issues/27)) ([801cbd0](https://github.com/roadrunner-php/psr-logger/commit/801cbd0459c7864e434f3ff969bc809793353d9a))


### Bug Fixes

* keep context values app-logger cannot encode ([#23](https://github.com/roadrunner-php/psr-logger/issues/23)) ([2ccf9a7](https://github.com/roadrunner-php/psr-logger/commit/2ccf9a7270ba677f6be814db956f086ab60d3acb))
* send context keys reserved by RoadRunner as `context.<key>` ([#24](https://github.com/roadrunner-php/psr-logger/issues/24)) ([13c6ce6](https://github.com/roadrunner-php/psr-logger/commit/13c6ce6bc1c321a8e09a862f610457dc081205b6))


### Code Refactoring

* take only an array of processed context in `encodable()` ([2ccf9a7](https://github.com/roadrunner-php/psr-logger/commit/2ccf9a7270ba677f6be814db956f086ab60d3acb))

## [1.2.0](https://github.com/roadrunner-php/psr-logger/compare/1.1.0...1.2.0) (2026-10-10)


### Features

* support RoadRunner v3 ([3d58180](https://github.com/roadrunner-php/psr-logger/commit/3d581800a4c9aeac7c3707fb1cc428ae6f9a5761))

## [1.1.0](https://github.com/roadrunner-php/psr-logger/compare/1.0.0...1.1.0) (2026-10-09)


### Features

* migrate tests to Testo, require PHP 8.2 ([#16](https://github.com/roadrunner-php/psr-logger/issues/16)) ([1177b97](https://github.com/roadrunner-php/psr-logger/commit/1177b97df7cb806257c0bd467720ccd8c4aa35df))


### Bug Fixes

* log exceptions in context as structured arrays ([#20](https://github.com/roadrunner-php/psr-logger/issues/20)) ([d7d301d](https://github.com/roadrunner-php/psr-logger/commit/d7d301d3254bdf7b78299849b813c08d48886cc4))
* stop infinite recursion on objects with reference cycles ([#21](https://github.com/roadrunner-php/psr-logger/issues/21)) ([36788d4](https://github.com/roadrunner-php/psr-logger/commit/36788d4182bf07009cf50ed788e0301b53a81804))


### Documentation

* add mutation testing badge ([f1c8914](https://github.com/roadrunner-php/psr-logger/commit/f1c891444b670cadadbd36932fb0bff19b6ab2d2))
* refresh README header and examples ([#13](https://github.com/roadrunner-php/psr-logger/issues/13)) ([b1c1949](https://github.com/roadrunner-php/psr-logger/commit/b1c19492441617e7593c57ad957e9d08dcb81939))


### Code Refactoring

* resolve psalm errors instead of suppressing them ([#19](https://github.com/roadrunner-php/psr-logger/issues/19)) ([75db7b4](https://github.com/roadrunner-php/psr-logger/commit/75db7b428317840b64d63d6cbd102ee941c654dd))
