/* global $t:writable, onTranslationReady:writable */
/* exported $t, onTranslationReady */

(async function () {
  const translationCallbacks = []

  async function loadLocaleMessages (locale) {
    const messages = {}
    try {
      const response = await fetch(`resource/translations/messages.${locale}.json`)
      const data = await response.json()
      messages[locale] = data
    } catch (error) {
      console.error('Loading error:', error)
    }
    return messages
  }

  async function initializeTranslations (locale) {
    const messages = await loadLocaleMessages(locale)
    const translations = messages[locale] || {}
    $t = function (key) {
      return translations[key] || key
    }
    translationCallbacks.forEach(callback => callback())
  }

  onTranslationReady = function (callback) {
    translationCallbacks.push(callback)
  }

  await initializeTranslations(window.SKOSMOS.lang || 'en')
})()
