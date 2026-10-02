function truncateSearchResults () {
  const results = document.querySelectorAll('.search-result .list-group .list-group-item')
  results.forEach((result) => {
    setRowClass(result, 'search-result-list')
    const valueSpan = result.querySelector('.search-result-propval, .search-result-uri')
    const containerWidth = valueSpan ? valueSpan.clientWidth : result.clientWidth
    const actualWidth = valueSpan ? valueSpan.scrollWidth : result.scrollWidth

    if (actualWidth > containerWidth) {
      setRowClass(result, 'search-result-hidden')
      // if the element does not have a show all -link, add one
      const lastElement = result.lastElementChild
      if (!lastElement || lastElement.tagName !== 'A') {
        const link = document.createElement('A')
        link.textContent = renderShowAllText(result)
        link.setAttribute('class', 'p-0 ps-4 search-result-hide')

        link.onclick = () => showAllResults(result)
        result.appendChild(link)
      }
    } else {
      removeShowAll(result)
    }
  })
}

function setRowClass (element, cls) {
  element.classList.remove('search-result-list', 'search-result-hidden', 'search-result-showall')
  element.classList.add(cls)
}

function removeShowAll (element) {
  const lastElement = element.lastElementChild
  if (lastElement && lastElement.tagName === 'A') {
    element.removeChild(lastElement)
  }
}

function showAllResults (element) {
  setRowClass(element, 'search-result-showall')
  const link = element.querySelector('A')
  if (link) {
    element.removeChild(link)
  }
}

function renderShowAllText (element) {
  const propval = element.querySelector('.search-result-propval')
  const text = propval ? propval.textContent : element.textContent
  const textArr = text.split(',')
  return '... (' + textArr.length + ')'
}

// Event listeners when page is initially loaded, and when the window is resized
document.addEventListener('DOMContentLoaded', truncateSearchResults)
window.addEventListener('resize', truncateSearchResults)
