# DORA in-page PHP code

## Introduction

[PHP](https://secure.php.net) code of pages we set up directly in [Drupal](https://www.drupal.org)'s admin environment of our [Islandora](https://islandora.ca)-based institutional repository [DORA](https://www.dora.lib4ri.ch).

## CAVEAT

THIS IS PART WORK IN PROGRESS, PART OUTDATED WORK!!! THE IMPLEMENTATIONS ARE CRUDE AND WERE DONE IN A HURRY. IN PARTICULAR, WE DID NOT ALWAYS CODE THE FEATURES IN THE CORRECT WAY. DUE TO TIME CONSTRAINTS, THIS EVALUATION CODE WILL BE USED IN PRODUCTION, BUT WE HOPE TO UPDATE IT AT SOME POINT. YOU SHOULD PROBABLY NOT USE THIS CODE YOURSELF, AS IT MIGHT NOT WORK FOR YOU OR EVEN BREAK YOUR SYSTEM (SEE ALSO 'LICENSE'). UNDER NO CIRCUMSTANCES WHATSOEVER ARE WE TO BE HELD LIABLE FOR ANYTHING. YOU HAVE BEEN WARNED.

## Installation

1. Log into [DORA](https://www.dora.lib4ri.ch) and create a new _Basic page_.
2. Choose a title
3. Paste the entire content of the `php` file of your choice into the _Body_ field, making sure the _Text format_ is set to _PHP code_.
4. Choose an appropriate _URL alias_
5. (<i>@OPTIONAL</i>) Adapt other settings
6. _Save_

## Files

### `README.md`

This file...

### `LICENSE`

The license under which the code is distributed:
```
Copyright (c) 2017 d-r-p (Lib4RI) <d-r-p@users.noreply.github.com>

Permission to use, copy, modify, and distribute this software for any
purpose with or without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
```

### `.gitignore`

This is set up to include only the files described in this section:
```
/*
!/.gitignore
!/README.md
!/LICENSE
!/migration-progress.php
!/migration-progress.conf
!/author-list.php
```

### `migration-progress.php` (@unmaintained)

This page displays progress bars per publication type regarding the amount of migrated objects from the source (RefWorks/FileMaker) into [DORA](https://www.dora.lib4ri.ch). It shows bi-coloured bars, in dependence of the correction status of the publication.

The logic hinges on manually entering the total per publication type (although it has a crude auto-complete mechanism), as well as using certain markers to indicate correction (we (mis!)use the department descriptor field for this purpose). The initial part of the file contains an area that should be modified in this regard to contain the source and correctors information relevant for the current subsite (see the file `migration-progress.conf` for our latest configurations).

The code has several shortcomings (besides style). Chiefly among them is a buggy mechanism to ignore certain correction markers (since it matches substrings). Also, the part that counts publications should probably be revisited, as it might not cope too well once the migration is finished and new publications are ingested (at least the autocompletion seems to fail).

Note: We actually do not use this code any longer. We post it for inspiration and reference. If we should need it again and find the time, we will probably update it. Do not hold your breath, though.

### `migration-progress.conf` (@unmaintained)

This file contains the latest setup for the three subsites [DORA Eawag](https://www.dora.lib4ri.ch/eawag), [DORA Empa](https://www.dora.lib4ri.ch/empa) and [DORA WSL](https://www.dora.lib4ri.ch/wsl). You can replace the generic data in `migration-progress.php` between the markers `/***** EDIT THIS PART ONLY *****/` and `/*******************************/` with the section of interest.

### `author-list.php`

This page allows the user to browse through the (alphabetically ordered) list of affiliated authors who have publications in [DORA](https://www.dora.lib4ri.ch). It groups the authors by their name's starting letter and has a (slightly faulty) pager to limit the result set. In addition, it has a very simplistic search field.

The page is, actually, operated through the url (via query). The starting letter can be specified using `letter=...` (it defaults to `letter=A`), and a search can be triggered using `find=...`. Moreover, the query has two "hidden" features:
* Specifying `letter=*` will show _all_ authors in alphabetical order
* Adding `showall` or setting `showall` to `true` or any non-zero integer will show affiliated authors, even if they currently have no publications in [DORA](https://www.dora.lib4ri.ch).

## @TODO

* Modify the mechanism for excluding certain markers in `migration-progress.php`
* Review the counting and autocomplete mechanism in `migration-progress.php`
* Correct the pager in `author-list.php`
* Re-implement `author-list.php` as an external module using proper templates/themes

<br/>
> _This document is Copyright &copy; 2017 by d-r-p (Lib4RI) `<d-r-p@users.noreply.github.com>` and licensed under [CC&nbsp;BY&nbsp;4.0](https://creativecommons.org/licenses/by/4.0/)._


