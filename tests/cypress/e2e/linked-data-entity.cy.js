describe('HTTP content negotiation for linked data', () => {
  it('Default entity representation: HTML', () => {
    // visit the URI for "archaeology" concept via the entity controller
    cy.request('/entity?uri=http://www.yso.fi/onto/yso/p1265').then((response) => {
      // Check that the Content-Type header contains 'text/html'
      expect(response.headers['content-type']).to.include('text/html');
      // Check that the response body is not empty
      expect(response.body).to.not.be.empty;
    })
  });
  it('Negotiated entity representation: Turtle', () => {
    // visit the URI for "archaeology" concept via the entity controller
    cy.request({
      url: '/entity?uri=http://www.yso.fi/onto/yso/p1265',
      headers: {
        Accept: 'text/turtle'
      }
    }).then((response) => {
      // Check that the Content-Type header contains 'text/turtle'
      expect(response.headers['content-type']).to.include('text/turtle');
      // Check that the response body is not empty
      expect(response.body).to.not.be.empty;
    })
  });
  it('Negotiated entity representation: JSON-LD', () => {
    // visit the URI for "archaeology" concept via the entity controller
    cy.request({
      url: '/entity?uri=http://www.yso.fi/onto/yso/p1265',
      headers: {
        Accept: 'application/ld+json'
      }
    }).then((response) => {
      // Check that the Content-Type header contains 'application/ld+json'
      expect(response.headers['content-type']).to.include('application/ld+json');
      // Check that the response body is not empty
      expect(response.body).to.not.be.empty;
    })
  });
  it('Negotiated entity representation: RDF/XML', () => {
    // visit the URI for "archaeology" concept via the entity controller
    cy.request({
      url: '/entity?uri=http://www.yso.fi/onto/yso/p1265',
      headers: {
        Accept: 'application/rdf+xml'
      }
    }).then((response) => {
      // Check that the Content-Type header contains 'application/rdf+xml'
      expect(response.headers['content-type']).to.include('application/rdf+xml');
      // Check that the response body is not empty
      expect(response.body).to.not.be.empty;
    })
  });
});
