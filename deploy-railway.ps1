<#
.SYNOPSIS One-click frontend deploy via Railway CLI.
Bypasses Railway UI "can't add 2nd GitHub service from same repo" block.
.EXAMPLE Set-ExecutionPolicy -Scope Process Bypass -Force; .\deploy-railway.ps1
#>
param([string]$BackendDomain = "https://de-prince-computer-hub-production.up.railway.app")
$ErrorActionPreference="Stop"; Set-Location $PSScriptRoot
Write-Host ""; Write-Host "=== DE-PRINCE HUB  Railway Deploy ===" -ForegroundColor Cyan
if (-not (Get-Command railway -ea 0)){ Write-Host "[1/4] Installing Railway CLI..."; npm i -g @railway/cli }
Write-Host ("[1/4] Railway CLI: " + (railway --version)) -ForegroundColor Green
if (-not (railway whoami 2>$null)){ Write-Host "[2/4] Browser login..."; railway login --browser }
Write-Host ("[2/4] Logged in: " + (railway whoami)) -ForegroundColor Green
Write-Host "[3/4] Linking project..." -ForegroundColor Yellow
railway init -n "DE-PRINCE-COMPUTER-HUB" 2>$null; railway link 2>$null
Write-Host ""
Write-Host "[4/4] Deploying frontend (VITE_API_URL=$BackendDomain)..." -ForegroundColor Yellow
Set-Location frontend
railway variables set VITE_API_URL "$BackendDomain" --service frontend 2>$null
railway up --service-name frontend
if ($LASTEXITCODE -ne 0){ throw "Frontend deploy FAILED. Railway dashboard -> Frontend service -> Logs" }
Set-Location $PSScriptRoot
Write-Host ""
Write-Host "========== DEPLOY FINISHED ==========" -ForegroundColor Cyan
Write-Host "Backend  : $BackendDomain/api/ping"
Write-Host "Frontend : Railway dashboard -> Frontend service -> Public Domain"
Write-Host ""
Write-Host "POST-DEPLOY UI STEPS:"
Write-Host "  Frontend Vars: VITE_API_URL=$BackendDomain  -> Redeploy Frontend"
Write-Host "  Backend  Vars: FRONTEND_URL=https://<frontend-domain>  -> Redeploy Backend"
Write-Host ""