function truncateSearchResults () {
  const results = document.querySelectorAll('.search-result .list-group .list-group-item')
  results.forEach((result) => {
    result.style.whiteSpace = 'nowrap'
    result.style.position = 'relative'
    const containerWidth = result.offsetWidth
    const actualWidth = result.scrollWidth

    if (actualWidth > containerWidth) {
      result.style.overflow = 'hidden'
      // if the element does not have a show all -link, add one
      const lastElement = result.lastElementChild
      if (!lastElement || lastElement.tagName !== 'A') {
        const link = document.createElement('A')
        link.style.position = 'absolute'
        link.style.right = '0'
        link.style.padding = '0'
        link.textContent = renderShowAllText(result)
        const backgroundColor = window.getComputedStyle(result).backgroundColor
        link.style.backgroundColor = backgroundColor
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
  element.style.whiteSpace = 'normal'
  element.style.overflow = 'visible'
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
