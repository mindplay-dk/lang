mindplay/lang
=============

This library implements an ultra-simple, super-lightweight localization facility. (~100 SLOC)

It consists of a single pseudo-namespace with just a few functions, but affords a surprising
amount of flexibility, including the ability to handle plurality, such as "1 result" vs "5 results",
without the (mental or computational) overhead of inventing any proprietary syntax.

##### Concept

Strings are translated from english source text, optionally with name/value tokens being replaced.

Alternatively, you can use keys instead of english source text, e.g. `NUM_RESULTS` rather than
`{num} results` - this is solely a matter of convention, and has no bearing on how the translation
mechanism works, you just need to understand the pros and cons of each strategy:

  * With english source text, you get more readable code, and you may not need any english language
    files at all - the downside is that even a small change (capitalization, punctuation) to the
    english source text will require the source text to be updated in all translation files. (on the
    other hand, you could view it as an advantage, that any change to the source text will require
    updates to the translations, forcing you to keep translations up to date.)
    
  * With keys, your source code is arguably less readable, but more sturdy - minor changes to english
    source text doesn't affect anything. Using this approach however, you must ship a set of english
    language files with your project or package. (again, you could view it as an advantage, that the
    english source files provide a source of reference for translators.)

Translations are grouped into "translation domains", which map to subfolders and plain PHP files,
e.g. one file per translation language.

By convention, your base translation domain should be your Composer package name, e.g. `{vendor}/{package}`,
optionally with translation sub-domains nested below those.

The whole thing is `static`, and if that makes you quibble - relax. Translations *are* after all
global, in the sense that there's only one translation of each string for any given language.

## Usage

To translate a string, simpy do this:

```php
use mindplay\lang;

echo lang::text("foo/bar", "Hello {who}", ["who" => "World"]); // => "Hello World"
```

This will translate to the active language, which you can set like this:

```php
lang::set("en");
```

To obtain a string in a specific language (ignoring the active language) use the `translate()` function:

```php
$text = lang::translate("en", "foo/bar", "...");
```

Alternatively, you can obtain a translation function for a specific language domain, and reuse it:

```php
$t = lang::domain("foo/bar");

echo $t("Hello {who}", ["who" => "World"]); // => "Hello World"
echo $t("Hi {who}", ["who" => "World"]); // => "Hi World"
```

This can be useful for dependency injection, or just to avoid repeating the language domain name
for every call. Note that an optional second argument for the language code will give you a translation
function for a specific language. Also note that language files will not actually load until the
translation function is used - so there are no unpredictable performance implications.

Before the language domain `"foo/bar"` can be translated, you need to register the base path of the
translation files for the language domain - for example:

```php
lang::register("foo/bar", __DIR__ . "/lang");
```

Now, if you call `lang::text("foo/bar", "...")`, the translation file `{__DIR__}/lang/en.php` will
be loaded - the file name is the two-letter language code. If you call `lang::text("foo/bar/baz", "...")`,
the translation file `{__DIR__}/lang/baz/en.php` will load, and so on.

A language file such as `lang/baz/en.php` might look like this:

```php
<?php

return [
    'Hello, {world}' => 'Greetings, {world}',
    '{num} results'  => function ($num) { return $num != 1 ? "{$num} results" : "{$num} result"; }
];
```

This example demonstrates the two forms of translation - using plain strings with curly-braced
placeholders for replaceable tokens, or using anonymous functions; in the latter case, tokens
are mapped to matching argument names, which enables you to implement language-specific logic
for things like plurality.

Refer to the [inline documentation](src/lang.php) for additional details. 
