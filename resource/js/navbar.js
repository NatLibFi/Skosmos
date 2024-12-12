function setLangCookie(lang) {
  // The cookie path should be relative if the baseHref is known
  let cookiePath = window.SKOSMOS.baseHref?.replace(window.origin, '') || '/'
  const date = new Date()
  date.setTime(date.getTime() + 365 * 24 * 60 * 60 * 1000) // 365 days from now
  document.cookie = `SKOSMOS_LANGUAGE=${lang}; expires=${date.toGMTString()}; path=${cookiePath}; SameSite=Lax`
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
