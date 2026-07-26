Проект состоит из 4 репозиториев

https://github.com/Nofez/CMS

https://github.com/Nofez/Monitoring

https://github.com/Nofez/Monitoring-Grafana-

https://github.com/Nofez/Monitoring-Victoria-

## Описание архитектуры системы

Система представляет собой комплексную контейнеризированную среду, состоящую из четырёх взаимосвязанных проектов Docker Compose:

1. **Part-of-app** — основное приложение (WordPress + Nginx + MariaDB + PHP)
2. **Monitoring** — стек сбора метрик (Prometheus, Alertmanager, Node Exporter, Blackbox Exporter, MySQL Exporter)
3. **Monitoring-Grafana-** — визуализация метрик (Grafana)
4. **Monitoring-Victoria-** — долгосрочное хранение метрик (VictoriaMetrics)

### Взаимодействие между проектами:

1. **MariaDB** (Part-of-app) подключена к сети `monitor_app`, через неё **MySQL Exporter** (Monitoring) собирает метрики БД.
2. **Prometheus** (Monitoring) подключен к сети `grafana_prometheus` — через неё **Grafana** (Monitoring-Grafana-) читает данные.
3. **Prometheus** (Monitoring) подключен к сети `vm_network` — через неё отправляет метрики в **VictoriaMetrics** (Monitoring-Victoria-) по remote write.
4. **Grafana** (Monitoring-Grafana-) подключена к сети `vm_network` — может читать данные напрямую из VictoriaMetrics.
5. **Все сервисы Part-of-app** работают в изолированной сети `cms_network`.
   
   IdeaProjects/
├── Part-of-app/                          # Основное приложение (WordPress)
│   ├── .env                              # Переменные окружения CMS
│   ├── docker-compose.yml                # Оркестрация CMS-сервисов
│   ├── README.md                         # Документация Part-of-app
│   ├── Mariadb/                          # SQL-скрипты инициализации (пусто)
│   ├── nginx/
│   │   └── nginx.conf                    # Конфигурация Nginx для WordPress
│   ├── php/
│   │   ├── DockerFile                    # Кастомный PHP-fpm-alpine образ
│   │   └── Dockerfile_wp                 # Кастомный WordPress-fpm-alpine образ
│   └── mhw-theme/                        # WordPress-тема Monster Hunter Wilds
│       ├── index.php
│       ├── functions.php
│       └── style.css
│
├── Monitoring/                           # Стек сбора метрик
│   ├── .env                              # Переменные окружения мониторинга
│   ├── docker-compose.yml                # Оркестрация мониторинга
│   ├── DockerFile/
│   │   ├── DockerFile                    # Кастомный Prometheus с envsubst
│   │   └── entrypoint.sh                 # Entrypoint с подстановкой env-переменных
│   ├── prometheus/
│   │   ├── prometheus.yml                # Конфигурация Prometheus (шаблон)
│   │   ├── targets/
│   │   │   ├── node_exporter/
│   │   │   │   └── node_exporter.yml     # Таргеты Node Exporter
│   │   │   ├── mysql_exporter/
│   │   │   │   └── mysql_exporter.yml    # Таргеты MySQL Exporter
│   │   │   └── blackbox/
│   │   │       └── blackbox.yml          # Таргеты Blackbox Exporter
│   │   └── rules/
│   │       ├── node_exporter/
│   │       │   └── node_exporter.yml     # Алерты Node Exporter
│   │       ├── mysql_exporter/
│   │       │   └── mysql_exporter.yml    # Алерты MySQL Exporter
│   │       └── blackbox/
│   │           └── blackbox.yml          # Алерты Blackbox Exporter
│   ├── alertmanager/
│   │   └── alertmanager.yml              # Конфигурация Alertmanager с Telegram
│   └── exporters/
│       └── .my.cnf                       # Креды MySQL для экспортера
│
├── Monitoring-Grafana-/                  # Визуализация метрик
│   ├── .env                              # Переменные окружения Grafana
│   └── docker-compose.yml                # Оркестрация Grafana
│
├── Monitoring-Victoria-/                 # Долгосрочное хранение метрик
	   ├── .env                              # Переменные окружения VictoriaMetrics
	   └── docker-compose.yml                # Оркестрация VictoriaMetrics


## Системные требования

### Необходимое ПО

| Компонент      | Версия (минимальная) | Назначение                               |
| -------------- | -------------------- | ---------------------------------------- |
| Docker Engine  | 24.x+                | Контейнеризация всех сервисов            |
| Docker Compose | 2.20.x+              | Оркестрация многоконтейнерных приложений |
| Git            | 2.x+                 | Управление версиями (опционально)        |
### Требования к хосту

- **ОС**: Linux (рекомендуется) или Windows с WSL2
- **CPU**: минимум 2 ядра (рекомендуется 4)
- **RAM**: минимум 4 GB (рекомендуется 8 GB)
- **Диск**: минимум 20 GB свободного места

## Инструкция по запуску проекта
Проекты нужно запускать в строгом порядке из-за сетевых зависимостей:

1. **Part-of-app** — создаёт сети `cms_network` и `monitor_app`
2. **Monitoring-Victoria-** — создаёт сеть `vm_network`
3. **Monitoring-Grafana-** — использует внешнюю сеть `grafana_prometheus` (создаётся Monitoring)
4. **Monitoring** — использует внешние сети `monitor_app` и `vm_network`, создаёт `grafana_prometheus`

```bash
# 1. Запуск CMS (создаёт cms_network, monitor_app)
cd ../CMS && docker compose up -d

# 2. Запуск VictoriaMetrics (создаёт vm_network)
cd ../Monitoring-Victoria- && docker compose up -d

# 3. Запуск Monitoring (создаёт grafana_prometheus, использует monitor_app и vm_network)
cd ../Monitoring && docker compose up -d

# 4. Запуск Grafana (использует grafana_prometheus и vm_network)
cd ../Monitoring-Grafana- && docker compose up -d
```


### Запуск конкретного проекта
```bash
# Запуск всех сервисов в фоновом режиме
docker compose up -d

# Запуск с пересборкой образов
docker compose up -d --build

# Просмотр логов всех сервисов
docker compose logs -f

# Просмотр логов конкретного сервиса
docker compose logs -f <service_name>
```

### Остановка сервисов
```bash
# Остановка всех сервисов (сохранение данных в volumes)
docker compose down

# Остановка с удалением сетей (данные сохраняются)
docker compose down --remove-orphans
```

### Перезапуск сервисов
```bash
# Пересоздание всех контейнеров
docker compose up -d --force-recreate

# Перезапуск конкретного сервиса
docker compose restart <service_name>

# Пересборка образов и запуск
docker compose up -d
```

### Полезные команды
```bash
# Статус контейнеров
docker compose ps

# Использование ресурсов
docker stats

docker network ls

docker volume ls
```

## Схема и описание используемых сетей
### Определение сетей

| Сеть                 | Проект               | Драйвер | Подключённые сервисы                                                                                                   |
| -------------------- | -------------------- | ------- | ---------------------------------------------------------------------------------------------------------------------- |
| `cms_network`        | Part-of-app          | bridge  | `mariadb`, `php`, `wordpress`, `webserver` (nginx)                                                                     |
| `monitor_app`        | Part-of-app          | bridge  | Создаётся Part-of-app. Используется: `mariadb` (Part-of-app), `prometheus` (Monitoring), `mysql_exporter` (Monitoring) |
| `monitoring`         | Monitoring           | bridge  | `prometheus`, `alertmanager`, `node_exporter`, `blackbox_exporter`, `mysql_exporter`                                   |
| `grafana_prometheus` | Monitoring           | bridge  | Создаётся Monitoring. Используется: `prometheus` (Monitoring), `grafana` (Grafana)                                     |
| `vm_network`         | Monitoring-Victoria- | bridge  | Создаётся Victoria. Используется: `victoria` (Victoria), `prometheus` (Monitoring), `grafana` (Grafana)                |
| `grafana_network`    | Grafana              | bridge  | `grafana` (Grafana)                                                                                                    |
## Список используемых Volumes
### 1. Part-of-app (WordPress CMS)

|Volume|Параметры|Назначение|Привязка к сервисам|
|---|---|---|---|
|`maria_data`|named|Персистентное хранение файлов БД MariaDB|`mariadb`|
|`wp_files`|named|Файлы WordPress (ядро, плагины, темы, загрузки)|`php`, `wordpress`, `nginx`|

### 2. Monitoring (Prometheus стек)

|Volume|Параметры|Назначение|Привязка к сервисам|
|---|---|---|---|
|`prometheus_data`|named|Хранение метрик Prometheus (TSDB)|`prometheus`|
|`alertmanager_data`|named|Хранение состояния алертов (silences, etc.)|`alertmanager`|

### 3. Monitoring-Grafana-

|Volume|Параметры|Назначение|Привязка к сервисам|
|---|---|---|---|
|`grafana_data`|named|Хранение дашбордов, data sources, настроек|`grafana`|

### 4. Monitoring-Victoria-

|Volume|Параметры|Назначение|Привязка к сервисам|
|---|---|---|---|
|`vm_data`|named|Хранение метрик VictoriaMetrics (до 1 года)|`victoria`|
### Bind mount точки монтирования (Part-of-app)

|Хост-путь (bind)|Путь в контейнере|Сервис|Назначение|
|---|---|---|---|
|`./nginx/nginx.conf`|`/etc/nginx/conf.d/default.conf`|webserver|Конфигурация Nginx|
|`./mhw-theme`|`/var/www/html/wp-content/themes/mhw-theme`|wordpress, nginx|Разработка темы (live-reload)|
### Bind mount точки монтирования (Monitoring)

|Хост-путь (bind)|Путь в контейнере|Сервис|Назначение|
|---|---|---|---|
|`./prometheus/prometheus.yml`|`/etc/prometheus/prometheus.yml.template`|prometheus|Шаблон конфигурации Prometheus|
|`./prometheus/targets`|`/etc/prometheus/targets`|prometheus|Таргеты для сбора метрик|
|`./prometheus/rules`|`/etc/prometheus/rules`|prometheus|Правила алертов|
|`./alertmanager/alertmanager.yml`|`/etc/alertmanager/alertmanager.yml`|alertmanager|Конфигурация Alertmanager|
|`./exporters/.my.cnf`|`/.my.cnf`|mysql_exporter|Креды для подключения к БД|

## Описание переменных окружения (.env)

### Part-of-app (`.env`)
```ini
# ─── MariaDB ─────────────────────────────────────────────
DATABASE_IMAGE=mariadb:11.4.2       # Версия MariaDB
DATABASE_NAME=mariadb               # Имя БД
DATABASE_PASS=123                   # Пароль root и пользователя ⚠️
DATABASE_USER=maria_user            # Пользователь БД

# ─── PHP ─────────────────────────────────────────────────
PHP_IMAGE=php:8.4.23-fpm-alpine     # Базовый образ PHP-FPM

# ─── Nginx ───────────────────────────────────────────────
NGINX_IMAGE=nginx:stable-alpine3.23-perl   # Образ Nginx
#NGINX_DOMAIN=mylabkool.pp.ua              # Домен (закомментирован)
NGINX_PORT=8080                      # Альтернативный порт (не используется)
NGINX_PORT_SECOND=80                 # HTTP порт

# ─── WordPress ───────────────────────────────────────────
WORDPRESS_IMAGE=wordpress:fpm-alpine # Базовый образ WordPress (PHP-FPM)
WORDPRESS_PORT=9000                  # Порт PHP-FPM
```

### Monitoring (`.env`)
```ini
# ─── Prometheus ──────────────────────────────────────────
PROMETHEUS_IMAGE=prom/prometheus:v3.4.1    # Версия Prometheus
PROMETHEUS_YML=./prometheus/prometheus.yml  # Шаблон конфигурации
PROMETHEUS_TARGET=./prometheus/targets      # Директория таргетов
PROMETHEUS_RULE=./prometheus/rules          # Директория правил алертов
PROMETHEUS_PORT=9090                        # Порт Prometheus

# ─── Alertmanager ────────────────────────────────────────
ALERT_IMAGE=prom/alertmanager:v0.27.0       # Версия Alertmanager
ALERT_YML=./alertmanager/alertmanager.yml   # Конфигурация
ALERT_PORT=9093                             # Порт Alertmanager

# ─── Node Exporter ───────────────────────────────────────
NODA_IMAGE=prom/node-exporter:v1.8.1        # Версия Node Exporter
NODA_PORT=9100                              # Порт Node Exporter

# ─── Blackbox Exporter ───────────────────────────────────
BLACKBOX_IMAGE=prom/blackbox-exporter:v0.25.0  # Версия Blackbox Exporter

# ─── MySQL Exporter ──────────────────────────────────────
MYSQL_IMAGE=prom/mysqld-exporter:v0.15.1    # Версия MySQL Exporter
MYSQL_NDWORK=./exporters/.my.cnf            # Файл с кредами БД

# ─── Пути к таргетам и правилам ──────────────────────────
BLACKBOX_PATH_TARGET=/etc/prometheus/targets/blackbox/blackbox.yml
MYSQL_PATH_TARGET=/etc/prometheus/targets/mysql_exporter/mysql_exporter.yml
NODA_PATH_TARGET=/etc/prometheus/targets/node_exporter/node_exporter.yml

BLACKBOX_PATH_RULES=/etc/prometheus/rules/blackbox/blackbox.yml
MYSQL_PATH_RULES=/etc/prometheus/rules/mysql_exporter/mysql_exporter.yml
NODA_PATH_RULES=/etc/prometheus/rules/node_exporter/node_exporter.yml

# ─── Параметры для уведомлений и таргетов ────────────────
DOMAIN=mylabkool.pp.ua                   # Домен для Blackbox проверки
DB=mariadb                               # Имя БД для меток MySQL Exporter
```

### Monitoring-Grafana- (`.env`)
```ini
GRAFANA_PORT=3000                        # Порт Grafana
GRAFANA_IMAGE=grafana/grafana:11.6.16    # Версия Grafana
GRAFANA_PASS=123                         # Пароль администратора
```
### Monitoring-Victoria- (`.env`)
```ini
VICTORIA_PORTS=8428                      # Порт VictoriaMetrics
VICTORIA_IMAGE=victoriametrics/victoria-metrics:v1.102.1  # Версия
```

## Используемые сервисы
### Part-of-app (WordPress CMS)

---

#### 1. `mariadb` — База данных

| Параметр        | Значение                                                                    |
| --------------- | --------------------------------------------------------------------------- |
| **Образ**       | `mariadb:11.4.2`                                                            |
| **Container**   | mariadb                                                                     |
| **Restart**     | always                                                                      |
| **Порты**       | Нет внешних (только внутренняя сеть)                                        |
| **Volumes**     | `maria_data:/var/lib/mysql`                                                 |
| **Сети**        | `cms_network`, `monitor_app`                                                |
| **Healthcheck** | `healthcheck.sh --connect --innodb_initialized` (start: 10s, interval: 10s) |
| **Назначение**  | Хранение всех данных WordPress                                              |
#### 2. `php` — Промежуточный PHP-образ (builder)

|Параметр|Значение|
|---|---|
|**Базовый образ**|`php:8.4.23-fpm-alpine`|
|**Dockerfile**|`php/DockerFile`|
|**Container**|php|
|**Restart**|always|
|**Порты**|`9000` (expose, только внутренняя сеть)|
|**Volumes**|`wp_files:/var/www/html`|
|**Сети**|`cms_network`|
|**Healthcheck**|`cgi-fcgi -bind -connect 127.0.0.1:9000` (start: 5s)|
|**Назначение**|Сборка кастомного PHP-образа; передаёт файлы WordPress сервису wordpress|
**Dockerfile:**
```dockerfile
FROM $PHP_IMAGE
RUN apk add --no-cache fcgi
RUN echo "ping.path = /ping" >> /usr/local/etc/php-fpm.d/zz-docker.conf
EXPOSE $WORDPRESS_PORT
```

#### 3. `wordpress` — CMS WordPress

|Параметр|Значение|
|---|---|
|**Базовый образ**|`wordpress:fpm-alpine` (официальный)|
|**Dockerfile**|`php/Dockerfile_wp`|
|**Container**|wordpress|
|**Restart**|always|
|**Порты**|`9000:9000` (внешний доступ)|
|**Volumes**|`wp_files:/var/www/html`, `./mhw-theme:/var/www/html/wp-content/themes/mhw-theme`|
|**Сети**|`cms_network`|
|**Healthcheck**|`cgi-fcgi -bind -connect 127.0.0.1:9000` (start: 60s)|
|**Зависимости**|`php` (condition: service_healthy)|
|**Назначение**|Основной обработчик PHP-скриптов WordPress|
**Dockerfile:**
```dockerfile
FROM $WORDPRESS_IMAGE
RUN apk add --no-cache fcgi
RUN echo "ping.path = /ping" >> /usr/local/etc/php-fpm.d/zz-docker.conf
EXPOSE $WORDPRESS_PORT
```

#### 4. `webserver` — Nginx

|Параметр|Значение|
|---|---|
|**Образ**|`nginx:stable-alpine3.23-perl`|
|**Container**|nginx|
|**Restart**|always|
|**Порты**|`80:80` (HTTP)|
|**Volumes**|`wp_files:/var/www/html`, `./nginx/nginx.conf:/etc/nginx/conf.d/default.conf`, `./mhw-theme:/var/www/html/wp-content/themes/mhw-theme`, `certbot/www`, `certbot/conf`|
|**Сети**|`cms_network`|
|**Healthcheck**|`wget --no-verbose --tries=1 --spider http://127.0.0.1/ping` (start: 10s)|
|**Зависимости**|`wordpress` (condition: service_healthy)|
|**Назначение**|Веб-сервер, терминация SSL, отдача статики, прокси PHP-FPM|

### Monitoring (Стек сбора метрик)
#### 5. `prometheus` — Система сбора метрик

|Параметр|Значение|
|---|---|
|**Образ**|`prom/prometheus:v3.4.1` (кастомный build)|
|**Dockerfile**|`DockerFile/DockerFile`|
|**Container**|prometheus|
|**Restart**|always (нет restart — managed by compose)|
|**Порты**|`9090:9090`|
|**Volumes**|`./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml.template:ro`, `./prometheus/targets:/etc/prometheus/targets:ro`, `./prometheus/rules:/etc/prometheus/rules:ro`, `prometheus_data:/prometheus`|
|**Сети**|`monitoring`, `grafana_prometheus`, `vm_network`, `monitor_app`|
|**Healthcheck**|`wget -qO- http://127.0.0.1:9090/-/healthy` (start: 40s)|
|**Назначение**|Сбор, хранение (15 дней) и обработка метрик со всех экспортеров|

#### 6. `alertmanager` — Управление алертами

| Параметр       | Значение                                                                                                   |
| -------------- | ---------------------------------------------------------------------------------------------------------- |
| **Образ**      | `prom/alertmanager:v0.27.0`                                                                                |
| **Container**  | alertmanager                                                                                               |
| **Restart**    | always                                                                                                     |
| **Порты**      | `9093:9093`                                                                                                |
| **Volumes**    | `./alertmanager/alertmanager.yml:/etc/alertmanager/alertmanager.yml:ro`, `alertmanager_data:/alertmanager` |
| **Сети**       | `monitoring`                                                                                               |
| **Назначение** | Приём алертов из Prometheus, дедупликация, маршрутизация, отправка в Telegram                              |

#### 7. `node_exporter` — Метрики хоста

|Параметр|Значение|
|---|---|
|**Образ**|`prom/node-exporter:v1.8.1`|
|**Container**|node_exporter|
|**Restart**|always|
|**Порты**|`9100:9100`|
|**Volumes**|`/proc:/host/proc:ro`, `/sys:/host/sys:ro`, `/:/rootfs:ro`|
|**Command**|`--path.procfs=/host/proc --path.sysfs=/host/sys --path.rootfs=/rootfs`|
|**Сети**|`monitoring`|
|**Назначение**|Сбор системных метрик хоста (CPU, RAM, Disk, Network)|

#### 8. `blackbox_exporter` — Проверка доступности сайта

| Параметр       | Значение                                                  |
| -------------- | --------------------------------------------------------- |
| **Образ**      | `prom/blackbox-exporter:v0.25.0`                          |
| **Container**  | blackbox-exporter                                         |
| **Restart**    | always                                                    |
| **Порты**      | Нет внешних (только внутренняя сеть)                      |
| **Сети**       | `monitoring`                                              |
| **Назначение** | Прокси-проверка HTTP-доступности сайта (модуль `http_2xx` |

#### 9. `mysql_exporter` — Метрики MariaDB

| Параметр       | Значение                                                       |
| -------------- | -------------------------------------------------------------- |
| **Образ**      | `prom/mysqld-exporter:v0.15.1`                                 |
| **Container**  | mysqld-exporter                                                |
| **Restart**    | always                                                         |
| **Порты**      | Нет внешних (только внутренняя сеть)                           |
| **Volumes**    | `./exporters/.my.cnf:/.my.cnf:ro`                              |
| **Command**    | `--config.my-cnf=/.my.cnf`                                     |
| **Сети**       | `monitoring`, `monitor_app`                                    |
| **Назначение** | Сбор метрик MariaDB (connections, queries, InnoDB, репликация) |

### Monitoring-Grafana- (Визуализация)

#### 10. `grafana` — Панели мониторинга

| Параметр        | Значение                                                  |
| --------------- | --------------------------------------------------------- |
| **Образ**       | `grafana/grafana:11.6.16`                                 |
| **Container**   | grafana                                                   |
| **Restart**     | always                                                    |
| **Порты**       | `3000:3000`                                               |
| **Volumes**     | `grafana_data:/var/lib/grafana`                           |
| **Сети**        | `grafana_network`, `grafana_prometheus`, `vm_network`     |
| **Healthcheck** | `wget -qO- http://localhost:3000/api/health` (start: 30s) |
| **Назначение**  | Визуализация метрик, создание дашбордов                   |
### Monitoring-Victoria- (Долгосрочное хранение)

#### 11. `victoria` — VictoriaMetrics (TSDB)

|Параметр|Значение|
|---|---|
|**Образ**|`victoriametrics/victoria-metrics:v1.102.1`|
|**Container**|victoria|
|**Restart**|always|
|**Порты**|`8428:8428`|
|**Volumes**|`vm_data:/storage`|
|**Сети**|`vm_network`|
|**Healthcheck**|`wget -qO- http://127.0.0.1:8428/health` (start: 15s)|
|**Назначение**|Долгосрочное хранение метрик (retention 1 год)|
## Инструкция по проверке работоспособности

### Базовые проверки Part-of-app (WordPress)

| Что проверяем          | Команда / URL                                                  | Ожидаемый результат        |
| ---------------------- | -------------------------------------------------------------- | -------------------------- |
| Статус контейнеров CMS | `cd Part-of-app && docker compose ps`                          | Все сервисы в статусе `Up` |
| Healthcheck MariaDB    | `docker inspect --format='{{.State.Health.Status}}' mariadb`   | `healthy`                  |
| Healthcheck PHP        | `docker inspect --format='{{.State.Health.Status}}' php`       | `healthy`                  |
| Healthcheck WordPress  | `docker inspect --format='{{.State.Health.Status}}' wordpress` | `healthy`                  |
| Healthcheck Nginx      | `docker inspect --format='{{.State.Health.Status}}' nginx`     | `healthy`                  |
| HTTP-доступность       | `curl -I http://localhost`                                     | HTTP/1.1 200 OK            |
| PHP-FPM ping           | `curl http://localhost/ping`                                   | `pong`                     |
| WordPress API          | `curl -I http://localhost/wp-json/`                            | HTTP/1.1 200 OK            |

## Описание мониторинга
### Список экспортеров

|Экспортер|Сервис|Образ / Версия|Порт|Метрики|
|---|---|---|---|---|
|**Node Exporter**|`node_exporter`|`prom/node-exporter:v1.8.1`|9100|CPU, RAM, Disk, Network, Load Average|
|**MySQL Exporter**|`mysql_exporter`|`prom/mysqld-exporter:v0.15.1`|9104|Connections, Queries, InnoDB, Replication|
|**Blackbox Exporter**|`blackbox_exporter`|`prom/blackbox-exporter:v0.25.0`|9115|HTTP probe: status, duration, SSL, DNS|

### Настроенные правила алертов

#### Node Exporter алерты (`prometheus/rules/node_exporter/node_exporter.yml`)

|Алерт|Условие|Severity|Длительность|Описание|
|---|---|---|---|---|
|HighCPULoad|`100 - (avg by(instance) (rate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 85`|warning|5m|Высокая загрузка CPU > 85%|
|HighMemoryUsage|`(1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) * 100 > 90`|critical|5m|Критическое использование памяти > 90%|
|DiskSpaceLow|`(node_filesystem_avail_bytes / node_filesystem_size_bytes) * 100 < 10`|warning|10m|Мало места на диске < 10%|

#### MySQL Exporter алерты (`prometheus/rules/mysql_exporter/mysql_exporter.yml`)

|Алерт|Условие|Severity|Длительность|Описание|
|---|---|---|---|---|
|MySQLDown|`mysql_up == 0`|critical|1m|MySQL/MariaDB недоступен|
|MySQLTooManyConnections|`mysql_global_status_threads_connected / mysql_global_variables_max_connections * 100 > 80`|warning|5m|Много подключений к БД > 80%|

#### Blackbox алерты (`prometheus/rules/blackbox/blackbox.yml`)

| Алерт       | Условие                              | Severity | Длительность | Описание                            |
| ----------- | ------------------------------------ | -------- | ------------ | ----------------------------------- |
| WebsiteDown | `probe_success{job="blackbox"} == 0` | critical | 2m           | Сайт недоступен (HTTP probe failed) |





