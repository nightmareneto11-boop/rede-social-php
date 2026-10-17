# Rede social PHP (MVP)

PHP 8.3 + MySQL. Cadastro, login, posts, curtir, comentar, seguir e feed. As tabelas são criadas sozinhas na primeira visita.

## Deploy: GitHub + Railway
1. Crie um repositório no GitHub e envie estes arquivos (`git init && git add . && git commit -m init && git push`).
2. Em railway.app: **New Project > Deploy from GitHub repo** e escolha o repositório (ele detecta o Dockerfile).
3. No mesmo projeto: **New > Database > MySQL**.
4. No serviço do app, aba **Variables**, crie (referenciando o MySQL):
   - `MYSQLHOST` = `${{MySQL.MYSQLHOST}}`
   - `MYSQLPORT` = `${{MySQL.MYSQLPORT}}`
   - `MYSQLUSER` = `${{MySQL.MYSQLUSER}}`
   - `MYSQLPASSWORD` = `${{MySQL.MYSQLPASSWORD}}`
   - `MYSQLDATABASE` = `${{MySQL.MYSQLDATABASE}}`
5. Em **Settings > Networking > Generate Domain** para obter o link público.

## Teste local
`docker run -d -p 3306:3306 -e MYSQL_ROOT_PASSWORD=x -e MYSQL_DATABASE=rede mysql:8` e depois
`docker build -t rede . && docker run -p 8080:80 -e MYSQLHOST=host.docker.internal -e MYSQLPASSWORD=x rede`

## Limitações conhecidas
Sem upload de fotos, sem recuperação de senha, sem moderação/denúncia, sem limite de tentativas de login, sem paginação. Adicione antes de abrir ao público.
