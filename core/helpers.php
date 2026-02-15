<?php

use Core\Database;

// Carica variabili d'ambiente dal file .env
function loadEnv(?string $path = null): void {
    static $loaded = false;
    
    if ($loaded) {
        return;
    }
    
    // Cerca prima nella root del progetto, poi in config
    if ($path) {
        $envFile = $path;
    } elseif (file_exists(__DIR__ . '/../config/.env')) {
        $envFile = __DIR__ . '/../config/.env';
    } else {
        $envFile = __DIR__ . '/../config/.env';
    }
    
    if (!file_exists($envFile)) {
        return;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora commenti
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Rimuovi spazi bianchi e separa chiave e valore
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Rimuovi commenti inline
            if (strpos($value, '#') !== false) {
                $value = trim(explode('#', $value)[0]);
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    $loaded = true;
}

// Ottiene una variabile d'ambiente
function env(string $key, $default = null) {
    loadEnv();
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Escape output per prevenire XSS
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Shortcut per echo + escape
function ee($value) {
    echo e($value);
}

// Carica la configurazione API
function loadApiConfig(): array {
    $apiConfigPath = __DIR__ . '/../config/api_config.json';
    
    if (!file_exists($apiConfigPath)) {
        return [];
    }
    
    return json_decode(file_get_contents($apiConfigPath), true) ?? [];
}

// Salva la configurazione API
function saveApiConfig(array $config): bool {
    $apiConfigPath = __DIR__ . '/../config/api_config.json';
    return file_put_contents(
        $apiConfigPath, 
        json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ) !== false;
}

// Genera la struttura completa del database in JSON
function generateDatabaseStructure($db, $tables): void {
    try {
        $databaseName = $db->getDatabaseName();
        $apiConfig = loadApiConfig();
        
        // Se il database non esiste ancora nel config, crealo
        if (!isset($apiConfig[$databaseName])) {
            $apiConfig[$databaseName] = [];
        }
        
        // Per ogni tabella del database corrente, aggiungi/aggiorna la configurazione
        foreach ($tables as $table) {
            if (!isset($apiConfig[$databaseName][$table])) {
                // Inizializza le tabelle che non hanno ancora una configurazione
                $apiConfig[$databaseName][$table] = [
                    'enabled' => false
                ];
            }
        }
        
        // Aggiungi le viste personalizzate come tabelle virtuali
        if (isset($apiConfig[$databaseName]['_views'])) {
            foreach ($apiConfig[$databaseName]['_views'] as $viewName => $viewConfig) {
                if (!isset($apiConfig[$databaseName]['_view_' . $viewName])) {
                    $apiConfig[$databaseName]['_view_' . $viewName] = [
                        'enabled' => false,
                        'is_virtual' => true,
                        'view_name' => $viewName
                    ];
                }
            }
        }
        
        saveApiConfig($apiConfig);
        
    } catch (\Exception $e) {
        error_log("Failed to update database structure: " . $e->getMessage());
    }
}

// Funzioni per gestire il Base URL

/**
 * Ottiene il base URL configurato (es: "" per localhost, "/cartella/progetto" per Altervista)
 */
function getBaseUrl(): string {
    static $baseUrl = null;
    
    if ($baseUrl === null) {
        $baseUrl = rtrim(env('BASE_URL', ''), '/');
    }
    
    return $baseUrl;
}

/**
 * Genera un URL completo con il base URL
 */
function url(string $path): string {
    $baseUrl = getBaseUrl();
    $path = '/' . ltrim($path, '/');
    return $baseUrl . $path;
}

/**
 * Rimuove il base URL da un URI
 */
function removeBaseUrl(string $uri): string {
    $baseUrl = getBaseUrl();
    
    if (empty($baseUrl)) {
        return $uri;
    }
    
    // Rimuovi il base URL dall'inizio dell'URI
    if (strpos($uri, $baseUrl) === 0) {
        $uri = substr($uri, strlen($baseUrl));
    }
    
    return $uri ?: '/';
}

// Aggiungi qui le funzioni che ti servono per il progetto 
