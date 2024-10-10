(async function() {
    async function loadLocaleMessages(locale) {
      const messages = {};
      try {
        const response = await fetch(`resource/translations/messages.${locale}.json`);
        const data = await response.json();
        messages[locale] = data;
      } catch (error) {
        console.error('Loading error:', error);
      }
      return messages;
    }
  
    async function initializeTranslations(locale = 'en') {
      const messages = await loadLocaleMessages(locale);
      translations = messages[locale] || {};
      $t = function(key) {
        const translation = translations[key] || key; // Paras kuitenkin kait tulostaa avain, jos käännöstä ei löydy
        return translation; 
      };
    }
  
    await initializeTranslations(window.SKOSMOS.lang || 'fi');
  })();
