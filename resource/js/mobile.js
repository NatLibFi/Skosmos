/* global $t, onTranslationReady */

// Register event listener for clicking on sidebar collapse button that toggles visibility of sidebar

function initializeSidebarCollapse () {
  const sidebarCollapseBtn = document.getElementById('sidebar-collapse-btn')

  if (sidebarCollapseBtn) {
    sidebarCollapseBtn.addEventListener('click', () => {
      // Toggle sidebar visibility
      const sidebar = document.getElementById('sidebar-col')
      sidebar.classList.toggle('d-none')

      // Toggle fontawesome icon
      const i = document.querySelector('#sidebar-collapse-btn i')
      i.classList.toggle('fa-chevron-right')
      i.classList.toggle('fa-chevron-down')

      // Toggle button text and aria expanded
      sidebarCollapseBtn.getElementsByTagName('span')[0].textContent = sidebar.classList.contains('d-none') ? $t('Browse concepts') : $t('Hide concepts')
      sidebarCollapseBtn.setAttribute('aria-expanded', sidebarCollapseBtn.getAttribute('aria-expanded') === 'true' ? 'false' : 'true')

      // Custom event for sidebar visibility
      sidebarCollapseBtn.dispatchEvent(new CustomEvent('sidebar-toggled'))
    })
  }
}

onTranslationReady(initializeSidebarCollapse)
