/* global $t, onTranslationReady */

let searchResultOffset = window.SKOSMOS.search_results_size

function handleScrollEvent () {
  const searchResultList = document.getElementById('search-results')

  // Only load new search results if the bottom of the result list is visible and no unloaded results remain
  const bottomVisible = searchResultList.getBoundingClientRect().bottom <= window.innerHeight && searchResultList.getBoundingClientRect().bottom >= 0
  if (bottomVisible && searchResultOffset < window.SKOSMOS.search_count) {
    // Disable event listener until more results are loaded
    document.removeEventListener('scroll', handleScrollEvent)

    // Add a spinner to end of result list
    const lastResult = document.querySelector('.search-result:last-of-type')
    lastResult.innerHTML += `<p id="search-loading-spinner">${$t('Loading more items')} <i class="fa-solid fa-spinner fa-spin-pulse"></i></p>`

    // Construct search URL depending on page type
    const params = new URLSearchParams(window.location.search)
    const searchURL = 
      window.SKOSMOS.pageType === 'vocab-search'
      ? `${window.SKOSMOS.vocab}/${window.SKOSMOS.lang}/search?clang=${window.SKOSMOS.content_lang}&q=${params.get('q')}&offset=${searchResultOffset}`
      : `${window.SKOSMOS.lang}/search?clang=${window.SKOSMOS.content_lang}&q=${params.get('q')}&vocabs=${params.get('vocabs')}&offset=${searchResultOffset}`
    fetch(searchURL)
      .then(data => {
        return data.text()
      })
      .then(data => {
        const resultHTML = document.createElement('div')
        resultHTML.innerHTML = data.trim()

        // Append new search results to the list
        const searchResults = resultHTML.querySelector('#search-results').querySelectorAll('.search-result')
        for (const elem of searchResults) {
          searchResultList.append(elem)
        }

        // Remove spinner and increment offset
        lastResult.removeChild(document.getElementById('search-loading-spinner'))
        searchResultOffset += window.SKOSMOS.search_results_size

        // Re-enable event listener after results have been loaded
        document.addEventListener('scroll', handleScrollEvent)

        // If all results have been loaded, display message
        if (searchResultOffset >= window.SKOSMOS.search_count) {
          const newLastResult = document.querySelector('.search-result:last-of-type')

          const translatedMessage = $t('All %d results displayed').replace('%d', window.SKOSMOS.search_count)
          newLastResult.innerHTML += `<p id="search-count">${translatedMessage}</p>`
        }
      })
  }
}

function registerSearchResultEventListener () {
  if (window.SKOSMOS.pageType === 'vocab-search' || window.SKOSMOS.pageType === 'global-search') {
    document.addEventListener('scroll', handleScrollEvent)
  }
}

onTranslationReady(registerSearchResultEventListener)
