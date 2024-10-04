export async function loadLocaleMessages(locale) {
    const messages = {};
    try {
      const response = await fetch(`http://localhost/skosmos/resource/translations/messages.${locale}.json`);
      const data = await response.json();
      console.log(`Haettiin data: ${locale}:`, data);
      messages[locale] = data;
    } catch (error) {
      console.error('käännösten lataamisessa tapahtui virhe:', error);
    }
    return messages;
}

export async function createI18nInstance(locale = 'en', componentName = 'Unknown Component') {
    const messages = await loadLocaleMessages(locale);
    const i18n = VueI18n.createI18n({
      locale: locale,
      fallbackLocale: 'en', // fallback-kieli
      messages
    });

    console.log('Luotiin i18n:', i18n);
    return i18n;
}
