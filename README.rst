Automatic cache flush for TYPO3
===============================

This extension adds the required bits to enable cache flushes
not implemented by the TYPO3 core.

It hooks into frontend rendering and backend data handling – this is to add cache
tags while rendering which will be flushed by the backend data handling hooks.

Currently supported features:

- Menu flush for page-related operations: add, remove, rename, hide, time-based (un)publish
- Subtree flushing when pages.media is changed (for the usecase where header images are inherited using levelmedia)

Configuration
-------------

For the basic functionality no configuration is required. Just install the extension and
stop using the "flush frontend caches" button whenever you rename, delete, hide or add a page.

.. code-block:: bash

    typo3/cli_dispath.phpsh extbase extension:install autoflush

Cache flush for time-based page publishing
------------------------------------------

There's an extbase command controller which should be run from a cronjob
(directly or indirectly through the scheduler).
The command controller will clear the cache for all pages that render
references (menus) to pages that are published by time.

.. code-block:: bash

    typo3/cli_dispath.phpsh extbase autoflush:clearmenuforpulishedpages

The command will flush references for new/expired pages since the last
and the current run of the command. Therefore you should run this command
frequently to reduce the time where menu's are out-of-date.


TODO
----

- File handling (add, move, delete, edit metadata) – you may use EXT:cacheopt for now
- Category based menus
- create menu_pid\_ tags for pages that could eventually render a submenu (but do not have child pages yet)
