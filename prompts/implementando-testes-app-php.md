# Testes — app-php

Índice dos prompts. Use na ordem ou só a camada desejada.

| Ordem | Arquivo | Quando usar |
|-------|---------|-------------|
| 0 | [app-php-testes/contexto.md](app-php-testes/contexto.md) | Contexto compartilhado (ler uma vez) |
| 1 | [app-php-testes/setup.md](app-php-testes/setup.md) | PHPUnit, pastas, `composer test` |
| 2 | [app-php-testes/unitarios.md](app-php-testes/unitarios.md) | Value Objects + `validateCPF` |
| 3 | [app-php-testes/integracao.md](app-php-testes/integracao.md) | `PeopleService` + fakes |
| 4 | [app-php-testes/feature.md](app-php-testes/feature.md) | Endpoints HTTP + SQLite isolado |

**Projeto:** `exemplos/app-php` · **App:** `exemplos/app-php/app`

## Comando rápido (suíte completa)

> Leia `prompts/app-php-testes/contexto.md` e implemente na ordem: setup → unitários → integração → feature. Rode `composer test` até passar.

## Cenário perfeito

Antes de codar, liste o plano (1 linha por passo). Implemente uma camada por vez.
