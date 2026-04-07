param([string]$Token = "")
if (-not $Token) {
    $Token = Read-Host "GitHub Personal Access Token (scopes: repo + project)"
}

$owner    = "gaetan-perso"
$repo     = "claude-quizz"
$fullRepo = "$owner/$repo"

$headers = @{
    "Authorization"        = "Bearer $Token"
    "Accept"               = "application/vnd.github+json"
    "X-GitHub-Api-Version" = "2022-11-28"
}

function Invoke-GH([string]$Method, [string]$Path, $Body = $null) {
    $p = @{ Uri = "https://api.github.com$Path"; Method = $Method; Headers = $headers }
    if ($Body) { $p.Body = ($Body | ConvertTo-Json -Depth 10); $p.ContentType = "application/json" }
    try   { return Invoke-RestMethod @p }
    catch { Write-Host "  ERR $Method $Path : $($_.Exception.Message)" -ForegroundColor Red }
}

function Invoke-GQL([string]$Query, $Vars = @{}) {
    $b = (@{ query = $Query; variables = $Vars } | ConvertTo-Json -Depth 10)
    try   { return Invoke-RestMethod -Uri "https://api.github.com/graphql" -Method POST -Headers $headers -Body $b -ContentType "application/json" }
    catch { Write-Host "  ERR GraphQL : $($_.Exception.Message)" -ForegroundColor Red }
}

# ── 1. LABELS ────────────────────────────────────────────────
Write-Host "`n=== 1/4 Labels ===" -ForegroundColor Cyan
$lbls = @(
    [PSCustomObject]@{ name="database"; color="0075ca"; description="Migrations et modeles" }
    [PSCustomObject]@{ name="backend";  color="e4e669"; description="Laravel backend" }
    [PSCustomObject]@{ name="filament"; color="7057ff"; description="Backoffice Filament PHP" }
    [PSCustomObject]@{ name="ai";       color="d93f0b"; description="Generation IA Claude" }
    [PSCustomObject]@{ name="testing";  color="0e8a16"; description="Tests Pest PHPUnit" }
)
foreach ($l in $lbls) {
    $ex = Invoke-GH "GET" "/repos/$fullRepo/labels/$($l.name)"
    if ($ex) { Invoke-GH "PATCH" "/repos/$fullRepo/labels/$($l.name)" $l | Out-Null; Write-Host "  update: $($l.name)" }
    else      { Invoke-GH "POST"  "/repos/$fullRepo/labels" $l           | Out-Null; Write-Host "  create: $($l.name)" }
}

# ── 2. ISSUES ────────────────────────────────────────────────
Write-Host "`n=== 2/4 Issues ===" -ForegroundColor Cyan

$issues = [System.Collections.Generic.List[object]]::new()

$issues.Add([PSCustomObject]@{
    Title  = "[T01] Migration : table categories"
    Labels = @("database","backend")
    Body   = @"
## Description
Creer la migration pour la table categories.

## Fichier
database/migrations/2026_04_07_000001_create_categories_table.php

## Colonnes
id (ulid PK), name string(100), slug string(100) unique, icon string(50) nullable, color string(7) default #6366f1, is_active boolean, timestamps, softDeletes

## Index
is_active, slug

## Commit
feat(db): create categories table
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T02] Migration : table questions"
    Labels = @("database","backend")
    Body   = @"
## Description
Creer la migration pour la table questions.

## Fichier
database/migrations/2026_04_07_000002_create_questions_table.php

## Colonnes
id (ulid), category_id (FK ulid cascade), text, difficulty enum(easy/medium/hard), type enum(multiple_choice/open), explanation text nullable, tags json nullable, estimated_time_seconds smallint default 30, is_active boolean, source enum(manual/ai_generated), timestamps, softDeletes

## Index
category_id, difficulty, is_active, [difficulty + is_active]

## Commit
feat(db): create questions table
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T03] Migration : table choices"
    Labels = @("database","backend")
    Body   = @"
## Description
Creer la migration pour la table choices (reponses QCM).

## Fichier
database/migrations/2026_04_07_000003_create_choices_table.php

## Colonnes
id (ulid), question_id (FK ulid cascadeOnDelete), text string(500), is_correct boolean default false, order tinyint unsigned default 0, timestamps

## Index
question_id, [question_id + is_correct]

## Commit
feat(db): create choices table
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T04] Migration : colonne role sur users"
    Labels = @("database","backend")
    Body   = @"
## Description
Ajouter la colonne role sur la table users pour distinguer admins et joueurs.

## Fichier
database/migrations/2026_04_07_000004_add_role_to_users_table.php

## Changement
Colonne role : enum(player, admin), default player, after email. Index sur role.

## Commit
feat(db): add role enum to users table
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T05] Enums PHP : Difficulty, UserRole, QuestionType, QuestionSource"
    Labels = @("backend")
    Body   = @"
## Description
Creer les 4 backed enums PHP 8.3+.

## Fichiers
- app/Enums/Difficulty.php : easy / medium / hard + methodes label() et color()
- app/Enums/UserRole.php : player / admin
- app/Enums/QuestionType.php : multiple_choice / open
- app/Enums/QuestionSource.php : manual / ai_generated

## Verification
php artisan tinker --execute="echo App\Enums\Difficulty::Hard->label();"
Expected : Difficile

## Commit
feat(enums): add Difficulty, UserRole, QuestionType, QuestionSource
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T06] Model Category + CategoryFactory"
    Labels = @("backend")
    Body   = @"
## Description
Creer le model Eloquent Category et sa factory.

## Fichiers
- app/Models/Category.php
- database/factories/CategoryFactory.php

## Model
Traits : HasUlids, SoftDeletes, HasFactory
fillable : name, slug, icon, color, is_active
casts : is_active boolean
Relation : questions() HasMany
Scope : active()

## Factory
Etat : inactive()

## Commit
feat(model): add Category model with factory
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T07] Models Question + Choice + Factories"
    Labels = @("backend")
    Body   = @"
## Description
Creer les models Question et Choice avec leurs factories.

## Fichiers
- app/Models/Question.php
- app/Models/Choice.php
- database/factories/QuestionFactory.php
- database/factories/ChoiceFactory.php

## Question - casts
difficulty, type, source vers leurs enums. tags array. is_active boolean.

## Question - relations
category() BelongsTo, choices() HasMany orderBy(order), correctChoice() HasMany where is_correct=true

## Question - scopes
active(), forDifficulty(Difficulty)

## Factory etats
QuestionFactory : aiGenerated(), inactive()
ChoiceFactory : correct()

## Commit
feat(model): add Question and Choice models with factories
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T08] Model User : role + interface Filament"
    Labels = @("backend","filament")
    Body   = @"
## Description
Modifier app/Models/User.php pour supporter les roles et l'acces Filament.

## Changements
1. Ajouter role dans fillable
2. Ajouter cast 'role' => UserRole::class
3. Ajouter methode isAdmin(): bool
4. Implementer interface FilamentUser -> canAccessPanel() retourne isAdmin()

## Commit
feat(model): add role cast and isAdmin() to User
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T09] Installation Filament v3 + panel /admin"
    Labels = @("filament","backend")
    Body   = @"
## Description
Installer Filament PHP v3 et configurer le panel admin.

## Commandes
composer require filament/filament:"^3.0"
php artisan filament:install --panels

## Configuration AdminPanelProvider
- id: admin, path: /admin
- Couleur primaire: Indigo
- Login active
- Autodiscovery Resources / Pages / Widgets dans app/Filament/
- AuthMiddleware: Authenticate::class

## Verification
php artisan route:list | grep admin
Expected : GET|HEAD admin/login visible

## Commit
feat(backoffice): install Filament v3 admin panel
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T10] Filament Resource : CategoryResource CRUD"
    Labels = @("filament")
    Body   = @"
## Description
Creer le CRUD Filament pour les categories.

## Fichiers
- app/Filament/Resources/CategoryResource.php
- app/Filament/Resources/CategoryResource/Pages/ListCategories.php
- app/Filament/Resources/CategoryResource/Pages/CreateCategory.php
- app/Filament/Resources/CategoryResource/Pages/EditCategory.php

## Formulaire
name (auto-slug), slug, icon (emoji), color (ColorPicker), is_active (Toggle)

## Table
icon, name, slug, ColorColumn, questions_count, ToggleColumn is_active, created_at since

## Filtres
TernaryFilter sur is_active

## Navigation
Groupe : Bibliotheque, icone : heroicon-o-tag, sort : 1

## Commit
feat(backoffice): add CategoryResource with CRUD
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T11] Filament Resource : QuestionResource liste et filtres"
    Labels = @("filament")
    Body   = @"
## Description
Creer le CRUD Filament complet pour les questions.

## Fichier
app/Filament/Resources/QuestionResource.php

## Formulaire - 3 sections
Section Question : category (Select searchable), text (Textarea), difficulty, type, estimated_time_seconds, tags (TagsInput), is_active
Section Choix (visible si type=multiple_choice) : Repeater 4 choix min/max, chaque choix : text + is_correct Toggle
Section Pedagogie : explanation Textarea

## Table
category badge, text limit(60) + tooltip, difficulty badge colore, source badge, choices_count, is_active toggle, created_at

## Filtres
category Select, difficulty Select, source Select, is_active Ternary

## Navigation
Groupe : Bibliotheque, icone : heroicon-o-question-mark-circle, sort : 2

## Commit
feat(backoffice): add QuestionResource with CRUD and filters
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T12] Pages pour QuestionResource"
    Labels = @("filament")
    Body   = @"
## Description
Creer les 3 pages Filament pour QuestionResource.

## Fichiers
- app/Filament/Resources/QuestionResource/Pages/ListQuestions.php
- app/Filament/Resources/QuestionResource/Pages/CreateQuestion.php
- app/Filament/Resources/QuestionResource/Pages/EditQuestion.php

## Specificite ListQuestions
Ajouter bouton "Generer via IA" dans getHeaderActions() qui redirige vers la page GenerateQuestions.

## Commit
feat(backoffice): add QuestionResource pages
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T13] Page Filament : Generation IA de questions"
    Labels = @("filament","ai")
    Body   = @"
## Description
Creer une page Filament pour declencher la generation IA via Claude API.

## Fichiers
- app/Filament/Pages/GenerateQuestions.php
- resources/views/filament/pages/generate-questions.blade.php

## Formulaire
- topic TextInput required (ex: Revolution francaise)
- category_id Select options Category::active()
- difficulty Select options Difficulty::cases()
- count numeric 1-20 default 5

## Action generate()
1. Recuperer donnees validees
2. Dispatch GenerateQuestionsJob en queue
3. Notification Filament de confirmation

## Navigation
Groupe : Bibliotheque, icone : heroicon-o-sparkles, sort : 3

## Commit
feat(backoffice): add AI question generation page
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T14] Filament Resource : UserResource gestion roles"
    Labels = @("filament")
    Body   = @"
## Description
Creer la resource Filament pour la gestion des utilisateurs.

## Fichiers
- app/Filament/Resources/UserResource.php
- app/Filament/Resources/UserResource/Pages/ListUsers.php
- app/Filament/Resources/UserResource/Pages/EditUser.php

## Regles metier
Pas de creation depuis le backoffice. Pas de bulk delete.
Edition : name, email, role uniquement.

## Table
name searchable, email, role badge (warning si admin), created_at since

## Filtre
SelectFilter sur role

## Navigation
Groupe : Administration, icone : heroicon-o-users, sort : 1

## Commit
feat(backoffice): add UserResource (role management)
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T15] Widget Dashboard : QuizStatsOverview"
    Labels = @("filament")
    Body   = @"
## Description
Creer un widget StatsOverview pour le dashboard Filament.

## Fichier
app/Filament/Widgets/QuizStatsOverview.php

## 4 stats
- Questions actives : Question::active()->count() - icone question-mark-circle - couleur success
- Questions IA generees : source=ai_generated count - icone sparkles - couleur primary
- Categories actives : Category::active()->count() - icone tag - couleur warning
- Utilisateurs : User::count() + nb admins en description - icone users - couleur gray

## Commit
feat(backoffice): add QuizStatsOverview dashboard widget
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T16] Seeders : admin + 8 categories de base"
    Labels = @("database","backend")
    Body   = @"
## Description
Creer les seeders pour bootstrapper l'environnement.

## Fichiers
- database/seeders/AdminUserSeeder.php
- database/seeders/CategorySeeder.php
- database/seeders/DatabaseSeeder.php (modifier)

## AdminUserSeeder
firstOrCreate admin@quiz.local / password avec role=admin

## CategorySeeder
8 categories par slug (firstOrCreate) :
Histoire, Geographie, Sciences, Informatique, Litterature, Sport, Cinema, Musique

## Verification
php artisan db:seed puis login admin@quiz.local sur /admin

## Commit
feat(db): add admin and category seeders
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T17] Tests Feature : CategoryResource Pest"
    Labels = @("testing")
    Body   = @"
## Description
Tests Pest pour le CRUD categories en backoffice.

## Fichier
tests/Feature/Backoffice/CategoryResourceTest.php

## Tests
1. Admin voit les categories dans la table Filament
2. Admin peut creer une categorie via le formulaire
3. Joueur (role=player) recoit 403 sur /admin

## Commande
vendor/bin/pest tests/Feature/Backoffice/CategoryResourceTest.php
Expected : PASS (3 tests, 5 assertions)

## Commit
test(backoffice): add CategoryResource feature tests
"@
})

$issues.Add([PSCustomObject]@{
    Title  = "[T18] Tests Feature : QuestionResource + generation IA Pest"
    Labels = @("testing","ai")
    Body   = @"
## Description
Tests Pest pour le CRUD questions et la generation IA.

## Fichier
tests/Feature/Backoffice/QuestionResourceTest.php

## Tests
1. Admin voit les questions avec filtres
2. Admin peut creer une question manuellement
3. GenerateQuestionsJob dispatche via Queue::fake() quand le formulaire IA est soumis

## Commande
vendor/bin/pest tests/Feature/Backoffice/QuestionResourceTest.php
Expected : PASS (3 tests, 6 assertions)

## Commit
test(backoffice): add QuestionResource and AI generation tests
"@
})

$issueNumbers = [System.Collections.Generic.List[int]]::new()

foreach ($issue in $issues) {
    $payload = @{
        title  = $issue.Title
        body   = $issue.Body
        labels = $issue.Labels
    }
    $r = Invoke-GH "POST" "/repos/$fullRepo/issues" $payload
    if ($r) {
        $issueNumbers.Add($r.number)
        Write-Host "  #$($r.number) $($issue.Title)"
    }
    Start-Sleep -Milliseconds 500
}

# ── 3. PROJET GITHUB (GraphQL) ───────────────────────────────
Write-Host "`n=== 3/4 Projet GitHub ===" -ForegroundColor Cyan

$r = Invoke-GQL 'query { user(login: "gaetan-perso") { id } }'
$ownerId = $r.data.user.id
Write-Host "  Owner ID : $ownerId"

$r = Invoke-GQL 'mutation($o:ID!,$t:String!){createProjectV2(input:{ownerId:$o,title:$t}){projectV2{id number url}}}' @{ o=$ownerId; t="Backoffice Quiz" }
$projectId  = $r.data.createProjectV2.projectV2.id
$projectUrl = $r.data.createProjectV2.projectV2.url
Write-Host "  Projet : $projectUrl"

# ── 4. AJOUT DES ISSUES AU PROJET ────────────────────────────
Write-Host "`n=== 4/4 Ajout au projet ===" -ForegroundColor Cyan

foreach ($num in $issueNumbers) {
    $r  = Invoke-GQL 'query($o:String!,$r:String!,$n:Int!){repository(owner:$o,name:$r){issue(number:$n){id}}}' @{ o=$owner; r=$repo; n=$num }
    $id = $r.data.repository.issue.id
    Invoke-GQL 'mutation($p:ID!,$c:ID!){addProjectV2ItemById(input:{projectId:$p,contentId:$c}){item{id}}}' @{ p=$projectId; c=$id } | Out-Null
    Write-Host "  #$num ajoute"
    Start-Sleep -Milliseconds 300
}

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "  TERMINE - $($issueNumbers.Count) issues creees" -ForegroundColor Green
Write-Host "  $projectUrl" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
