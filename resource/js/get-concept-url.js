/* global SKOSMOS */

/* eslint-disable no-unused-vars */
const getConceptURL = (uri) => {
  const clangParam = (SKOSMOS.content_lang !== SKOSMOS.lang) ? 'clang=' + SKOSMOS.content_lang : ''
  let clangSeparator = '?'
  let page = ''

  if (uri.indexOf(SKOSMOS.uriSpace) !== -1) {
    page = uri.substr(SKOSMOS.uriSpace.length)

    if (/[^a-zA-Z0-9-_.~]/.test(page) || page.indexOf('/') > -1) {
      // contains special characters or contains an additional '/' - fall back to full URI
      page = '?uri=' + encodeURIComponent(uri)
      clangSeparator = '&'
    }
  } else {
    // not within URI space - fall back to full URI
    page = '?uri=' + encodeURIComponent(uri)
    clangSeparator = '&'
  }

  return SKOSMOS.vocab + '/' + SKOSMOS.lang + '/page/' + page + (clangParam !== '' ? clangSeparator + clangParam : '')
}
/* eslint-disable no-unused-vars */
