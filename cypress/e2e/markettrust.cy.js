
describe('Login Form Tests', () => {
  it('should fill in the email and password', () => {
    cy.visit('http://127.0.0.1:8000/login')

    cy.get('input[name="email"]')
      .type('test@example.com')

    cy.get('input[name="password"]')
      .type('ExamplePassword123!')

    cy.get('input[name="email"]')
      .should('have.value', 'test@example.com')

    cy.get('input[name="password"]')
      .should('have.value', 'ExamplePassword123!')
  })
})