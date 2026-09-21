# Torrent Translate

Portal de traduções do SerrebiTorrent mantido pelo CIATA.

## Fluxo

O SerrebiTorrent mantém o catálogo canônico em `locales/serrebitorrent.pot`. O Torrent Translate sincroniza esse catálogo, permite tradução e revisão, valida a integridade das entradas e publica os artefatos por Pull Request.

```text
SerrebiTorrent main
→ POT canônico
→ Torrent Translate
→ tradução
→ revisão/aprovação
→ PO + JSON Web + index.json
→ Pull Request
→ merge manual
```

## Desenvolvimento

O projeto é Laravel. O checkout versionado deve conter o esqueleto completo da aplicação e permitir instalação/teste a partir do repositório sem depender de arquivos presentes apenas no servidor.

Nunca versionar `.env`, credenciais, tokens, `vendor/`, `node_modules/`, logs, caches ou outros artefatos de runtime.

### Comandos operacionais

```bash
php artisan translations:sync-github
php artisan translations:sync-pot
php artisan translations:publish pt-BR
```

A publicação exige catálogo integralmente aprovado e usa a integração GitHub configurada no ambiente.

## Papéis

- tradutor: envia traduções para revisão;
- revisor: aprova, rejeita ou corrige traduções;
- administrador: possui as permissões de revisor e também sincroniza/publica catálogos e consulta auditoria.

Administradores e revisores são configurados por ambiente.

## Acessibilidade

O portal segue o CIATA Design System como fonte canônica. Entre os requisitos adotados estão HTML semântico, navegação por teclado, foco visível, labels persistentes, erros associados aos campos, feedback por região live, campos readonly quando o conteúdo precisa continuar consultável e controles de mostrar/ocultar senha com nome e estado acessíveis.

Referência: https://github.com/CIATA-BR/CIATA-DS

## Produção

Após atualizar código que não contém migration:

```bash
git pull
php artisan optimize:clear
```

Quando houver migrations novas:

```bash
git pull
php artisan migrate --force
php artisan optimize:clear
```

## Estado antes do merge inicial

A PR inicial só deve ser mergeada quando o repositório contiver também o esqueleto Laravel usado em produção, incluindo `composer.json`, `artisan`, `bootstrap/app.php`, `public/index.php` e demais arquivos necessários para uma instalação reproduzível. Depois disso, o projeto deve receber CI e testes automatizados dos fluxos críticos.
