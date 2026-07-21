..  include:: /Includes.rst.txt

..  _bugfix-15-typo3-v14-frontend-request-attributes:

===================================================
Bugfix: #15 - TYPO3 v14 frontend request attributes
===================================================

Description
===========

The TYPO3 v14 frontend environment builder did not create three of the
request attributes that the TYPO3 core `PrepareTypoScriptFrontendRendering`
middleware sets up, which the builder stands in for:

*   `frontend.response.data`
*   `frontend.register.stack`
*   `frontend.page.parts`

On TYPO3 v13 the corresponding state lived on the
:php:`TypoScriptFrontendController`, which the TYPO3 v13 builder bootstraps, so
only TYPO3 v14 was affected.

The attributes are not inert. The :php:`ContentObjectRenderer` created by the
builder reads :php:`frontend.register.stack` for `register:` data, the
`LOAD_REGISTER` and `RESTORE_REGISTER` content objects, menus and `FILES`. With
the attribute missing, a content object operation in the built environment
raised a :php:`\TypeError`. The Extbase bootstrap likewise reads
:php:`frontend.page.parts` and :php:`frontend.response.data`.

Impact
======

The three request attributes are now created when building a TYPO3 v14 frontend
environment, so content object and Extbase operations run in the built
environment as they do in a regular TYPO3 v14 frontend request.

This fixes a regression introduced with the TYPO3 v14 support in version
`2.0.0`.
