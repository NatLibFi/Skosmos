function truncateSearchResults() {
  const results = document.querySelectorAll('.search-result .list-group .list-group-item');
  results.forEach((result) => {
    result.style.whiteSpace = 'nowrap';
    result.style.position = 'relative';
    const containerWidth = result.offsetWidth;
    const actualWidth = result.scrollWidth;

    if (actualWidth > containerWidth) {
      result.style.overflow = 'hidden';

      const link = document.createElement('a');
      link.style.position = 'absolute';
      link.style.right = '0';
      link.style.padding = '0';
      link.textContent = '... (' + countResultValues(result) + ')';
      const backgroundColor = getComputedStyle(result).backgroundColor;
      link.style.backgroundColor = backgroundColor
      link.onclick = () => showAllResults(result);
      result.appendChild(link);
    }
  });
}

function showAllResults(result) {
  result.style.whiteSpace = 'normal';
  result.style.overflow = 'visible';
  const link = result.querySelector('a');
  if (link) {
    result.removeChild(link);
  }
}

function countResultValues(result) {
  return 5; // Placeholder
}

// Event listeners when page is initially loaded, and when the window is resized
document.addEventListener('DOMContentLoaded', truncateSearchResults);
window.addEventListener('resize', truncateSearchResults);
