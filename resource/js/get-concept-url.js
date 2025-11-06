/* eslint-disable no-unused-vars */
const getConceptURL = (uri) => {
  const clangParam = (window.SKOSMOS.content_lang !== window.SKOSMOS.lang) ? 'clang=' + window.SKOSMOS.content_lang : ''
  let clangSeparator = '?'
  let page = ''

  if (uri.indexOf(window.SKOSMOS.uriSpace) !== -1 && uri !== window.SKOSMOS.uriSpace) {
    page = uri.substr(window.SKOSMOS.uriSpace.length)

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

  return window.SKOSMOS.vocab + '/' + window.SKOSMOS.lang + '/page/' + page + (clangParam !== '' ? clangSeparator + clangParam : '')
}
/* eslint-disable no-unused-vars */
