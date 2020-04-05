# Migrate Scraper

This library is built to have a list of sites that are scraped and saved into JSON files. The intent is to use the JSON files for a migration into a CMS.

This library is meant to be general and for use with any project that wants to migrate HTML into JSON. However some elements are custom to the original use case of Orange County.

## Installation

``composer install``

## Configuration

### sites.yml file

The ``sites.yml`` file lists the sites that will be scraped as well as the tags in the HTML to be used for fields, link to output and input files and optional transforms and overrides.

#### Fields

```yml

site_key: # id of the site
  url: http://example.com
  file: # link to a file with list of URLs
  transform: # optional link to transform.yml file
  override: # optional link to override class
  news: # optiona list of dynamic news pages
  filter: # optional list of pages to ignore
  fields: # list of fields and html elements for domCrawler to select from.
    title:
      - h1.title
    body:
      - .body
      - article

```

## Menu Configuration

The menu is currently a separate migration. The menu scraper needs the following fields:

```yml

  rootMenu: http://www.ocpl.org/navdata/rootmenu49.xml
  topMenu:

```

The ``rootMenu`` is the location of the xml menu document published by Civica. That needs to be downloaded and placed into the ``oc/sites/[site_id]/rootmenu.xl`` document. It needs to be cleaned up to work. The first line needs to be removed and the back-slashes need to be removed. A TODO: is to automate that.

The ``topMenu`` is the top level menu of the site and the ID for each menu item. These ids can be found in the ``rootmenu.xml`` file. TODO: pull these out automatically.

### tranform.yml file

File with a list of pages keyed by their id and a list transforms to override default discovery. Current transforms include:

#### tag

Specificy the tag to use for an individual field.

#### override

Specify the text to override a field.

An example that overrides the title of a page with "Custom Title" with the id of ``21312891``:

```yml

21312891:
  field: title
  type: override
  text: Custom Title

```

### remove.yml file

List of files to filter by id or url.

### news.yml file

List news or press release pages. These are list pages like a view. Each link in the list is treated like page.

## Content Demo

``composer install; php oc/run.php ocpl 96125273``

You should see a file ``oc/sites/ocpl/data/96125273.json`` that has scraped "http://www.ocpl.org/libloc/aliso" and added a custom type and title from ``oc/sites/ocpl/transform.yml``.

## Menu Demo

``composer install; php oc/menu.php ocgov``

You should see a file ``oc/sites/ocpl/data/menu.json``. that has scraped "http://www.ocpl.org/libloc/aliso" and added a custom type and title from ``oc/sites/ocgov/transform.yml``.

