# Lancer le projet avec Docker

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et **démarré**
- PowerShell (Windows)

> Vérifie que Docker Desktop est bien lancé (icône baleine dans la barre des tâches) avant toute commande.

---

## Premier démarrage

```powershell
.\run.ps1 setup
```

Cette commande fait tout en une fois :
1. Copie `.env.docker` vers `.env`
2. Build les images Docker
3. Démarre tous les services
4. Génère la clé d'application Laravel
5. Lance les migrations et les seeders

L'application est ensuite disponible sur **http://localhost:8000**.

---

## Commandes du quotidien

### Cycle de vie

| Commande | Description |
|----------|-------------|
| `.\run.ps1 up` | Démarre tous les services |
| `.\run.ps1 up-dev` | Démarre avec le serveur Vite (hot reload frontend) |
| `.\run.ps1 down` | Arrête tous les services |
| `.\run.ps1 build` | Rebuild les images Docker (après modif du Dockerfile) |
| `.\run.ps1 restart app` | Redémarre un service spécifique |
| `.\run.ps1 ps` | Liste les services en cours |
| `.\run.ps1 logs` | Affiche tous les logs en temps réel |
| `.\run.ps1 logs app` | Logs d'un service spécifique (`app`, `nginx`, `mysql`…) |

### Base de données

| Commande | Description |
|----------|-------------|
| `.\run.ps1 migrate` | Lance les migrations |
| `.\run.ps1 seed` | Lance les seeders |
| `.\run.ps1 fresh` | Recrée la BDD from scratch + seed |

### Laravel

| Commande | Description |
|----------|-------------|
| `.\run.ps1 shell` | Ouvre un shell bash dans le conteneur |
| `.\run.ps1 tinker` | Lance Laravel Tinker (REPL) |
| `.\run.ps1 artisan "route:list"` | Lance n'importe quelle commande Artisan |
| `.\run.ps1 test` | Lance la suite de tests (Pest) |
| `.\run.ps1 npm-build` | Build les assets CSS/JS pour la production |

---

## Services Docker

| Service | URL / Port | Rôle |
|---------|-----------|------|
| Laravel (Nginx) | http://localhost:8000 | Application principale |
| MySQL | localhost:3306 | Base de données |
| Redis | localhost:6379 | Cache, sessions, queues |
| Reverb (WebSocket) | localhost:8080 | Temps réel multijoueur |
| Vite | localhost:5173 | Dev server frontend (profil `dev` uniquement) |

---

## Configuration

Le fichier `.env.docker` contient les valeurs par défaut pour Docker.  
Lors du premier `.\run.ps1 setup`, il est copié vers `.env`.

Pour modifier la configuration (ports, clés API…), édite directement `.env` puis relance :

```powershell
.\run.ps1 restart app
```

Pour ajouter la clé Anthropic (génération de questions IA) :

```
ANTHROPIC_API_KEY=sk-ant-...
```

---

## Aide

```powershell
.\run.ps1 help
```
