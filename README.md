drupal_parse_composer
=====================

Parses drupal update xml and makes composer json using the .info file(s) and composer.json to represent dependencies and submodules.

Installation
============

Paradoxically, this thing depends on drupal, and until I have time to put the generated json on a server somewhere, you will need to copy the `repositories` property out of the composer.json in this project and put it in the composer.json of your project. I also haven't put this on packagist yet, so you will need to add this repository to the repositories in your composer.json.

This should be fixed-ish soon, to the point that you really do just require it like any other project.

Use
===

after install, you can do something like this:

`vendor/bin/drupal-packages views features panels strongarm`

and you will get composer package json for the drupal 7 versions of all those, as well as drupal core itself.

You can specify versions like so:

`vendor/bin/drupal-packages views:6,7,8 features:6,7 panels strongarm`

Note that `project:7` is equivalent to `project`. Also, right now we don't do any recovery if the update xml isn't found in the normal place, so it will just crash if one of the projects doesn't have releases for the given versions.

Now What?
=========

Now take the resulting packages.json file and put it in a web root somewhere or just reference it with a file url in your composer.json for a sweet new drupal project:

```js
{
...
  "repositories": [
    {
      "type": "composer",
      "url": "file:///path/to/dir/with/packages.json"
    }
  ]
...
}
```

or like this:

```js
{
...
  "repositories": [
    {
      "type": "composer",
      "url": "http://www.example.com"
    }
  ]
...
}
```

and then require some drupal projects like a bad ass:

```js
{
...
  "require": {
    "drupal/views": "7.*",
    "drupal/features": "7.2.*"
  }
...
}
````
