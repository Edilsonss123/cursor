# Proposta: adicionar testes automatizados

## Objetivo

Adicionar testes automatizados nos endpoints da aplicação para validar regras de negócio, respostas da API e reduzir riscos de regressão.

---

# Estrutura atual

A aplicação já possui separação por camadas (`Controller`, `Service`, `Repository` e `DB`), facilitando a implementação dos testes.

---

# Escopo

Cobrir os principais cenários:

- Fluxos de sucesso
- Validação de payload
- Tratamento de erros
- Regras de negócio
- Retornos HTTP

---

# Abordagem

- Utilização de PHPUnit
- Organização dos testes por camada
- Uso de SQLite para cenários controlados

---

# Resultado esperado

Maior segurança nas alterações futuras, redução de falhas em produção e melhoria na manutenção da aplicação.