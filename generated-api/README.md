# API REST — acca_totem

Generato il 15/02/2026 12:41 — **18 tabelle**, **7 viste**

---

## Setup

### Requisiti server
- PHP 7.4+, MySQL 5.7+, Apache con mod_rewrite
- Estensioni PHP: `pdo`, `pdo_mysql`, `json`

### Installazione

1. Carica `generated-api/` sul server via FTP o dal builder
2. Verifica credenziali in `config/database.php`
3. Verifica che `.htaccess` sia presente nella root
4. Testa: `curl https://ftp.accaicedtea.altervista.org/mymenu/api/auth/login`

---

## Base URL

```
https://ftp.accaicedtea.altervista.org/mymenu/api/
```

Tutti gli endpoint hanno prefisso `/api/`. Esempio completo:
```
https://ftp.accaicedtea.altervista.org/mymenu/api/allergens
https://ftp.accaicedtea.altervista.org/mymenu/api/auth/login
https://ftp.accaicedtea.altervista.org/mymenu/api/views/v_products_full
```

---

## Autenticazione (JWT)

### POST `/api/auth/login`

```json
// Request body
{ "email": "admin@example.com", "password": "admin123" }
```

```json
// Response 200
{
  "status": 200,
  "data": {
    "token": "eyJ0eXAiOiJKV1Q...",
    "user": { "id": 1, "email": "admin@example.com", "role": "admin" }
  },
  "message": "Login effettuato con successo"
}
```

```json
// Response 401
{ "error": true, "message": "Credenziali non valide", "status": 401 }
```

### GET `/api/auth/me`

```
Authorization: Bearer <token>
```
Restituisce i dati dell'utente autenticato. Token valido **24 ore**.

---

## Endpoint

Ogni tabella abilitata espone 5 operazioni CRUD.

### Riepilogo permessi

| Tabella | GET | POST | PUT | DELETE | Rate Limit |
|---------|-----|------|-----|--------|------------|
| `allergens` | auth | admin | admin | admin | 100/60s |
| `audit_allergens` | auth | admin | admin | admin | 100/60s |
| `audit_categories` | auth | admin | admin | admin | 100/60s |
| `audit_ingredients` | auth | admin | admin | admin | 100/60s |
| `audit_products` | auth | admin | admin | admin | 100/60s |
| `audit_promotions` | auth | admin | admin | admin | 100/60s |
| `categories` | auth | admin | admin | admin | 100/60s |
| `ingredient_allergens` | auth | admin | admin | admin | 100/60s |
| `ingredients` | auth | admin | admin | admin | 100/60s |
| `product_allergens` | auth | admin | admin | admin | 100/60s |
| `product_ingredients` | auth | admin | admin | admin | 100/60s |
| `product_promotions` | auth | admin | admin | admin | 100/60s |
| `products` | auth | admin | auth | admin | 100/60s |
| `promotions` | auth | admin | admin | admin | 100/60s |
| `_view_v_products_full` | all | none | none | none | 1000/60s |
| `_view_v_categories_with_count` | all | none | none | none | 1000/60s |
| `_view_v_ingredients_with_allergens` | all | none | none | none | 100/60s |
| `_view_v_products_with_promotions` | all | none | none | none | 100/60s |

**Legenda:** `all` = pubblico, `auth` = token richiesto, `admin` = ruolo admin, `owner` = proprietario

### Schema richieste

| Metodo | URL | Body | Descrizione |
|--------|-----|------|-------------|
| GET | `/api/{tabella}` | — | Lista tutti |
| GET | `/api/{tabella}/{id}` | — | Singolo per ID |
| POST | `/api/{tabella}` | JSON | Crea nuovo |
| PUT | `/api/{tabella}/{id}` | JSON | Aggiorna |
| DELETE | `/api/{tabella}/{id}` | — | Elimina |

### Viste (sola lettura)

Le viste sono accessibili sia come `/api/_view_{nome}` che come `/api/views/{nome}`.

| Vista | Descrizione | Auth |
|-------|-------------|------|
| `v_products_full` | Tutti i prodotti con categoria, allergeni e ingredienti | auth |
| `v_categories_with_count` | Categorie con conteggio prodotti per azienda | auth |
| `v_ingredients_with_allergens` | Ingredienti con la lista degli allergeni associati per azienda | auth |
| `v_allergens_usage` | Allergeni con conteggio utilizzo in prodotti e ingredienti | all |
| `v_azienda_stats` | Statistiche complete per azienda | all |
| `v_products_with_promotions` | Prodotti con promozioni attive e prezzo scontato | auth |
| `v_active_promotions` | Promozioni attive con conteggio prodotti | all |

---

## Formato risposte

### Successo
```json
{ "status": 200, "data": [...], "timestamp": 1234567890 }
```

### Errore
```json
{ "error": true, "message": "Descrizione errore", "status": 400, "timestamp": 1234567890 }
```

### Codici HTTP

| Codice | Significato |
|--------|-------------|
| 200 | OK |
| 201 | Creato |
| 400 | Dati mancanti o non validi |
| 401 | Token mancante o scaduto |
| 403 | Accesso diretto da browser bloccato / Permesso negato |
| 404 | Risorsa non trovata |
| 405 | Metodo non consentito |
| 429 | Rate limit superato |
| 500 | Errore server |

> **Nota:** L'accesso diretto da browser (barra URL) è bloccato. Usare un client API (fetch, axios, curl) con gli header corretti.

---

## Integrazione Vue.js / Vite

### 1. Proxy Vite (sviluppo locale)

In `vite.config.js`:
```javascript
export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'https://ftp.accaicedtea.altervista.org/mymenu',
        changeOrigin: true,
        secure: true,
        rewrite: (path) => path
      }
    }
  }
})
```

### 2. Servizio API (`src/services/api.js`)

```javascript
// api.js — Servizio API pronto all'uso

const BASE_URL = import.meta.env.DEV
  ? '/api/'
  : 'https://ftp.accaicedtea.altervista.org/mymenu/api/'

const getHeaders = () => {
  const headers = { 'Content-Type': 'application/json' }
  const token = localStorage.getItem('token')
  if (token) headers['Authorization'] = `Bearer ${token}`
  return headers
}

const handleResponse = async (response) => {
  const text = await response.text()
  let result
  try {
    result = JSON.parse(text)
  } catch {
    throw new Error('Risposta non JSON: ' + text.substring(0, 200))
  }
  if (!response.ok) throw new Error(result.message || `Errore ${response.status}`)
  return result
}

// === AUTH ===
export const login = async (email, password) => {
  const res = await fetch(`${BASE_URL}auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  })
  const result = await handleResponse(res)
  localStorage.setItem('token', result.data.token)
  localStorage.setItem('user', JSON.stringify(result.data.user))
  return result.data
}

export const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}

export const getUser = () => {
  const user = localStorage.getItem('user')
  return user ? JSON.parse(user) : null
}

export const isAuthenticated = () => !!localStorage.getItem('token')

// === CRUD GENERICO ===
export const apiGet = async (endpoint) => {
  const res = await fetch(`${BASE_URL}${endpoint}`, { headers: getHeaders() })
  return handleResponse(res)
}

export const apiPost = async (endpoint, data) => {
  const res = await fetch(`${BASE_URL}${endpoint}`, {
    method: 'POST', headers: getHeaders(), body: JSON.stringify(data)
  })
  return handleResponse(res)
}

export const apiPut = async (endpoint, data) => {
  const res = await fetch(`${BASE_URL}${endpoint}`, {
    method: 'PUT', headers: getHeaders(), body: JSON.stringify(data)
  })
  return handleResponse(res)
}

export const apiDelete = async (endpoint) => {
  const res = await fetch(`${BASE_URL}${endpoint}`, {
    method: 'DELETE', headers: getHeaders()
  })
  return handleResponse(res)
}
```

### 3. Uso nei componenti Vue

```vue
<script setup>
import { ref, onMounted } from 'vue'
import { apiGet, login } from '@/services/api'

const items = ref([])
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  try {
    // Login (una volta, salva il token in localStorage)
    // await login('admin@example.com', 'admin123')

    // Fetch dati (il token viene incluso automaticamente)
    const result = await apiGet('allergens')
    items.value = result.data
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
})
</script>
```

### 4. Endpoint rapidi (copia-incolla)

```javascript
// === TABELLE ===
await apiGet('allergens')         // richiede token
await apiGet('audit_allergens')         // richiede token
await apiGet('audit_categories')         // richiede token
await apiGet('audit_ingredients')         // richiede token
await apiGet('audit_products')         // richiede token
await apiGet('audit_promotions')         // richiede token
await apiGet('categories')         // richiede token
await apiGet('ingredient_allergens')         // richiede token
await apiGet('ingredients')         // richiede token
await apiGet('product_allergens')         // richiede token
await apiGet('product_ingredients')         // richiede token
await apiGet('product_promotions')         // richiede token
await apiGet('products')         // richiede token
await apiGet('promotions')         // richiede token
await apiGet('_view_v_products_full')         // pubblico
await apiGet('_view_v_categories_with_count')         // pubblico
await apiGet('_view_v_ingredients_with_allergens')         // pubblico
await apiGet('_view_v_products_with_promotions')         // pubblico

// === VISTE (sola lettura) ===
await apiGet('views/v_products_full')  // richiede token
await apiGet('views/v_categories_with_count')  // richiede token
await apiGet('views/v_ingredients_with_allergens')  // richiede token
await apiGet('views/v_allergens_usage')  // pubblico
await apiGet('views/v_azienda_stats')  // pubblico
await apiGet('views/v_products_with_promotions')  // richiede token
await apiGet('views/v_active_promotions')  // pubblico

// === CRUD ===
await apiGet('allergens')          // lista
await apiGet('allergens/1')         // singolo
await apiPost('allergens', { ... }) // crea
await apiPut('allergens/1', { ... }) // aggiorna
await apiDelete('allergens/1')       // elimina
```

---

## cURL

```bash
# Login
curl -X POST https://ftp.accaicedtea.altervista.org/mymenu/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"admin123"}'

# GET con token
curl https://ftp.accaicedtea.altervista.org/mymenu/api/allergens \
  -H 'Authorization: Bearer <token>'
```

---

## Sicurezza

| Protezione | Implementazione |
|------------|----------------|
| SQL Injection | PDO Prepared Statements |
| XSS | htmlspecialchars() |
| Accesso browser | Bloccato (richiede header API) |
| Rate Limiting | Per IP, configurabile per endpoint |
| JWT | Scadenza configurabile (default 24h) |
| Password | Bcrypt hash |
| Error Handling | Centralizzato, log su file, JSON-only |

---

## Struttura

```
├── .htaccess            # Routing Apache
├── index.php            # Router PHP built-in server
├── cors.php             # CORS headers
├── config/
│   ├── database.php     # Credenziali DB + classe Database
│   ├── helpers.php      # sendResponse() + blockBrowserAccess()
│   ├── jwt.php          # JWTHandler
│   └── api_config.json  # Permessi tabelle
├── middleware/
│   ├── auth.php         # requireAuth() / validateToken()
│   ├── security.php     # Rate limiting + security headers
│   ├── security_helper.php # applySecurity()
│   └── error_handler.php   # Gestione errori centralizzata
├── models/              # Un file per ogni tabella/vista
├── endpoints/           # CRUD per ogni tabella/vista
├── auth/me.php          # GET /api/auth/me
└── logs/                # Error log (protetto da .htaccess)
```

---

## Troubleshooting

| Problema | Soluzione |
|----------|----------|
| 404 su Altervista | Verifica `.htaccess` e `RewriteBase` |
| CORS bloccato | Controlla `cors.php` e header Apache |
| Token invalid | Token scaduto → rifai login |
| 403 da browser | Normale: usa fetch/axios, non la barra URL |
| 500 senza dettagli | Controlla `logs/errors.log` |
| Response non JSON | Errore PHP fatale → controlla logs server |
