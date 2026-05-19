prompt ruim

Crie um endpoint em PHP para cadastrar usuário estrutura simples


prompt ideal

Crie um endpoint HTTP em PHP 8.2 para cadastrar usuário seguindo boas práticas modernas.

Requisitos obrigatórios:

- Utilizar Composer para autoload (PSR-4) com namespace App\
- Estrutura mínima:
  - src/Application
  - src/Domain
  - src/Infrastructure
  - public/index.php como entrypoint

Endpoint:
- POST /users

Entrada:
- JSON com:
  - name (string, obrigatório)
  - email (string, obrigatório, formato válido)

Regras:
- Validar entrada e retornar erro 400 se inválido
- Retornar JSON
- Separar responsabilidades:
  - Controller: recebe request/resposta HTTP
  - Repository: persistência

Persistência:
- em memoria

Arquitetura:
- Criar interface UserRepository no Domain
- Implementar InMemoryUserRepository no Infrastructure

Resposta esperada:
- 201 Created com usuário criado
- 400 Bad Request com mensagem de erro


cenario perfeito:
com o prompt ideal criar um plano de execução, economiza token
