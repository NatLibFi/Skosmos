function setLangCookie(lang) {
  // The cookie path should be relative if the baseHref is known
  let cookiePath = '/'
  if (window.SKOSMOS.baseHref && window.SKOSMOS.baseHref.replace(window.origin, '')) {
    cookiePath = window.SKOSMOS.baseHref.replace(window.origin, '')
  }
  document.cookie = `SKOSMOS_LANGUAGE=${lang};path=${cookiePath}`
}
function addLanguageEventListeners() {
  const languageLinks = document.querySelectorAll('.nav-item.language a');
  languageLinks.forEach(function(link) {
    link.addEventListener('click', function(event) {
      const langValue = this.getAttribute('id').substring(9) //strip the 'language-' part of the id
      setLangCookie(langValue)
    })
  })
}
// register the language link event listeners
document.addEventListener('DOMContentLoaded', addLanguageEventListeners)
