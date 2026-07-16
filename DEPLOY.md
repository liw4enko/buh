# Деплой на Beget через GitHub (авто-деплой при push)

Схема: правишь код локально → `git push` в ветку `main` → GitHub Actions
собирает Astro и заливает готовый `dist/` на Beget по SSH. Через ~40 секунд
сайт обновлён. Ручная заливка файлов больше не нужна.

```
Локально (правки) ──push──▶ GitHub (main) ──Actions: npm run build──▶ rsync dist/ ──▶ Beget (public_html)
```

---

## Шаг 1. Привязать проект к существующему репозиторию

В папке проекта (`buhsite/`):

```bash
git remote add origin <URL_ТВОЕГО_РЕПО>      # напр. git@github.com:user/buhgalter-orenburg.git
git branch -M main
git push -u origin main
```

Если репозиторий уже с историей (есть README/коммиты) и push отклонён:

```bash
git pull origin main --allow-unrelated-histories   # свести истории
git push -u origin main
# либо, если содержимое репо не нужно: git push -u origin main --force
```

## Шаг 2. Ключ SSH для деплоя

Генерим отдельную пару ключей **для CI** (без пароля):

```bash
ssh-keygen -t ed25519 -C "github-deploy-beget" -f beget_deploy -N ""
```

Появятся `beget_deploy` (приватный) и `beget_deploy.pub` (публичный).

- **Публичный** ключ (`beget_deploy.pub`) добавить на Beget:
  Панель Beget → раздел **SSH** (или «Доступ» → SSH-ключи) → добавить содержимое `.pub`.
  Убедись, что SSH-доступ у аккаунта включён (для прошлого проекта он уже был включён).
- **Приватный** ключ (`beget_deploy`, весь текст вместе с `BEGIN/END`) пойдёт в секрет GitHub (шаг 3).

## Шаг 3. Секреты в GitHub

Репозиторий → **Settings → Secrets and variables → Actions → New repository secret**.
Создать пять секретов:

| Секрет               | Что вписать                                   | Пример |
|----------------------|-----------------------------------------------|--------|
| `BEGET_SSH_HOST`     | SSH-хост Beget                                | `login.beget.tech` |
| `BEGET_SSH_USER`     | Логин аккаунта Beget                          | `login` |
| `BEGET_SSH_PORT`     | Порт SSH (обычно 22)                          | `22` |
| `BEGET_SSH_KEY`      | Приватный ключ `beget_deploy` целиком         | `-----BEGIN OPENSSH PRIVATE KEY----- …` |
| `BEGET_TARGET_PATH`  | Путь к папке сайта на Beget                   | `/home/l/login/имя-домена.ru/public_html` |

> Точные `HOST`, `USER` и `TARGET_PATH` — те же, что использовал в проекте
> «медработники». Путь можно подсмотреть в панели Beget (Файловый менеджер)
> или по SSH командой `pwd` внутри папки `public_html` нужного домена.

## Шаг 4. Проверка

```bash
git commit --allow-empty -m "trigger deploy"
git push
```

Вкладка **Actions** в репозитории → workflow «Build & Deploy to Beget» должен
пройти зелёным. Открой домен — увидишь свежую сборку.

---

## Как редактировать сайт дальше

Весь контент — в `src/data/site.ts` (текст, цены, контакты, FAQ). Правишь →
`git commit` → `git push` → деплой сам. Для превью локально: `npm run dev`.

## Тонкости именно для Beget

- **`--delete` включён**: то, что удалил из `dist/`, удалится и на сервере.
  Если в `public_html` лежит что-то постороннее (старый сайт, `.htaccess`) —
  либо перенеси нужное в проект (`public/`), либо временно убери `--delete`
  из `.github/workflows/deploy.yml`.
- **Домен уже привязан к `public_html`** в панели Beget — Actions только кладёт
  туда файлы, DNS/домен настраивается один раз в панели.
- **HTTPS**: включи бесплатный Let's Encrypt в панели Beget для домена.
- Если SSH-ключ по какой-то причине не заводится, есть запасной путь — деплой
  по FTP (Beget его поддерживает) через `SamKirkland/FTP-Deploy-Action`.
  Скажи — переключу workflow на FTP.
