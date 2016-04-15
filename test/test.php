<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use mindplay\lang;

setup(function () {
    lang::reset();
});

test(
    "can apply missing translations",
    function () {
        eq(lang::text("foo/bar", "Hello, {world}", ["world" => "World"]), "Hello, World");

        eq(lang::text("foo/bar", "{num} results", ["num" => 5]), "5 results");
    }
);

test(
    "can report missing files and translations",
    function () {
        $errors = [];

        lang::$on_error = function ($message) use (&$errors) {
            $errors[] = $message;
        };

        lang::text("foo/bar", "Hello");

        eq($errors, [
            "no translation file found for: foo/bar/en",
            "missing translation of 'Hello' for: foo/bar/en",
        ]);

        lang::$on_error = null;
    }
);

test(
    "can call translation closure",
    function () {
        lang::register("foo", __DIR__ . '/lang/foo');

        eq(lang::text("foo/bar", "{num} results", ["num" => 1]), "1 result");
        eq(lang::text("foo/bar", "{num} results", ["num" => 5]), "5 results");
    }
);

test(
    "can find translation for specific language file",
    function () {
        lang::register("foo/bar/en", __DIR__ . '/lang/foo/bar/en');

        eq(lang::text("foo/bar", "Hello, {world}", ["world" => "World"]), "Greetings, World");

        eq(lang::text("foo/bar", "{num} results", ["num" => 5]), "5 results");
    }
);

test(
    "can find translation for language in folder",
    function () {
        lang::register("foo/bar", __DIR__ . '/lang/foo/bar');

        eq(lang::text("foo/bar", "Hello, {world}", ["world" => "World"]), "Greetings, World");
    }
);

test(
    "can find translation for language in sub-folder",
    function () {
        lang::register("foo", __DIR__ . '/lang/foo');

        eq(lang::text("foo/bar", "Hello, {world}", ["world" => "World"]), "Greetings, World");
    }
);

test(
    "can switch languages",
    function () {
        lang::register("foo", __DIR__ . '/lang/foo');

        eq(lang::text("foo/bar", "Hello, {world}", ["world" => "World"]), "Greetings, World");

        lang::set("da");

        eq(lang::text("foo/bar", "Hello, {world}", ["world" => "World"]), "Hej, World");
    }
);

test(
    "can use domain closures",
    function () {
        lang::register("foo", __DIR__ . '/lang/foo');

        $domain = lang::domain("foo/bar");

        eq($domain("Hello, {world}", ["world" => "World"]), "Greetings, World");

        lang::set("da");

        eq($domain("Hello, {world}", ["world" => "World"]), "Hej, World");
    }
);

test(
    "can use domain closures with language code",
    function () {
        lang::register("foo", __DIR__ . '/lang/foo');

        $domain = lang::domain("foo/bar", "da");

        eq($domain("Hello, {world}", ["world" => "World"]), "Hej, World", "disregards current language code");
    }
);

exit(run());
