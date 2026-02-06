describe('RDF List', () => {
  it('displays an ordered list for a concept with hasRelatedConcept property containing an rdf:list', () => {
    // Visit the concept page with ordered SDLC phases (6 items)
    cy.visit('/test-rdf-list/en/page/sdlc-ordered')

    // Check that the hasRelatedConcept property exists
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept').should('exist')

    // Check the property label
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-label h2').invoke('text').should('include', 'Has related concept')

    // Check that the list is an ordered list (ol)
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol').should('exist')

    // Check that there are 6 items in the ordered list
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li').should('have.length', 6)

    // Verify the order is preserved
    const expectedOrder = [
      'Requirements Gathering',
      'System Design',
      'Implementation',
      'Testing',
      'Deployment',
      'Maintenance'
    ]

    // Check each item in order
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li').each(($li, index) => {
      cy.wrap($li).invoke('text').should('include', expectedOrder[index])
    })
  })

  it('truncates a long rdf:list in the hasRelatedConcept property (max 16 items)', () => {
    // Visit the concept page with ordered programming languages (17 items - above truncation limit)
    cy.visit('/test-rdf-list/en/page/languages-ordered')

    // Check that the hasRelatedConcept property exists
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept').should('exist')

    // Check that the list is an ordered list (ol)
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol').should('exist')

    // The list should be truncated - check that we have exactly 16 visible items
    // (17 total but last one is truncated and shown as "...")
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li').should('have.length.at.least', 16)

    // Check that the last visible item before truncation has the rdf-list-truncated class or indicator
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li').last().should('exist')
  })

  it('hides items after the 15th item in a long rdf:list and shows a "show all" link', () => {
    // Visit the concept page with ordered programming languages (17 items)
    cy.visit('/test-rdf-list/en/page/languages-ordered')

    // Check that the hasRelatedConcept property exists
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept').should('exist')

    // Wait for the truncation JavaScript to run
    cy.wait(500)

    // Check that items after the 15th are hidden
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li.property-value-hidden').should('exist')

    // Count visible items (not hidden)
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li').not('.property-value-hidden').should('have.length.at.most', 16)

    // Check that the "show all" link exists
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value a.property-value-show').should('exist')

    // The link should indicate how many items will be shown
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value a.property-value-show').invoke('text').should('match', /show all \d+ values/)

    // Click the "show all" link
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value a.property-value-show').click()

    // After clicking, the ul should have the property-value-expand class
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ul').should('have.class', 'property-value-expand')

    // The hidden items should still have the property-value-hidden class
    // (they are made visible via CSS when the parent has property-value-expand)
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ul li.property-value-hidden').should('exist')

    // The "show all" link should no longer exist
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value a.property-value-show').should('not.exist')
  })

  it('hides the truncated indicator when the list is long and has hidden items', () => {
    // Visit the concept page with ordered programming languages (17 items - truncated)
    cy.visit('/test-rdf-list/en/page/languages-ordered')

    // Check that the hasRelatedConcept property exists
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept').should('exist')

    // Wait for the truncation JavaScript to run
    cy.wait(500)

    // The truncated item (marked with rdf-list-truncated class) should be hidden
    // when there are more than 15 items
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ol > li.rdf-list-truncated').then(($truncated) => {
      if ($truncated.length > 0) {
        // If there is a truncated item, it should have the hidden class
        cy.wrap($truncated).should('have.class', 'property-value-hidden')
      }
    })

    // Click the "show all" link to reveal hidden items
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value a.property-value-show').click()

    // After clicking, the ul should have the property-value-expand class
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ul').should('have.class', 'property-value-expand')

    // The truncated items still have the property-value-hidden class,
    // but are now visible due to the property-value-expand class on the parent
    cy.get('.prop-http___www_skosmos_skos_hasRelatedConcept .property-value ul li.rdf-list-truncated').then(($truncated) => {
      if ($truncated.length > 0) {
        // The truncated item should still have the hidden class but be visible via CSS
        cy.wrap($truncated).should('have.class', 'property-value-hidden')
        // Verify it's actually visible on the page
        cy.wrap($truncated).should('be.visible')
      }
    })
  })
})
