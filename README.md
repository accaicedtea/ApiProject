# SerioApi

***Questo è un link alle [Api online]([https://www.google.com](https://accaicedtea.altervista.org/apiSerio/serioApi/)).***

**Generatore automatico di API REST in PHP da qualsiasi database MySQL/MariaDB.**

---

## Stack

| Layer | Tecnologia |
|-------|-----------|
| Backend | PHP 8.1+, PDO |
| Frontend | Bootstrap 5, Font Awesome 6 |
| Auth | JWT custom (HMAC-SHA256) |
| Sicurezza | CSRF, Rate Limiting, Bcrypt, Query parametrizzate |
| Deploy | FTP integrato (lftp) |

---

## Quick Start

```bash
git clone https://github.com/accaicedtea/serioApi.git
cd serioApi
php -S localhost:8000 index.php
```

Oppure con `server.sh`:

```bash
./server.sh
# > start
```

Al primo avvio → redirect automatico a `/settings` per configurare il database.

---

## Configurazione

### Database

Da interfaccia: **Settings** → inserisci credenziali → Testa → Salva.

Oppure manualmente in `config/database.php`:

```php
'development' => [
    'host' => 'localhost',
    'dbname' => 'nome_db',
    'user' => 'root',
    'pass' => '',
],
```

### Environment (`config/.env`)

```env
ENVIRONMENT=development
BASE_URL=
```

Per hosting su sottocartella (es. Altervista):

```env
ENVIRONMENT=production
BASE_URL=/cartella/progetto
```

---

## Flusso di lavoro

```
Settings → Home → Generator → Builder → Deploy
```

| Pagina | Cosa fa |
|--------|---------|
| **Home** | Panoramica database e tabelle |
| **Generator** | Configura permessi e rate limit per tabella |
| **Views** | Crea viste SQL come endpoint |
| **JWT** | Configura autenticazione e campi custom |
| **Builder** | Genera il progetto API completo |
| **Deploy** | Upload FTP su hosting remoto |
| **Settings** | Credenziali database |

---

## API Generata

Per ogni tabella abilitata:

| Metodo | Endpoint | Auth |
|--------|----------|------|
| `GET` | `/api/{tabella}` | Configurabile |
| `GET` | `/api/{tabella}/{id}` | Configurabile |
| `POST` | `/api/{tabella}` | Configurabile |
| `PUT` | `/api/{tabella}/{id}` | Configurabile |
| `DELETE` | `/api/{tabella}/{id}` | Configurabile |

Livelli di permesso: `all` (pubblico), `auth` (autenticato), `admin`, `owner`.

### Autenticazione

```
POST /api/auth/login
{ "email": "admin@example.com", "password": "admin123" }

→ { "data": { "token": "eyJ...", "user": { ... } } }
```

```
GET /api/auth/me
Authorization: Bearer <token>
```

---

## Struttura

```
serioApi/
├── app/
│   ├── Builder/          # Template per file generati
│   ├── Controllers/      # HomeController, GeneratorController, ApiBuilderController, FtpDeployController
│   └── Views/            # Viste Bootstrap 5
│       ├── components/   # Header, Navbar, Footer
│       ├── home/         # index, dbcon, nocon, about, settings
│       ├── generator/    # Config tabelle, viste, JWT, builder
│       └── deploy/       # Interfaccia FTP
├── config/
│   ├── .env              # Variabili d'ambiente
│   ├── database.php      # Credenziali DB
│   ├── api_config.json   # Tabelle e permessi (auto-generato)
│   └── routes.php        # Routing
├── core/
│   ├── App.php           # Bootstrap applicazione
│   ├── Controller.php    # Classe base MVC
│   ├── Database.php      # Connessione PDO
│   ├── ErrorHandler.php  # Gestione errori centralizzata
│   ├── Route.php         # Router
│   ├── Security.php      # CSRF
│   ├── FtpNew.php        # Client FTP
│   ├── helpers.php       # Funzioni globali
│   └── errors/           # Pagine errore (404, 500)
├── generated-api/        # Output: API generata
├── index.php             # Entry point
└── server.sh             # Script gestione server + test + deploy
```

---

## Sicurezza

- Query parametrizzate (PDO) contro SQL Injection
- `htmlspecialchars()` contro XSS
- Token CSRF su tutti i form
- Rate limiting per IP configurabile
- JWT con scadenza
- Password hashate con Bcrypt

---

## Troubleshooting

| Problema | Soluzione |
|----------|----------|
| Apache non parte (porta 80 occupata) | [Guida AskUbuntu](https://askubuntu.com/questions/615761/xampp-starting-apache-fail) |
| Connection refused | Controlla porta: `lsof -i :8080` |
| JWT token invalid | Token scaduto → rifai login |
| API restituisce 404 | Attiva mod_rewrite: `sudo a2enmod rewrite` |
| Pagina bianca su Altervista | Verifica `BASE_URL` nel `.env` e permessi file (644/755) |
| Deploy FTP fallisce | Installa lftp: `sudo apt install lftp` |
