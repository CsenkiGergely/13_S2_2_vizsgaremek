describe('Home oldal', () => {
  it('Keresés gomb működik', () => {
    cy.visit('http://localhost:5173/')
    cy.get('[data-cy="search-btn"]').click()
  })
})