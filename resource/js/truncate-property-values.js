/* global $t, onTranslationReady */

function truncatePropertyValues () {
  const maxValues = 15 // hide extras if there are more values than this
  const propVals = document.querySelectorAll('.property-value > ul, #concept-other-languages ul')
  propVals.forEach((propVal) => { // one ul within div.property-value
    const listItems = propVal.querySelectorAll('li:not(:has(ul, ol))') // the li's inside the ul and inside sub ul/ol

    if (propVal.querySelector('a.property-value-show') === null && listItems.length > maxValues) {
      // hide the items after the first maxValues
      let truncatedListItems = [];
      for (let i = maxValues; i < listItems.length; ++i) {
        listItems[i].classList.add('property-value-hidden')
        if (listItems[i].classList.contains('rdf-list-truncated')) {
          truncatedListItems.push(listItems[i])
        }
      }
      // add a link for showing all items
      const showItem = document.createElement('li')
      const showLink = document.createElement('a')
      showLink.href = '#'
      showLink.textContent = '[' + $t('show all # values').replace('#', listItems.length - truncatedListItems.length) + ']'
      showLink.classList.add('property-value-show')

      showLink.addEventListener('click', function (event) {
        event.preventDefault()
        propVal.classList.add('property-value-expand')
        event.target.closest('li').remove()
      })

      showItem.appendChild(showLink)
      propVal.appendChild(showItem)
    }
  })
}

onTranslationReady(truncatePropertyValues)
document.addEventListener('loadConceptPage', truncatePropertyValues)
