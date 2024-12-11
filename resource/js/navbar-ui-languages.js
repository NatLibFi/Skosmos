function setLangCookie(lang) {
  // The cookie path should be relative if the baseHref is known
  let cookiePath = '/'
  if (window.SKOSMOS.baseHref && window.SKOSMOS.baseHref.replace(window.origin, '')) {
    cookiePath = window.SKOSMOS.baseHref.replace(window.origin, '')
  }
  document.cookie = `SKOSMOS_LANGUAGE=${lang};path=${cookiePath}`
  console.log('KEKSI asetettu parametrille ' + lang)
}
function addLanguageEventListeners() {
  const languageLinks = document.querySelectorAll('.nav-item.language a');
  languageLinks.forEach(function(link) {
    link.addEventListener('click', function(event) {
      event.preventDefault()
      setLangCookie(this.getAttribute('href'))
    })
  })
}

// register the language link event listeners
document.addEventListener('DOMContentLoaded', addLanguageEventListeners)
