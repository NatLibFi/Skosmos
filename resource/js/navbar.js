function setLangCookie (lang) {
  // The cookie path should be relative if the baseHref is known
  let cookiePath = '/'
  if (window.SKOSMOS.baseHref && window.SKOSMOS.baseHref.replace(window.origin, '')) {
    cookiePath = window.SKOSMOS.baseHref.replace(window.origin, '')
  }
  const path = '; path=' + cookiePath

  const date = new Date()
  const msPerDay = 24 * 60 * 60 * 1000
  const days = 365
  date.setTime(date.getTime() + days * msPerDay)
  const expires = '; expires=' + date.toGMTString()

  const langValue = 'SKOSMOS_LANGUAGE=' + lang

  document.cookie = langValue + expires + path + '; SameSite=Lax'
}
function addLanguageEventListeners () {
  const languageLinks = document.querySelectorAll('.nav-item.language a')
  languageLinks.forEach(function (link) {
    link.addEventListener('click', function (event) {
      const langValue = this.getAttribute('id').substring(9) // strip the 'language-' part of the id
      setLangCookie(langValue)
    })
  })
}
// register the language link event listeners
document.addEventListener('DOMContentLoaded', addLanguageEventListeners)
