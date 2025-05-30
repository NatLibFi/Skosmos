function truncateSearchResults () {
  const results = document.querySelectorAll('.search-result .list-group .list-group-item')
  results.forEach((result) => {
    result.setAttribute('class', 'search-result-list')
    const containerWidth = result.offsetWidth
    const actualWidth = result.scrollWidth

    if (actualWidth > containerWidth) {
      result.setAttribute('class', 'search-result-hidden')
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

function removeShowAll (element) {
  const lastElement = element.lastElementChild
  if (lastElement && lastElement.tagName === 'A') {
    element.removeChild(lastElement)
  }
}

function showAllResults (element) {
  element.setAttribute('class', 'search-result-showall')
  const link = element.querySelector('A')
  if (link) {
    element.removeChild(link)
  }
}

function renderShowAllText (element) {
  const textArr = element.textContent.split(',')
  return '... (' + textArr.length + ')'
}

// Event listeners when page is initially loaded, and when the window is resized
document.addEventListener('DOMContentLoaded', truncateSearchResults)
window.addEventListener('resize', truncateSearchResults)
