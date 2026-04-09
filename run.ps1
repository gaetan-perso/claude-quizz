<#
.SYNOPSIS
    Equivalents des commandes Makefile pour Windows PowerShell
.EXAMPLE
    .\run.ps1 setup
    .\run.ps1 up
    .\run.ps1 shell
    .\run.ps1 artisan "route:list"
#>

param(
    [Parameter(Position=0)] [string]$Command = "help",
    [Parameter(Position=1)] [string]$Arg = ""
)

$DC  = "docker compose"
$PHP = "docker compose exec app php"

function Invoke-Help {
    Write-Host ""
    Write-Host "  Commandes disponibles :" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  .\run.ps1 setup          Premier demarrage (build + migrate + seed)" -ForegroundColor Green
    Write-Host "  .\run.ps1 up             Demarre tous les services"
    Write-Host "  .\run.ps1 up-dev         Demarre avec Vite (hot reload)"
    Write-Host "  .\run.ps1 down           Arrete tous les services"
    Write-Host "  .\run.ps1 build          Rebuild les images Docker"
    Write-Host "  .\run.ps1 restart [svc]  Redemarre un service"
    Write-Host "  .\run.ps1 ps             Liste les services"
    Write-Host "  .\run.ps1 logs [svc]     Affiche les logs"
    Write-Host ""
    Write-Host "  .\run.ps1 shell          Shell dans le conteneur app"
    Write-Host "  .\run.ps1 migrate        Lance les migrations"
    Write-Host "  .\run.ps1 seed           Lance les seeders"
    Write-Host "  .\run.ps1 fresh          Recrée la BDD + seed"
    Write-Host "  .\run.ps1 tinker         Lance Laravel Tinker"
    Write-Host "  .\run.ps1 artisan [cmd]  Lance une commande artisan"
    Write-Host ""
    Write-Host "  .\run.ps1 test           Lance les tests"
    Write-Host "  .\run.ps1 npm-build      Build les assets (production)"
    Write-Host ""
}

function Invoke-Setup {
    if (-not (Test-Path ".env")) {
        Copy-Item ".env.docker" ".env"
        Write-Host "  .env cree depuis .env.docker" -ForegroundColor Yellow
    }
    Invoke-Expression "$DC build"
    Invoke-Expression "$DC up -d"
    Write-Host "  Attente demarrage MySQL..." -ForegroundColor Yellow
    Start-Sleep -Seconds 8
    Invoke-Expression "$PHP artisan key:generate"
    Invoke-Expression "$PHP artisan migrate --seed"
    $portMatch = Get-Content .env | Select-String "^APP_PORT=(.+)"
    $port = if ($portMatch) { $portMatch.Matches.Groups[1].Value } else { "8000" }
    Write-Host ""
    Write-Host "  Application disponible sur http://localhost:$port" -ForegroundColor Green
}

switch ($Command) {
    "help"      { Invoke-Help }
    "setup"     { Invoke-Setup }
    "up"        { Invoke-Expression "$DC up -d" }
    "up-dev"    { Invoke-Expression "$DC --profile dev up -d" }
    "down"      { Invoke-Expression "$DC down" }
    "build"     { Invoke-Expression "$DC build --no-cache" }
    "restart"   { Invoke-Expression "$DC restart $Arg" }
    "ps"        { Invoke-Expression "$DC ps" }
    "logs"      { Invoke-Expression "$DC logs -f $Arg" }
    "shell"     { Invoke-Expression "docker compose exec app bash" }
    "migrate"   { Invoke-Expression "$PHP artisan migrate" }
    "seed"      { Invoke-Expression "$PHP artisan db:seed" }
    "fresh"     { Invoke-Expression "$PHP artisan migrate:fresh --seed" }
    "tinker"    { Invoke-Expression "docker compose exec app php artisan tinker" }
    "artisan"   { Invoke-Expression "$PHP artisan $Arg" }
    "test"      { Invoke-Expression "docker compose exec app ./vendor/bin/pest" }
    "npm-build" { Invoke-Expression "docker compose run --rm vite npm run build" }
    default     { Write-Host "  Commande inconnue: $Command" -ForegroundColor Red; Invoke-Help }
}
