# Desafio: sair do PHP legado para um código organizado

Temos um sistema antigo de cadastro de usuários feito com Xajax em `exemplos/desafio/app_xajax`.

O objetivo não é “modernizar aos poucos”.  
É entender como ele funciona e criar uma nova versão com o mesmo comportamento, mas com um código mais organizado, fácil de manter e menos acoplado.

---

# O que vocês precisam fazer

Criar um novo módulo de usuários mantendo:

- login
- permissões
- listagem
- cadastro
- edição
- visualização
- regras de acesso

Mas sem repetir os problemas do legado.

---

# O que o sistema novo deve ter

- Organização em camadas
- PSR-4 + Composer
- Backend separado da interface
- Permissões centralizadas no backend
- SQL com prepared statements
- Código legível e fácil de evoluir
- Sem Xajax

---

# O que deve continuar funcionando

## Perfis

- ADMIN
- SUPERVISOR
- OPERADOR
- VISUALIZADOR

## Funcionalidades

- Login com sessão
- Listagem de usuários
- Filtro por nome/e-mail
- Tela única para:
  - criar
  - editar
  - visualizar
- Controle de permissões por perfil