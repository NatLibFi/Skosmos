(async function() {
    async function loadLocaleMessages(locale) {
      const messages = {};
      try {
        const response = await fetch(`http://localhost/skosmos/resource/translations/messages.${locale}.json`);
        const data = await response.json();
        console.log(`Käännökset kielelle "${locale}":`, data);
        messages[locale] = data;
      } catch (error) {
        console.error('Latausvirhe:', error);
      }
      return messages;
    }
  
    async function initializeTranslations(locale = 'en') {
      const messages = await loadLocaleMessages(locale);
      translations = messages[locale] || {};
      console.log("Käännökset:", translations);
  
      $t = function(key) {
        const translation = translations[key] || key; // Paras kuitenkin kait tulostaa avain, jos käännöstä ei löydy
        console.log(`Käännnettävänä nyt: "${key}"`, translation);
        return translation; 
      };
    }
  
    await initializeTranslations(window.SKOSMOS.lang || 'fi');
    console.log("Alustus ok ja $t-functio tarjoutuu käyttöön");
  })();

//   Toimiva